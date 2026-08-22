<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PreviewOutgoingLetterRequest;
use App\Http\Requests\StoreOutgoingLetterRequest;
use App\Http\Requests\UpdateOutgoingLetterRequest;
use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Services\DocxTteService;
use App\Services\LetterTypeService;
use App\Services\OutgoingLetterService;
use App\Services\OutgoingLetterTemplateService;
use App\Services\VerificationQrCodeService;
use App\Services\DocxPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OutgoingLetterController extends Controller
{
    public function __construct(
        private readonly OutgoingLetterService $outgoingLetterService,
        private readonly LetterTypeService $letterTypeService,
        private readonly OutgoingLetterTemplateService $outgoingLetterTemplateService,
        private readonly VerificationQrCodeService $verificationQrCodeService,
        private readonly DocxPdfService $docxPdfService,
        private readonly DocxTteService $docxTteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OutgoingLetter::class);
        return response()->json(['data' => $this->outgoingLetterService->getAll($request->user()->tenant_id)]);
    }

    public function preview(PreviewOutgoingLetterRequest $request): JsonResponse
    {
        $this->authorize('create', OutgoingLetter::class);
        $data = $request->validated();
        $tenant = $request->user()->tenant;
        $letterType = $this->letterTypeService->find($data['letter_type_id'], $tenant->id);
        if ($letterType === null) return response()->json(['message' => 'Letter type not found.'], 404);
        if ($letterType->body_template === null) return response()->json(['message' => 'The selected letter type has no template.'], 422);
        return response()->json(['data' => ['letter_type_id' => $letterType->id, 'content' => $this->outgoingLetterTemplateService->render($letterType, $tenant, $data)]]);
    }

    public function store(StoreOutgoingLetterRequest $request): JsonResponse
    {
        $this->authorize('create', OutgoingLetter::class);
        $data = $request->validated();
        $tenant = $request->user()->tenant;
        $letterType = $this->letterTypeService->find($data['letter_type_id'], $tenant->id);
        if ($letterType === null) return response()->json(['message' => 'Letter type not found.'], 404);
        $templateVersion = $this->letterTypeService->ensureCurrentVersion($letterType);
        if (! isset($data['content']) && $templateVersion !== null) $data['content'] = $this->outgoingLetterTemplateService->renderVersion($templateVersion, $tenant, $data);
        if (! isset($data['content']) || trim($data['content']) === '') return response()->json(['message' => 'The content field is required when the letter type has no template.', 'errors' => ['content' => ['The content field is required.']]], 422);
        $outgoingLetter = $this->outgoingLetterService->create([...$data, 'tenant_id' => $request->user()->tenant_id, 'letter_type_version_id' => $templateVersion?->id, 'status' => OutgoingLetterStatus::DRAFT], $request->user()->id);
        return response()->json(['data' => $outgoingLetter], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);
        if ($outgoingLetter === null) return $this->notFoundResponse();
        $this->authorize('view', $outgoingLetter);
        return response()->json(['data' => $outgoingLetter]);
    }

    public function history(Request $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);
        if ($outgoingLetter === null) return $this->notFoundResponse();
        $this->authorize('view', $outgoingLetter);
        return response()->json(['data' => $outgoingLetter->statusHistories()->with('changedBy:id,name')->get()]);
    }

    public function update(UpdateOutgoingLetterRequest $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);
        if ($outgoingLetter === null) return $this->notFoundResponse();
        $this->authorize('update', $outgoingLetter);
        return response()->json(['data' => $this->outgoingLetterService->update($outgoingLetter, $request->validated())]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);
        if ($outgoingLetter === null) return $this->notFoundResponse();
        $this->authorize('delete', $outgoingLetter);
        $this->outgoingLetterService->delete($outgoingLetter);
        return response()->json(['message' => 'Outgoing letter deleted successfully.']);
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->outgoingLetterService->findWithTrashed($id, $request->user()->tenant_id);
        if ($outgoingLetter === null) return $this->notFoundResponse();
        $this->authorize('restore', $outgoingLetter);
        $this->outgoingLetterService->restore($outgoingLetter);
        return response()->json(['data' => $outgoingLetter->refresh()]);
    }

    public function validateLetter(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'validate', fn (OutgoingLetter $letter) => $this->outgoingLetterService->validate($letter, $request->user()->id));
    }

    public function issue(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'issue', fn (OutgoingLetter $letter) => $this->outgoingLetterService->issue($letter, $request->user()->id));
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'cancel', fn (OutgoingLetter $letter) => $this->outgoingLetterService->cancel($letter, $request->user()->id));
    }

    public function downloadPdf(Request $request, string $id): Response
    {
        $outgoingLetter = $this->findForTenant($id, $request);
        if ($outgoingLetter === null) abort(404, 'Outgoing letter not found.');
        $this->authorize('view', $outgoingLetter);
        $outgoingLetter->loadMissing(['tenant', 'letterType', 'letterTypeVersion']);

        $docxPath = $outgoingLetter->generated_docx_path;
        if (! $docxPath || ! Storage::disk('local')->exists($docxPath)) abort(422, 'DOCX hasil surat belum tersedia. Buat ulang draft surat terlebih dahulu.');

        // Backfill the TTE for drafts created before the QR integration.
        if (blank($outgoingLetter->verification_token)) {
            $outgoingLetter->verification_token = Str::random(64);
            $outgoingLetter->save();
        }

        try {
            $verificationUrl = url('/verify/' . $outgoingLetter->verification_token);
            $this->docxTteService->embed(Storage::disk('local')->path($docxPath), $verificationUrl);
            $pdfPath = $this->docxPdfService->convert($docxPath);
        } catch (\RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        $absolutePath = Storage::disk('local')->path($pdfPath);
        $filename = sprintf('surat-%s.pdf', str($outgoingLetter->number)->slug());
        $headers = ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $filename . '"'];
        if ($request->boolean('download')) $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        return response()->file($absolutePath, $headers);
    }

    private function findForTenant(string $id, Request $request): ?OutgoingLetter
    {
        return $this->outgoingLetterService->find($id, $request->user()->tenant_id);
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json(['message' => 'Outgoing letter not found.'], 404);
    }

    private function transition(Request $request, string $id, string $ability, callable $transition): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);
        if ($outgoingLetter === null) return $this->notFoundResponse();
        $this->authorize($ability, $outgoingLetter);
        try {
            $outgoingLetter = $transition($outgoingLetter);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
        return response()->json(['data' => $outgoingLetter]);
    }
}

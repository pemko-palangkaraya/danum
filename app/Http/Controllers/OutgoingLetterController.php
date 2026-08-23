<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PreviewOutgoingLetterRequest;
use App\Http\Requests\StoreOutgoingLetterRequest;
use App\Http\Requests\UpdateOutgoingLetterRequest;
use App\Enums\OutgoingLetterStatus;
use App\Enums\PositionStatus;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Services\DocxTteService;
use App\Services\LetterTypeService;
use App\Services\OutgoingLetterService;
use App\Services\OutgoingLetterTemplateService;
use App\Services\VerificationQrCodeService;
use App\Services\DocxPdfService;
use App\Services\PdfPreviewWatermarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;

class OutgoingLetterController extends Controller
{
    public function __construct(
        private readonly OutgoingLetterService $outgoingLetterService,
        private readonly LetterTypeService $letterTypeService,
        private readonly OutgoingLetterTemplateService $outgoingLetterTemplateService,
        private readonly VerificationQrCodeService $verificationQrCodeService,
        private readonly DocxPdfService $docxPdfService,
        private readonly DocxTteService $docxTteService,
        private readonly PdfPreviewWatermarkService $pdfPreviewWatermarkService,
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

        $signer = null;
        if (! empty($data['signer_position_id'])) {
            $signer = Position::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', PositionStatus::ACTIVE)
                ->where('can_sign', true)
                ->whereNull('deleted_at')
                ->with(['holders' => fn ($query) => $query
                    ->whereNull('ended_at')
                    ->where('started_at', '<=', now())
                    ->with('user')])
                ->find($data['signer_position_id']);

            $holder = $signer?->holders->first();
            if (! $signer || ! $holder?->user) {
                return response()->json([
                    'message' => 'Signer position is unavailable or has no active holder.',
                    'errors' => ['signer_position_id' => ['The selected signer position is not currently available.']],
                ], 422);
            }

            $data['tenant_head_name'] = $holder->user->name;
            $data['tenant_head_title'] = $signer->name;
            $data['signer_user_id'] = $holder->user_id;
            $data['signer_name'] = $holder->user->name;
            $data['signer_title'] = $signer->name;
        }

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
        try {
            $data = $request->validated();
            if (array_key_exists('signer_position_id', $data)) {
                $signer = Position::query()
                    ->where('tenant_id', $request->user()->tenant_id)
                    ->where('status', PositionStatus::ACTIVE)
                    ->where('can_sign', true)
                    ->with(['holders' => fn ($query) => $query->whereNull('ended_at')->where('started_at', '<=', now())->with('user')])
                    ->find($data['signer_position_id']);
                $holder = $signer?->holders->first();
                if (! $signer || ! $holder?->user) return response()->json(['message' => 'Signer position is unavailable or has no active holder.'], 422);
                $data['signer_user_id'] = $holder->user_id;
                $data['signer_name'] = $holder->user->name;
                $data['signer_title'] = $signer->name;
            }
            $outgoingLetter = $this->outgoingLetterService->update($outgoingLetter, $data);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
        return response()->json(['data' => $outgoingLetter]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);
        if ($outgoingLetter === null) return $this->notFoundResponse();
        $this->authorize('delete', $outgoingLetter);
        try { $this->outgoingLetterService->delete($outgoingLetter); }
        catch (\DomainException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
        return response()->json(['message' => 'Outgoing letter deleted successfully.']);
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->outgoingLetterService->findWithTrashed($id, $request->user()->tenant_id);
        if ($outgoingLetter === null) return $this->notFoundResponse();
        $this->authorize('restore', $outgoingLetter);
        try { $this->outgoingLetterService->restore($outgoingLetter); }
        catch (\DomainException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
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
        $outgoingLetter->loadMissing(['tenant', 'letterType', 'letterTypeVersion', 'signerPosition', 'signerUser']);

        $sourceDocxPath = $outgoingLetter->generated_docx_path;
        if (! $sourceDocxPath || ! Storage::disk('local')->exists($sourceDocxPath)) abort(422, 'DOCX hasil surat belum tersedia. Buat ulang draft surat terlebih dahulu.');

        $isIssued = $outgoingLetter->status === OutgoingLetterStatus::ISSUED;
        $temporaryDocx = null;

        try {
            if ($isIssued) {
                if (blank($outgoingLetter->verification_token)) abort(422, 'Surat terbit belum memiliki token verifikasi. Terbitkan ulang surat tersebut.');
                $verificationUrl = url('/verify/' . $outgoingLetter->verification_token);
                $temporaryDocx = $this->docxTteService->createIssuedCopy(Storage::disk('local')->path($sourceDocxPath), $verificationUrl);
                $pdfPath = $this->docxPdfService->convert($temporaryDocx);
            } else {
                $temporaryDocx = $this->docxTteService->createPreviewCopy(Storage::disk('local')->path($sourceDocxPath));
                $pdfPath = $this->docxPdfService->convert($temporaryDocx);
            }

            $absolutePath = Storage::disk('local')->path($pdfPath);
            if (! $isIssued) {
                $label = sprintf('%s | %s | PREVIEW', $request->user()->name, now()->format('d-m-Y'));
                $watermarked = $this->pdfPreviewWatermarkService->apply($absolutePath, $label);
                $filename = sprintf('preview-surat-%s.pdf', str($outgoingLetter->number)->slug());
                return response()->file($watermarked, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $filename . '"'])->deleteFileAfterSend(true);
            }
        } catch (\RuntimeException $exception) {
            abort(422, $exception->getMessage());
        } finally {
            if ($temporaryDocx !== null) @unlink($temporaryDocx);
        }

        $filename = sprintf('surat-%s.pdf', str($outgoingLetter->number)->slug());
        $headers = ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $filename . '"'];
        if ($request->boolean('download')) $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        return response()->file($absolutePath, $headers);
    }

    private function findForTenant(string $id, Request $request): ?OutgoingLetter { return $this->outgoingLetterService->find($id, $request->user()->tenant_id); }
    private function notFoundResponse(): JsonResponse { return response()->json(['message' => 'Outgoing letter not found.'], 404); }

    private function transition(Request $request, string $id, string $ability, callable $transition): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);
        if ($outgoingLetter === null) return $this->notFoundResponse();
        $this->authorize($ability, $outgoingLetter);
        try { $outgoingLetter = $transition($outgoingLetter); }
        catch (\DomainException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
        return response()->json(['data' => $outgoingLetter]);
    }
}

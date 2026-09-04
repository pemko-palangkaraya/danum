<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OutgoingLetterStatus;
use App\Http\Requests\PreviewOutgoingLetterRequest;
use App\Http\Requests\StoreOutgoingLetterRequest;
use App\Http\Requests\UpdateOutgoingLetterRequest;
use App\Models\OutgoingLetter;
use App\Services\LetterTypeService;
use App\Services\OutgoingLetterParticipantService;
use App\Services\OutgoingLetterPdfPreviewService;
use App\Services\OutgoingLetterService;
use App\Services\OutgoingLetterTemplateService;
use App\Services\OutgoingLetterWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OutgoingLetterController extends Controller
{
    public function __construct(
        private readonly OutgoingLetterService $outgoingLetterService,
        private readonly OutgoingLetterWorkflowService $workflowService,
        private readonly OutgoingLetterParticipantService $participantService,
        private readonly LetterTypeService $letterTypeService,
        private readonly OutgoingLetterTemplateService $outgoingLetterTemplateService,
        private readonly OutgoingLetterPdfPreviewService $pdfPreviewService,
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

        if ($error = $this->participantService->resolveSigner($data, $tenant->id, false, true)) return $this->participantError($error);
        if ($error = $this->participantService->resolveValidator($data, $tenant->id, false)) return $this->participantError($error);

        return response()->json(['data' => [
            'letter_type_id' => $letterType->id,
            'content' => $this->outgoingLetterTemplateService->render($letterType, $tenant, $data),
        ]]);
    }

    public function store(StoreOutgoingLetterRequest $request): JsonResponse
    {
        $this->authorize('create', OutgoingLetter::class);
        $data = $request->validated();
        $tenant = $request->user()->tenant;
        $letterType = $this->letterTypeService->find($data['letter_type_id'], $tenant->id);
        if ($letterType === null) return response()->json(['message' => 'Letter type not found.'], 404);

        if ($error = $this->participantService->resolveSigner($data, $tenant->id, true)) return $this->participantError($error);
        if ($error = $this->participantService->resolveValidator($data, $tenant->id, true)) return $this->participantError($error);

        $templateVersion = $this->letterTypeService->ensureCurrentVersion($letterType);
        if (! isset($data['content']) && $templateVersion !== null) {
            $data['content'] = $this->outgoingLetterTemplateService->renderVersion($templateVersion, $tenant, $data);
        }
        if (! isset($data['content']) || trim($data['content']) === '') {
            return response()->json([
                'message' => 'The content field is required when the letter type has no template.',
                'errors' => ['content' => ['The content field is required.']],
            ], 422);
        }

        $outgoingLetter = $this->outgoingLetterService->create([
            ...$data,
            'tenant_id' => $request->user()->tenant_id,
            'created_by' => $request->user()->id,
            'letter_type_version_id' => $templateVersion?->id,
            'status' => OutgoingLetterStatus::DRAFT,
        ], $request->user()->id);

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
            if ($error = $this->participantService->resolveSigner($data, $request->user()->tenant_id)) return $this->participantError($error);
            if ($error = $this->participantService->resolveValidator($data, $request->user()->tenant_id)) return $this->participantError($error);
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

    public function submit(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'submit', fn (OutgoingLetter $letter) => $this->workflowService->submit($letter, $request->user()->id));
    }

    public function validateLetter(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'validate', fn (OutgoingLetter $letter) => $this->workflowService->validate($letter, $request->user()->id));
    }

    public function issue(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'issue', fn (OutgoingLetter $letter) => $this->outgoingLetterService->issue($letter, $request->user()->id));
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        return $this->transition($request, $id, 'reject', fn (OutgoingLetter $letter) => $this->workflowService->reject($letter, $request->user()->id, $request->string('reason')->toString()));
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'cancel', fn (OutgoingLetter $letter) => $this->workflowService->cancel($letter, $request->user()->id));
    }

    public function downloadPdf(Request $request, string $id): Response
    {
        $outgoingLetter = $this->findForTenant($id, $request);
        if ($outgoingLetter === null) abort(404, 'Outgoing letter not found.');
        $this->authorize('view', $outgoingLetter);
        return $this->pdfPreviewService->respond($outgoingLetter, $request);
    }

    private function participantError(array $error): JsonResponse
    {
        return response()->json(
            array_filter(['message' => $error['message'] ?? null, 'errors' => $error['errors'] ?? null]),
            $error['status'] ?? 422,
        );
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
        try { $outgoingLetter = $transition($outgoingLetter); }
        catch (\DomainException $exception) { return response()->json(['message' => $exception->getMessage()], 422); }
        return response()->json(['data' => $outgoingLetter]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OutgoingLetterStatus;
use App\Http\Requests\PreviewOutgoingLetterRequest;
use App\Http\Requests\StoreOutgoingLetterRequest;
use App\Http\Requests\UpdateOutgoingLetterRequest;
use App\Models\OutgoingLetter;
use App\Services\LetterTypeService;
use App\Services\OutgoingLetterPdfPreviewService;
use App\Services\OutgoingLetterPositionService;
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
        private readonly LetterTypeService $letterTypeService,
        private readonly OutgoingLetterTemplateService $outgoingLetterTemplateService,
        private readonly OutgoingLetterPositionService $positionService,
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

        $signerError = $this->resolveSigner($data, $tenant->id, false, true);
        if ($signerError instanceof JsonResponse) return $signerError;
        $validatorError = $this->resolveValidator($data, $tenant->id, false);
        if ($validatorError instanceof JsonResponse) return $validatorError;

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

        $signerError = $this->resolveSigner($data, $tenant->id, true, false);
        if ($signerError instanceof JsonResponse) return $signerError;
        $validatorError = $this->resolveValidator($data, $tenant->id, true);
        if ($validatorError instanceof JsonResponse) return $validatorError;

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
            $signerError = $this->resolveSigner($data, $request->user()->tenant_id);
            if ($signerError instanceof JsonResponse) return $signerError;
            $validatorError = $this->resolveValidator($data, $request->user()->tenant_id);
            if ($validatorError instanceof JsonResponse) return $validatorError;
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

    private function resolveSigner(array &$data, string $tenantId, bool $includeErrors = false, bool $previewOnly = false): ?JsonResponse
    {
        if (! array_key_exists('signer_position_id', $data) || empty($data['signer_position_id'])) return null;
        $signer = $this->positionService->findAvailable($tenantId, $data['signer_position_id'], 'can_sign');
        $holder = $signer?->holders->first();
        if (! $signer || ! $holder?->user) {
            return response()->json([
                'message' => 'Signer position is unavailable or has no active holder.',
                ...($includeErrors ? ['errors' => ['signer_position_id' => ['The selected signer position is not currently available.']]] : []),
            ], 422);
        }
        $data['tenant_head_name'] = $holder->user->name;
        $data['tenant_head_title'] = $signer->name;
        if (! $previewOnly) {
            $data['signer_user_id'] = $holder->user_id;
            $data['signer_name'] = $holder->user->name;
            $data['signer_title'] = $signer->name;
        }
        return null;
    }

    private function resolveValidator(array &$data, string $tenantId, bool $includeErrors = false): ?JsonResponse
    {
        if (! array_key_exists('validator_position_id', $data) || empty($data['validator_position_id'])) return null;
        $validator = $this->positionService->findAvailable($tenantId, $data['validator_position_id'], 'can_validate');
        $holder = $validator?->holders->first();
        if (! $validator || ! $holder?->user) {
            return response()->json([
                'message' => 'Validator position is unavailable or has no active holder.',
                ...($includeErrors ? ['errors' => ['validator_position_id' => ['The selected validator position is not currently available.']]] : []),
            ], 422);
        }
        $data['validator_user_id'] = $holder->user_id;
        $data['validator_name'] = $holder->user->name;
        $data['validator_title'] = $validator->name;
        return null;
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

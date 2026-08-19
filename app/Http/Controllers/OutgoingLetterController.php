<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutgoingLetterRequest;
use App\Http\Requests\UpdateOutgoingLetterRequest;
use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Services\OutgoingLetterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class OutgoingLetterController extends Controller
{
    public function __construct(
        private readonly OutgoingLetterService $outgoingLetterService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OutgoingLetter::class);

        return response()->json([
            'data' => $this->outgoingLetterService->getAll($request->user()->tenant_id),
        ]);
    }

    public function store(StoreOutgoingLetterRequest $request): JsonResponse
    {
        $this->authorize('create', OutgoingLetter::class);

        $outgoingLetter = $this->outgoingLetterService->create([
            ...$request->validated(),
            'tenant_id' => $request->user()->tenant_id,
            'status' => OutgoingLetterStatus::DRAFT,
        ]);

        return response()->json(['data' => $outgoingLetter], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);

        if ($outgoingLetter === null) {
            return $this->notFoundResponse();
        }

        $this->authorize('view', $outgoingLetter);

        return response()->json(['data' => $outgoingLetter]);
    }

    public function update(UpdateOutgoingLetterRequest $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);

        if ($outgoingLetter === null) {
            return $this->notFoundResponse();
        }

        $this->authorize('update', $outgoingLetter);

        return response()->json([
            'data' => $this->outgoingLetterService->update(
                $outgoingLetter,
                $request->validated(),
            ),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);

        if ($outgoingLetter === null) {
            return $this->notFoundResponse();
        }

        $this->authorize('delete', $outgoingLetter);
        $this->outgoingLetterService->delete($outgoingLetter);

        return response()->json([
            'message' => 'Outgoing letter deleted successfully.',
        ]);
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->outgoingLetterService->findWithTrashed(
            $id,
            $request->user()->tenant_id,
        );

        if ($outgoingLetter === null) {
            return $this->notFoundResponse();
        }

        $this->authorize('restore', $outgoingLetter);
        $this->outgoingLetterService->restore($outgoingLetter);

        return response()->json(['data' => $outgoingLetter->refresh()]);
    }

    public function validateLetter(Request $request, string $id): JsonResponse
    {
        return $this->transition(
            $request,
            $id,
            'validate',
            fn (OutgoingLetter $outgoingLetter) => $this->outgoingLetterService->validate($outgoingLetter),
        );
    }

    public function issue(Request $request, string $id): JsonResponse
    {
        return $this->transition(
            $request,
            $id,
            'issue',
            fn (OutgoingLetter $outgoingLetter) => $this->outgoingLetterService->issue($outgoingLetter),
        );
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        return $this->transition(
            $request,
            $id,
            'cancel',
            fn (OutgoingLetter $outgoingLetter) => $this->outgoingLetterService->cancel($outgoingLetter),
        );
    }

    public function downloadPdf(Request $request, string $id): Response
    {
        $outgoingLetter = $this->findForTenant($id, $request);

        if ($outgoingLetter === null) {
            abort(404, 'Outgoing letter not found.');
        }

        $this->authorize('view', $outgoingLetter);

        $outgoingLetter->loadMissing(['tenant', 'letterType']);

        return Pdf::loadView('pdf.outgoing-letter', [
            'letter' => $outgoingLetter,
        ])->setPaper('a4')->download(sprintf(
            'surat-%s.pdf',
            str($outgoingLetter->number)->slug(),
        ));
    }

    private function findForTenant(string $id, Request $request): ?OutgoingLetter
    {
        return $this->outgoingLetterService->find($id, $request->user()->tenant_id);
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Outgoing letter not found.',
        ], 404);
    }

    private function transition(
        Request $request,
        string $id,
        string $ability,
        callable $transition,
    ): JsonResponse {
        $outgoingLetter = $this->findForTenant($id, $request);

        if ($outgoingLetter === null) {
            return $this->notFoundResponse();
        }

        $this->authorize($ability, $outgoingLetter);

        try {
            $outgoingLetter = $transition($outgoingLetter);
        } catch (\DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json(['data' => $outgoingLetter]);
    }
}

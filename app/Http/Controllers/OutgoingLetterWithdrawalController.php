<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterWithdrawalRequest;
use App\Services\OutgoingLetterWithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OutgoingLetterWithdrawalController extends Controller
{
    public function __construct(private readonly OutgoingLetterWithdrawalService $service) {}

    public function store(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'statement' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $letter = OutgoingLetter::query()->where('tenant_id', $request->user()->tenant_id)->find($id);
        if ($letter === null) return response()->json(['message' => 'Outgoing letter not found.'], 404);
        $this->authorize('requestWithdrawal', $letter);

        try {
            $path = $request->file('statement')->store('outgoing-letter-withdrawals');
            $withdrawal = $this->service->request(
                $letter,
                $request->user()->id,
                $request->string('reason')->toString(),
                $path,
            );

            return response()->json(['data' => $withdrawal->load(['requestedBy:id,name'])], 201);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function statement(string $id)
    {
        $withdrawal = OutgoingLetterWithdrawalRequest::query()
            ->with('outgoingLetter')
            ->findOrFail($id);

        $this->authorize('decideWithdrawal', $withdrawal->outgoingLetter);

        abort_unless($withdrawal->statement_path, 404);

        $disk = Storage::disk(config('filesystems.default'));
        abort_unless($disk->exists($withdrawal->statement_path), 404);

        return $disk->download(
            $withdrawal->statement_path,
            'surat-pernyataan-' . $withdrawal->outgoingLetter->number . '.' . pathinfo($withdrawal->statement_path, PATHINFO_EXTENSION),
        );
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $withdrawal = OutgoingLetterWithdrawalRequest::query()->with('outgoingLetter')->find($id);
        if ($withdrawal === null) return response()->json(['message' => 'Withdrawal request not found.'], 404);
        $this->authorize('decideWithdrawal', $withdrawal->outgoingLetter);

        try {
            $letter = $this->service->approve($withdrawal, $request->user()->id, $request->input('note'));
            return response()->json(['data' => $letter]);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $withdrawal = OutgoingLetterWithdrawalRequest::query()->with('outgoingLetter')->find($id);
        if ($withdrawal === null) return response()->json(['message' => 'Withdrawal request not found.'], 404);
        $this->authorize('decideWithdrawal', $withdrawal->outgoingLetter);

        try {
            $withdrawal = $this->service->reject($withdrawal, $request->user()->id, $request->string('note')->toString());
            return response()->json(['data' => $withdrawal]);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}

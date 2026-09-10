<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\RegisterEntry;
use App\Services\RegisterEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RegisterEntryController extends Controller
{
    public function index(Request $request, RegisterEntryService $service): JsonResponse
    {
        abort_unless($request->user()->hasPermission('outgoing-letters.view'), 403);
        $tenantId = $request->user()->tenant_id;
        abort_unless($tenantId !== null || $request->user()->isSuperAdmin(), 403);

        $query = RegisterEntry::query()->with(['outgoingLetter', 'registeredBy:id,name']);
        if (! $request->user()->isSuperAdmin()) {
            $query->where('tenant_id', $tenantId);
        } elseif ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->string('tenant_id')->toString());
        }

        if ($request->filled('year')) {
            $query->where('register_year', (int) $request->integer('year'));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->string('source')->toString());
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->where('letter_number', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhere('recipient_name', 'like', "%{$search}%"));
        }

        return response()->json(['data' => $query->orderByDesc('register_year')->orderByDesc('register_number')->paginate(min(100, max(5, (int) $request->integer('per_page', 25)))]]);
    }

    public function store(Request $request, RegisterEntryService $service): JsonResponse
    {
        abort_unless($request->user()->hasPermission('outgoing-letters.create'), 403);
        abort_unless($request->user()->isTenantUser(), 403);

        $data = $request->validate([
            'letter_number' => ['required', 'string', 'max:100'],
            'letter_date' => ['required', 'date'],
            'letter_type_name' => ['nullable', 'string', 'max:255'],
            'classification_code' => ['nullable', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_address' => ['nullable', 'string'],
            'signer_name' => ['nullable', 'string', 'max:255'],
            'signer_title' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            return response()->json(['data' => $service->createManual($data, $request->user())], 201);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function update(Request $request, string $id, RegisterEntryService $service): JsonResponse
    {
        abort_unless($request->user()->hasPermission('outgoing-letters.update'), 403);
        $entry = RegisterEntry::query()->whereKey($id)->firstOrFail();
        if (! $request->user()->isSuperAdmin()) {
            abort_unless($entry->tenant_id === $request->user()->tenant_id, 404);
        }
        abort_unless($entry->source->value === 'manual', 422, 'Register yang berasal dari DANUM tidak diedit dari buku register.');

        $data = $request->validate([
            'letter_number' => ['sometimes', 'string', 'max:100'],
            'letter_date' => ['sometimes', 'date'],
            'letter_type_name' => ['nullable', 'string', 'max:255'],
            'classification_code' => ['nullable', 'string', 'max:100'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'recipient_name' => ['sometimes', 'string', 'max:255'],
            'recipient_address' => ['nullable', 'string'],
            'signer_name' => ['nullable', 'string', 'max:255'],
            'signer_title' => ['nullable', 'string', 'max:255'],
            'correction_reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            return response()->json(['data' => $service->correct($entry, $data, $request->user())]);
        } catch (\DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}

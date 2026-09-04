<?php

declare(strict_types=1);

namespace App\Services;

class OutgoingLetterParticipantService
{
    public function __construct(
        private readonly OutgoingLetterPositionService $positionService,
    ) {}

    /**
     * Resolve the signer position and copy its active holder data into the letter payload.
     * Returns null when no signer position was supplied, or an error payload when unavailable.
     */
    public function resolveSigner(array &$data, string $tenantId, bool $includeErrors = false, bool $previewOnly = false): ?array
    {
        if (! array_key_exists('signer_position_id', $data) || empty($data['signer_position_id'])) {
            return null;
        }

        $signer = $this->positionService->findAvailable($tenantId, $data['signer_position_id'], 'can_sign');
        $holder = $signer?->holders->first();

        if (! $signer || ! $holder?->user) {
            return [
                'message' => 'Signer position is unavailable or has no active holder.',
                ...($includeErrors ? ['errors' => ['signer_position_id' => ['The selected signer position is not currently available.']]] : []),
                'status' => 422,
            ];
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

    /**
     * Resolve the validator position and copy its active holder data into the letter payload.
     * Returns null when no validator position was supplied, or an error payload when unavailable.
     */
    public function resolveValidator(array &$data, string $tenantId, bool $includeErrors = false): ?array
    {
        if (! array_key_exists('validator_position_id', $data) || empty($data['validator_position_id'])) {
            return null;
        }

        $validator = $this->positionService->findAvailable($tenantId, $data['validator_position_id'], 'can_validate');
        $holder = $validator?->holders->first();

        if (! $validator || ! $holder?->user) {
            return [
                'message' => 'Validator position is unavailable or has no active holder.',
                ...($includeErrors ? ['errors' => ['validator_position_id' => ['The selected validator position is not currently available.']]] : []),
                'status' => 422,
            ];
        }

        $data['validator_user_id'] = $holder->user_id;
        $data['validator_name'] = $holder->user->name;
        $data['validator_title'] = $validator->name;

        return null;
    }
}

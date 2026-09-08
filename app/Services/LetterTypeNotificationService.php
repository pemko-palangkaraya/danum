<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Models\LetterType;
use App\Models\LetterTypePermission;
use App\Models\User;
use App\Notifications\LetterTypeLifecycleNotification;
use Illuminate\Support\Collection;

final class LetterTypeNotificationService
{
    public function notifyScheduled(LetterType $letterType): void
    {
        $this->notifyAffectedUsers(
            $letterType,
            'deletion_scheduled',
            $letterType->deletion_scheduled_at?->format('d/m/Y H:i:s'),
        );
    }

    public function notifyDeleted(LetterType $letterType): void
    {
        $this->notifyAffectedUsers($letterType, 'deleted');
    }

    public function notifyRestored(LetterType $letterType): void
    {
        $this->notifyAffectedUsers($letterType, 'restored');
    }

    private function notifyAffectedUsers(LetterType $letterType, string $event, ?string $scheduledAt = null): void
    {
        $tenantIds = $this->affectedTenantIds($letterType);

        if ($tenantIds->isEmpty()) {
            return;
        }

        $users = User::query()
            ->whereIn('tenant_id', $tenantIds->all())
            ->where('status', UserStatus::ACTIVE)
            ->get()
            ->filter(fn (User $user): bool => $user->hasPermission(Permission::OUTGOING_LETTERS_VIEW->value));

        foreach ($users as $user) {
            $user->notify(new LetterTypeLifecycleNotification(
                $event,
                (string) $letterType->id,
                $letterType->name,
                $scheduledAt,
            ));
        }
    }

    private function affectedTenantIds(LetterType $letterType): Collection
    {
        if (! $letterType->isGlobal()) {
            return collect([$letterType->tenant_id]);
        }

        return LetterTypePermission::query()
            ->where('letter_type_id', $letterType->id)
            ->where('allowed', true)
            ->with('category.tenants:id,tenant_category_id')
            ->get()
            ->flatMap(function (LetterTypePermission $permission): Collection {
                $ids = collect();

                if ($permission->tenant_id !== null) {
                    $ids->push($permission->tenant_id);
                }

                if ($permission->category !== null) {
                    $ids = $ids->merge($permission->category->tenants->pluck('id'));
                }

                return $ids;
            })
            ->filter()
            ->unique()
            ->values();
    }
}

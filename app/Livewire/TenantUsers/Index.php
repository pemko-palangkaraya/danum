<?php

namespace App\Livewire\TenantUsers;

use App\Enums\PermissionEnum;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public Tenant $tenant;

    public int $perPage = 5;

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->authorizeView();
    }

    public function authorizeView(): void
    {
        abort_unless(auth()->user()?->hasPermission(PermissionEnum::USERS_VIEW), 403);
    }

    public function render()
    {
        $users = User::query()
            ->with('customRole')
            ->where('tenant_id', $this->tenant->id)
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.tenant-users.index', compact('users'));
    }
}

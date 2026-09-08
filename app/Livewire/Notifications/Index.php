<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Livewire\Concerns\WithStandardTablePagination;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithStandardTablePagination;

    public function openNotification(string $id): void
    {
        $notification = $this->notification($id);

        if ($notification === null) {
            return;
        }

        $notification->markAsRead();
        $url = $notification->data['action_url'] ?? null;

        if ($url) {
            $this->redirect($url, navigate: true);
        }
    }

    public function markAsRead(string $id): void
    {
        $this->notification($id)?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.pages.notifications.index', [
            'notifications' => auth()->user()
                ->notifications()
                ->latest()
                ->paginate($this->perPage),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    private function notification(string $id): ?DatabaseNotification
    {
        return auth()->user()->notifications()->whereKey($id)->first();
    }
}

<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LetterTypeLifecycleNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        private readonly string $letterTypeId,
        private readonly string $letterTypeName,
        private readonly ?string $scheduledAt = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'letter-type-'.$this->event;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'event' => $this->event,
            'letter_type_id' => $this->letterTypeId,
            'letter_type_name' => $this->letterTypeName,
            'scheduled_at' => $this->scheduledAt,
            'action_url' => route('outgoing-letters.index'),
        ];
    }

    private function title(): string
    {
        return match ($this->event) {
            'deletion_scheduled' => 'Jenis surat akan dihapus',
            'deleted' => 'Jenis surat telah dihapus',
            'restored' => 'Jenis surat tersedia kembali',
            default => 'Pemberitahuan jenis surat',
        };
    }

    private function message(): string
    {
        return match ($this->event) {
            'deletion_scheduled' => sprintf(
                'Jenis surat "%s" dijadwalkan untuk dihapus pada %s. Jenis surat masih dapat digunakan sampai waktu tersebut.',
                $this->letterTypeName,
                $this->scheduledAt ?? '23:59:59 hari ini',
            ),
            'deleted' => sprintf(
                'Jenis surat "%s" telah dihapus dan tidak dapat digunakan untuk membuat surat baru.',
                $this->letterTypeName,
            ),
            'restored' => sprintf(
                'Jenis surat "%s" telah dipulihkan dan dapat digunakan kembali.',
                $this->letterTypeName,
            ),
            default => sprintf('Ada perubahan pada jenis surat "%s".', $this->letterTypeName),
        };
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutgoingLetterStatusHistory>
 */
class OutgoingLetterStatusHistoryFactory extends Factory
{
    protected $model = OutgoingLetterStatusHistory::class;

    public function definition(): array
    {
        return [
            'outgoing_letter_id' => OutgoingLetter::factory(),
            'changed_by' => User::factory(),
            'status' => OutgoingLetterStatus::DRAFT,
            'action' => 'created',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\WithPagination;

/**
 * Project convention for list/table pagination.
 *
 * Every data table with a potentially growing dataset should use this trait
 * and render <x-ui.table-footer :paginator="$items" />.
 */
trait WithStandardTablePagination
{
    use WithPagination;

    public int $perPage = 5;

    public function updatedPerPage(): void
    {
        $this->perPage = max(5, min($this->perPage, 50));
        $this->resetPage();
    }
}

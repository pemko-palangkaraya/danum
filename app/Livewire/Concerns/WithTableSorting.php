<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Standard server-side sorting for DANUM data tables.
 *
 * Components opt in by defining a protected $sortableColumns map where the
 * public sort key maps to a safe database column expression.
 */
trait WithTableSorting
{
    public string $sortBy = '';
    public string $sortDirection = 'asc';

    /** @var array<string, string> */
    protected array $sortableColumns = [];

    public function sort(string $column): void
    {
        if (! array_key_exists($column, $this->sortableColumns)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    protected function applyTableSorting($query, string $defaultColumn = 'created_at', string $defaultDirection = 'desc')
    {
        $column = $this->sortableColumns[$this->sortBy] ?? $this->sortableColumns[$defaultColumn] ?? $defaultColumn;
        $direction = in_array($this->sortDirection, ['asc', 'desc'], true)
            ? $this->sortDirection
            : $defaultDirection;

        return $query->orderBy($column, $direction);
    }

    protected function tableSortIcon(string $column): string
    {
        if ($this->sortBy !== $column) {
            return '↕';
        }

        return $this->sortDirection === 'asc' ? '↑' : '↓';
    }
}

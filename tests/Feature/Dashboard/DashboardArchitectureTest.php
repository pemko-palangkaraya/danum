<?php

declare(strict_types=1);

it('keeps dashboard blade free of business logic and database queries', function (): void {
    $view = file_get_contents(resource_path('views/livewire/pages/dashboard.blade.php'));

    expect($view)
        ->not->toContain('<?php')
        ->not->toContain('@php')
        ->not->toContain('::query(')
        ->not->toContain('DB::')
        ->not->toContain('auth()->user()->tenant');
});

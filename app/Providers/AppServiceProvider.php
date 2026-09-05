<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\OutgoingLetter;
use App\Observers\OutgoingLetterObserver;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\LetterTypeRepositoryInterface;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use App\Repositories\Contracts\OutgoingLetterStatusHistoryRepositoryInterface;
use App\Repositories\Contracts\PositionRepositoryInterface;
use App\Repositories\Contracts\PositionHolderRepositoryInterface;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use App\Repositories\LetterTypeRepository;
use App\Repositories\OutgoingLetterRepository;
use App\Repositories\OutgoingLetterStatusHistoryRepository;
use App\Repositories\PositionRepository;
use App\Repositories\PositionHolderRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(LetterTypeRepositoryInterface::class, LetterTypeRepository::class);
        $this->app->bind(OutgoingLetterRepositoryInterface::class, OutgoingLetterRepository::class);
        $this->app->bind(OutgoingLetterStatusHistoryRepositoryInterface::class, OutgoingLetterStatusHistoryRepository::class);
        $this->app->bind(PositionRepositoryInterface::class, PositionRepository::class);
        $this->app->bind(PositionHolderRepositoryInterface::class, PositionHolderRepository::class);
    }

    public function boot(): void
    {
        OutgoingLetter::observe(OutgoingLetterObserver::class);
    }
}

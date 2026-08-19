<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\LetterTypeRepositoryInterface;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use App\Repositories\TenantRepository;
use App\Repositories\UserRepository;
use App\Repositories\LetterTypeRepository;
use App\Repositories\OutgoingLetterRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TenantRepositoryInterface::class,
            TenantRepository::class,
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class,
        );

        $this->app->bind(
            LetterTypeRepositoryInterface::class,
            LetterTypeRepository::class,
        );

        $this->app->bind(
            OutgoingLetterRepositoryInterface::class,
            OutgoingLetterRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

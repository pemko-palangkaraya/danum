<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\PositionController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PositionRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_position_routes_are_registered(): void
    {
        $this->assertRouteExists(
            'GET',
            'api/positions',
            PositionController::class . '@index'
        );

        $this->assertRouteExists(
            'POST',
            'api/positions',
            PositionController::class . '@store'
        );

        $this->assertRouteExists(
            'GET',
            'api/positions/{position}',
            PositionController::class . '@show'
        );

        $this->assertRouteExists(
            'PUT',
            'api/positions/{position}',
            PositionController::class . '@update'
        );

        $this->assertRouteExists(
            'PATCH',
            'api/positions/{position}',
            PositionController::class . '@update'
        );

        $this->assertRouteExists(
            'DELETE',
            'api/positions/{position}',
            PositionController::class . '@destroy'
        );

        $this->assertRouteExists(
            'POST',
            'api/positions/{id}/restore',
            PositionController::class . '@restore'
        );

        $this->assertRouteExists(
            'POST',
            'api/positions/{position}/holder',
            PositionController::class . '@assignHolder'
        );

        $this->assertRouteExists(
            'POST',
            'api/positions/{position}/holder/end',
            PositionController::class . '@endHolder'
        );

        $this->assertRouteExists(
            'GET',
            'api/positions/{position}/holder',
            PositionController::class . '@activeHolder'
        );

        $this->assertRouteExists(
            'GET',
            'api/positions/{position}/holders',
            PositionController::class . '@holderHistory'
        );
    }

    private function assertRouteExists(
        string $method,
        string $uri,
        string $action
    ): void {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(
                fn($route) =>
                $route->uri() === $uri
                    && in_array($method, $route->methods(), true)
                    && $route->getActionName() === $action
            );

        $this->assertNotNull(
            $route,
            "Route [$method $uri] with action [$action] was not found."
        );
    }
}

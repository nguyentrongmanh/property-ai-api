<?php

namespace App\Providers;

use App\Repositories\Contracts\BuildingRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use App\Repositories\EloquentBuildingRepository;
use App\Repositories\EloquentWorkOrderRepository;
use App\Services\AI\Contracts\WorkOrderClassifierInterface;
use App\Services\AI\GeminiWorkOrderClassifier;
use App\Services\Contracts\PropertyServiceInterface;
use App\Services\Contracts\WorkOrderServiceInterface;
use App\Services\PropertyService;
use App\Services\WorkOrderService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BuildingRepositoryInterface::class, EloquentBuildingRepository::class);
        $this->app->bind(WorkOrderRepositoryInterface::class, EloquentWorkOrderRepository::class);

        $this->app->bind(PropertyServiceInterface::class, PropertyService::class);
        $this->app->bind(WorkOrderServiceInterface::class, WorkOrderService::class);

        $this->app->bind(WorkOrderClassifierInterface::class, function (): GeminiWorkOrderClassifier {
            return new GeminiWorkOrderClassifier(
                apiKey: (string) config('services.gemini.key'),
                model: (string) config('services.gemini.model'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

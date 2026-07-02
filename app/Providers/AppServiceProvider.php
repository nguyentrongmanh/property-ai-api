<?php

namespace App\Providers;

use App\Repositories\Contracts\BuildingRepositoryInterface;
use App\Repositories\EloquentBuildingRepository;
use App\Services\Contracts\PropertyServiceInterface;
use App\Services\PropertyService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BuildingRepositoryInterface::class, EloquentBuildingRepository::class);
        $this->app->bind(PropertyServiceInterface::class, PropertyService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

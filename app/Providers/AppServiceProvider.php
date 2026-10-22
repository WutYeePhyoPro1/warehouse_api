<?php

namespace App\Providers;

use App\Services\Ums\WarehouseUserLifecycle;
use Illuminate\Support\ServiceProvider;
use Pro1\UmsIntegration\Contracts\UserLifecycle;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            UserLifecycle::class,
            WarehouseUserLifecycle::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

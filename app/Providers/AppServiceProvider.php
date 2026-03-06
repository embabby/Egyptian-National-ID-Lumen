<?php

namespace App\Providers;

use App\Services\EgyptianNationalIdService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EgyptianNationalIdService::class, function () {
            return new EgyptianNationalIdService();
        });
    }
}

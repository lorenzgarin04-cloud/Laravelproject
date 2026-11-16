<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Oras ng Pilipinas. Naka-hardcode ang 'UTC' sa config/app.php kaya dito natin
        // pinipilit ang timezone (mababasa mula sa APP_TIMEZONE sa .env, default: Asia/Manila).
        $timezone = config('dg.timezone', 'Asia/Manila');

        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
    }

    public function boot(): void
    {
        //
    }
}

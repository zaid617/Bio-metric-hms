<?php

namespace App\Providers;

use App\Models\PayrollSetting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Note: We intentionally do NOT globally bypass permission checks for any role.
        // Super-admin permissions are editable and should be respected by both @can and middleware.

        // Override the payroll config with values stored in the database so that
        // all config('payroll.*') calls across the app use the admin-managed values.
        try {
            if (Schema::hasTable('payroll_settings')) {
                $settings = PayrollSetting::current();
                config(['payroll' => $settings->toConfigArray()]);
            }
        } catch (Throwable) {
            // Silently fall back to the file-based config during migrations / installs.
        }
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
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
        $pluginClasses = config('plugins.mods');

        foreach ($pluginClasses as $pluginClass) {
            $pluginClass::registerRoutes();
        }

        foreach ($pluginClasses as $pluginClass) {
            new $pluginClass();
        }
    }
}

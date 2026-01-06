<?php

namespace PhpJunior\Glosa;

use Illuminate\Support\ServiceProvider;

class GlosaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'glosa');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/glosa.php' => config_path('glosa.php'),
        ], 'glosa-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/glosa'),
        ], 'glosa-views');
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/glosa.php',
            'glosa'
        );

        if (config('glosa.enable_db_loading', true)) {
            $this->app->extend('translation.loader', function ($service, $app) {
                return new TranslationLoader($app['files'], $app['path.lang']);
            });
        }
    }
}

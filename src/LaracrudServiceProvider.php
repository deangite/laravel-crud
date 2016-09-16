<?php namespace InveenLaracrud;

use Illuminate\Support\ServiceProvider;

class LaracrudServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if (! $this->app->routesAreCached()) {
            require __DIR__.'/routes.php';
        }

        $this->loadViewsFrom(__DIR__.'/inveen', 'inveen');

        $this->publishes([
            __DIR__.'/migrations/' => database_path('migrations')
        ], 'migrations');

        $this->publishes([
            __DIR__.'/models/' => app_path('Models')
        ]);

        $this->publishes([
            __DIR__.'/controllers/' => app_path('Http/Controllers')
        ]);

        $this->publishes([
            __DIR__.'/views/' => resource_path('views')
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            LaraCrudCommand::class
        ]);
    }

}
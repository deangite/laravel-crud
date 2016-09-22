<?php

namespace Deangite\LaravelCrud;

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
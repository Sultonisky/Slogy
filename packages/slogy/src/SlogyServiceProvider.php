<?php

namespace Sultonisky\Slogy;
use Illuminate\Support\ServiceProvider;

class SlogyServiceProvider extends ServiceProvider
{
    public function register() {
        
    }
    public function boot(): void {
        // config publish
        $this->publishes([
            __DIR__.'/../config/slogy.php'
                => config_path('slogy.php'),
        ], 'slogy-config');

        // migrations publish (optional)
        $this->publishes([
            __DIR__.'/../database/migrations'
                => database_path('migrations'),
        ], 'slogy-migrations');

        // auto load migrations
        $this->LoadMigrationsFrom([
            __DIR__.'/../database/migrations'
        ]);
    }
}


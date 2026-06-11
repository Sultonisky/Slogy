<?php

namespace Sultonisky\Slogy;
use Illuminate\Support\ServiceProvider;
use Sultonisky\Slogy\Commands\CleanActivityLogCommand;
use Sultonisky\Slogy\Commands\VerifyLogChainCommand;

class SlogyServiceProvider extends ServiceProvider
{
    public function register() {
        $this->commands([
            CleanActivityLogCommand::class,
            VerifyLogChainCommand::class,
        ]);
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
        $this->loadMigrationsFrom([
            __DIR__.'/../database/migrations'
        ]);
    }
}


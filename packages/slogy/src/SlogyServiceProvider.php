<?php

namespace Sultonisky\Slogy;
use Illuminate\Support\ServiceProvider;

class SlogyServiceProvider extends ServiceProvider
{
    public function register() {
        
    }
    public function boot(): void {
        $this->publishes([
            __DIR__.'/../config/slogy.php'
               => config_path('slogy.php'),
        ], 'slogy-config');

    }
}


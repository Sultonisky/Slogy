<?php

namespace Sultonisky\Slogy\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Sultonisky\Slogy\SlogyServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set default config
        config()->set('slogy', [
            'events' => [
                'created' => true,
                'updated' => true,
                'deleted' => true,
            ],
            'ignored_attributes' => [
                'password',
                'remember_token',
                'updated_at',
                'created_at',
                'deleted_at',
                'id', // Also ignore ID since it's auto-generated
            ],
            'user_model' => 'App\Models\User',
            'log_retention_days' => 60,
        ]);

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            SlogyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'database' => 'db_slogy',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase()
    {
        // Clean up tables first
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('products');
        Schema::dropIfExists('users');

        // Create dummy users table for testing first (because of foreign key)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Run package migrations
        $migration = include __DIR__ . '/../database/migrations/2026_05_17_000000_create_activity_logs_table.php';
        $migration->up();

        // Create dummy products table for testing
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
    }
}

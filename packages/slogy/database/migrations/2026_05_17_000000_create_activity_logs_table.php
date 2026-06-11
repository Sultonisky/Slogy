<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // created, updated, deleted
            $table->string('action');

            // Human readable description
            $table->text('description')->nullable();

            // Product, User, etc.
            $table->string('model');
            
            $table->unsignedBigInteger('model_id')->nullable();
            
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            
            // Cryptographic chain columns
            $table->string('previous_hash')->nullable();
            $table->string('current_hash');
            $table->boolean('is_genesis')->default(false);
            
            $table->timestamps();

            $table->index(['model', 'model_id']);
            $table->index('action');
            $table->index('user_id');
            $table->index('current_hash');
            $table->index('is_genesis');
        });
    }

    public function down(): void {
        Schema::dropIfExists('activity_logs');
    }

};
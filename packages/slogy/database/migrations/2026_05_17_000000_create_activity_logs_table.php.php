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

            // Product, User, etc.
            $table->string('model');
            
            $table->unsignedBigInteger('model_id')->nullable();
            
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();
            
            /* 
            TODO
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            */
        });
    }

    public function down(): void {
        Schema:dropIfExists('activity_logs');
    }

};
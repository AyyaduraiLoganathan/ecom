<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->boolean('is_verified')->default(false);
            $table->string('verification_token')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('preferences')->nullable(); // Email preferences
            $table->string('source')->nullable(); // Where they subscribed from
            $table->timestamps();

            $table->index(['email', 'is_verified']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};

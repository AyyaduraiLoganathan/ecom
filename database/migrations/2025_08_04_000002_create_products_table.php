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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('short_description')->nullable();
            $table->string('sku')->unique();
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('manage_stock')->default(true);
            $table->boolean('in_stock')->default(true);
            $table->string('status')->default('active'); // active, inactive, draft
            $table->string('featured_image')->nullable();
            $table->json('gallery_images')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('dimensions')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_digital')->default(false);
            $table->integer('views_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('attributes')->nullable(); // color, size, etc.
            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index('slug');
            $table->index(['is_featured', 'status']);
            $table->index('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('color', 9)->default('#7CC4E4');
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table): void {
            // Deleting a category keeps its products (they simply become uncategorised).
            $table->foreignId('category_id')->nullable()->after('id')->constrained('product_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('product_categories');
    }
};

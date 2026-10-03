<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seafood catalogue: category -> species -> cut -> product.
 *
 * - species: the fish itself (common / scientific / Japanese name, origin, sashimi suitability)
 * - cuts: the body part or cut (loin, saku, akami...); cut_species says which cuts a species is
 *   offered in and whether that cut is really sold as a product (available_as_product)
 * - processing_methods: freshness / freezing / skin / bone / trim / treatment, joined to products
 * - products get the technical fields (product code, freezing, temperature, usage, packaging...)
 *
 * Deleting a category, species or cut keeps the rows that point to it (they become unassigned).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table): void {
            $table->string('image_url', 500)->nullable()->after('color');
            $table->string('image_credit', 500)->nullable()->after('image_url');
            $table->json('meta_title')->nullable()->after('description');
            $table->json('meta_description')->nullable()->after('meta_title');
        });

        Schema::create('species', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('slug', 100)->unique();
            $table->json('common_name');
            $table->string('scientific_name', 120)->nullable();
            $table->string('japanese_name', 120)->nullable();
            $table->json('short_description')->nullable();
            $table->string('origin', 255)->nullable();
            $table->text('habitat')->nullable();
            $table->boolean('is_sashimi_suitable')->default(false);
            $table->string('sashimi_grade', 40)->nullable();
            $table->text('sustainability_info')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('image_credit', 500)->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->json('translation_status')->nullable();
            $table->boolean('is_sample')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cuts', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('body_region', 120)->nullable();
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cut_species', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('species_id')->constrained('species')->cascadeOnDelete();
            $table->foreignId('cut_id')->constrained('cuts')->cascadeOnDelete();
            $table->boolean('available_as_product')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['species_id', 'cut_id']);
        });

        Schema::create('processing_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->json('name');
            $table->string('type', 30);
            $table->json('description')->nullable();
            $table->string('temperature', 40)->nullable();
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('species_id')->nullable()->after('category_id')->constrained('species')->nullOnDelete();
            $table->foreignId('cut_id')->nullable()->after('species_id')->constrained('cuts')->nullOnDelete();
            $table->string('product_code', 40)->nullable()->unique()->after('cut_id');
            $table->string('body_part', 120)->nullable();
            $table->string('cut_type', 80)->nullable();
            $table->string('freezing_method', 20)->nullable();
            $table->string('temperature', 40)->nullable();
            $table->string('sashimi_grade', 40)->nullable();
            $table->string('color', 120)->nullable();
            $table->string('texture', 160)->nullable();
            $table->string('flavor_profile', 160)->nullable();
            $table->string('typical_usage', 255)->nullable();
            $table->string('packaging', 255)->nullable();
            $table->string('shelf_life', 120)->nullable();
            $table->string('origin', 255)->nullable();
            $table->string('certification', 255)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('image_credit', 500)->nullable();
            $table->boolean('image_is_reference')->default(false);
            $table->json('gallery_urls')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_sample')->default(false);
        });

        Schema::create('processing_method_product', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('processing_method_id')->constrained('processing_methods')->cascadeOnDelete();
            $table->unique(['product_id', 'processing_method_id'], 'product_method_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_method_product');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('species_id');
            $table->dropConstrainedForeignId('cut_id');
            $table->dropUnique(['product_code']);
            $table->dropColumn(['product_code', 'body_part', 'cut_type', 'freezing_method', 'temperature', 'sashimi_grade', 'color', 'texture', 'flavor_profile', 'typical_usage', 'packaging', 'shelf_life', 'origin', 'certification', 'image_url', 'image_credit', 'image_is_reference', 'gallery_urls', 'is_featured', 'is_sample']);
        });

        Schema::dropIfExists('processing_methods');
        Schema::dropIfExists('cut_species');
        Schema::dropIfExists('cuts');
        Schema::dropIfExists('species');

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropColumn(['image_url', 'image_credit', 'meta_title', 'meta_description']);
        });
    }
};

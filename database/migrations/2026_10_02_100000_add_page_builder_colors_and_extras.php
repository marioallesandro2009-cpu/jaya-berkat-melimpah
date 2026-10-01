<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Page builder: which page a block belongs to, and the look of blocks added in the admin.
        Schema::table('page_sections', function (Blueprint $table): void {
            $table->string('page', 10)->default('home')->after('key');
            $table->string('layout', 20)->nullable()->after('page');
            $table->string('background', 10)->nullable()->after('layout');
            $table->boolean('is_custom')->default(false)->after('background');
        });

        // Blocks that already existed: the company page's blocks belong to the company page.
        DB::table('page_sections')->where('key', 'like', 'company\_%')->update(['page' => 'company']);

        // Brand colours (hex per token) and the hero slideshow speed.
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->json('colors')->nullable();
            $table->unsignedSmallInteger('hero_slide_duration')->default(6);
        });

        // Product detail pages.
        Schema::table('products', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique()->after('id');
            $table->string('detail_status', 12)->default('draft')->after('slug');
            $table->json('intro')->nullable();
            $table->json('content')->nullable();
            $table->json('specs')->nullable();
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
        });

        Schema::create('hero_slides', function (Blueprint $table): void {
            $table->id();
            $table->json('label')->nullable();
            $table->json('image_alt')->nullable();
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('trust_logos', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->json('question');
            $table->json('answer')->nullable();
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('kind')->nullable();
            $table->text('address')->nullable();
            $table->string('maps_url')->nullable();
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['locations', 'faqs', 'trust_logos', 'hero_slides'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['slug', 'detail_status', 'intro', 'content', 'specs', 'seo_title', 'seo_description']);
        });
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['colors', 'hero_slide_duration']);
        });
        Schema::table('page_sections', function (Blueprint $table): void {
            $table->dropColumn(['page', 'layout', 'background', 'is_custom']);
        });
    }
};

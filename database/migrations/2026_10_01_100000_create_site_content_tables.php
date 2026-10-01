<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Singleton: company data, contact details, default texts and SEO.
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name')->default('PT Jaya Berkat Melimpah');
            $table->string('legal_name')->nullable();
            $table->json('company_description')->nullable();
            $table->json('footer_tagline')->nullable();
            $table->string('whatsapp_number', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->json('cta_label')->nullable();
            $table->json('ui_texts')->nullable();
            $table->json('contact_recipients')->nullable();
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->json('translation_status')->nullable();
            $table->json('translation_glossary')->nullable();
            $table->timestamps();
        });

        // One row per block of page copy (hero, statement, origin, ...): the key is fixed in code.
        Schema::create('page_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 40)->unique();
            $table->json('eyebrow')->nullable();
            $table->json('title')->nullable();
            $table->json('body')->nullable();
            $table->json('body_extra')->nullable();
            $table->json('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->json('image_alt')->nullable();
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('stats', function (Blueprint $table): void {
            $table->id();
            $table->decimal('value', 12, 2)->nullable();
            $table->string('suffix', 12)->nullable();
            $table->json('text_value')->nullable();
            $table->json('label')->nullable();
            $table->boolean('is_sample')->default(false);
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('image_alt')->nullable();
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('chain_steps', function (Blueprint $table): void {
            $table->id();
            $table->json('title');
            $table->json('body')->nullable();
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Short text blocks grouped by where they appear: quality | sustainability | value | checkpoint.
        Schema::create('features', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 20)->index();
            $table->json('title');
            $table->json('body')->nullable();
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('timeline_items', function (Blueprint $table): void {
            $table->id();
            $table->json('year_label');
            $table->json('title');
            $table->json('body')->nullable();
            $table->boolean('is_sample')->default(false);
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('leaders', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('role');
            $table->boolean('is_sample')->default(false);
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('certifications', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('status_label');
            $table->boolean('is_sample')->default(false);
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('phone', 40)->nullable();
            $table->string('company', 150)->nullable();
            $table->string('country', 100)->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // Copy of the product name at submission time, so history stays readable if the product is removed.
            $table->string('product_title')->nullable();
            $table->string('volume', 120)->nullable();
            $table->text('message')->nullable();
            $table->string('locale', 5);
            $table->string('status', 10)->default('new')->index();
            $table->boolean('email_failed')->default(false);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        foreach (['contact_messages', 'certifications', 'leaders', 'timeline_items', 'features', 'chain_steps', 'products', 'stats', 'page_sections', 'site_settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

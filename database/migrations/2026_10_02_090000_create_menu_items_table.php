<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            // header | footer_explore | footer_company
            $table->string('location', 20)->index();
            $table->json('label')->nullable();
            // section (an anchor on the home page) | page (company, news, home; optional "#anchor") | url
            $table->string('type', 10)->default('section');
            $table->string('target', 255);
            $table->boolean('new_tab')->default(false);
            // Header only: drawn as the outlined call-to-action button.
            $table->boolean('is_button')->default(false);
            $table->json('translation_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};

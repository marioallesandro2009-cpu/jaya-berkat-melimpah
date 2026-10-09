<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Home hero: the animated 3D sea and boat, or the photograph / slide show. Switchable from the admin.
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('hero_mode', 10)->default('3d');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn('hero_mode');
        });
    }
};

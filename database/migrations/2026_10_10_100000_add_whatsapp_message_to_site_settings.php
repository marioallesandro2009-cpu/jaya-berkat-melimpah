<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The text that is already typed in when someone taps the floating WhatsApp button (empty = a default greeting).
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('whatsapp_message', 300)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn('whatsapp_message');
        });
    }
};

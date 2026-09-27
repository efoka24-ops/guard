<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** T061bis — traçabilité de l'alerte vers son site, même patron que terminal_id (ENDPOINT). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alertes', function (Blueprint $table) {
            $table->uuid('site_web_id')->nullable()->after('terminal_id');
        });
    }

    public function down(): void
    {
        Schema::table('alertes', function (Blueprint $table) {
            $table->dropColumn('site_web_id');
        });
    }
};

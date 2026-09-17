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
        Schema::table('organisations', function (Blueprint $table) {
            // Utilisé par POST /terminals/register (contracts/endpoint-sync-api.yaml)
            // pour rattacher un terminal à son organisation sans exposer son UUID.
            $table->string('token_enrolement', 64)->unique()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->dropColumn('token_enrolement');
        });
    }
};

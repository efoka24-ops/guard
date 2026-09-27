<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige une faille de cloisonnement multi-tenant (durcissement T053) :
 * identifiant_appareil était unique globalement, ce qui permettait à une
 * organisation de détourner le terminal d'une autre en réutilisant son
 * device_hash (TerminalController::register faisait un updateOrCreate sur
 * cette seule colonne). L'unicité doit être portée par (organisation_id,
 * identifiant_appareil) : un même device_hash peut légitimement apparaître
 * sous deux organisations différentes sans collision.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terminaux', function (Blueprint $table) {
            $table->dropUnique(['identifiant_appareil']);
            $table->unique(['organisation_id', 'identifiant_appareil']);
        });
    }

    public function down(): void
    {
        Schema::table('terminaux', function (Blueprint $table) {
            $table->dropUnique(['organisation_id', 'identifiant_appareil']);
            $table->unique('identifiant_appareil');
        });
    }
};

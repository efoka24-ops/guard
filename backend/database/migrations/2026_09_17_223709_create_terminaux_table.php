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
        Schema::create('terminaux', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organisation_id')->constrained('organisations')->cascadeOnDelete();
            $table->enum('plateforme', ['android', 'windows']);
            // Hash salé de l'identifiant appareil — jamais l'IMEI/GUID brut (Principe IV).
            $table->string('identifiant_appareil')->unique();
            $table->string('version_app');
            $table->string('version_signatures')->nullable();
            $table->timestamp('derniere_synchro_le')->nullable();
            $table->unsignedTinyInteger('score_risque_comportemental')->nullable();
            $table->enum('statut', ['actif', 'hors_ligne_prolonge', 'desinstalle'])->default('actif');
            $table->timestamps();

            $table->index(['organisation_id', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terminaux');
    }
};

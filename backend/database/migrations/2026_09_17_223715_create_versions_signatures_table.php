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
        Schema::create('versions_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_version')->unique(); // ex. "2026.09.17"
            $table->unsignedInteger('taille_delta_ko'); // doit rester < 50 (contrainte SC-010)
            $table->json('hashes_ajoutes'); // liste de hash SHA-256 (delta depuis la version precedente)
            $table->json('regles_yara_ajoutees'); // liste de regles YARA (texte brut)
            $table->string('checksum_delta');
            $table->timestamp('publie_le');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('versions_signatures');
    }
};

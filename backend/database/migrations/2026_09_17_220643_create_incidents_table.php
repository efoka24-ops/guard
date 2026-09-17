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
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organisation_id')->constrained('organisations')->cascadeOnDelete();
            $table->string('titre');
            $table->enum('type', [
                'malware', 'piratage_social', 'defacement',
                'fuite_donnees', 'usurpation', 'autre',
            ]);
            $table->enum('statut', ['ouvert', 'en_cours', 'cloture'])->default('ouvert');
            $table->boolean('dossier_legal_genere')->default(false);
            $table->string('dossier_legal_url')->nullable();
            $table->enum('pays_autorite_cible', ['ANTIC_CM', 'RCA_RW', 'ARTCI_CI', 'ADIE_SN'])->nullable();
            $table->timestamp('ouvert_le')->useCurrent();
            $table->timestamp('cloture_le')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};

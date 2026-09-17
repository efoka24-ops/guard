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
        Schema::create('elements_quarantaine', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('evenement_scan_id')->unique()->constrained('evenements_scan')->cascadeOnDelete();
            // chemin_origine (data-model.md) reste local au terminal, jamais remonté (Principe IV).
            $table->enum('raison', ['hash_connu', 'yara', 'score_ia', 'comportemental']);
            $table->enum('statut', ['en_quarantaine', 'restaure', 'supprime', 'envoye_analyse'])
                ->default('en_quarantaine');
            $table->foreignId('resolu_par_utilisateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolu_le')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elements_quarantaine');
    }
};

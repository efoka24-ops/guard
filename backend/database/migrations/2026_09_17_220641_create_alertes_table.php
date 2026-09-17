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
        Schema::create('alertes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organisation_id')->constrained('organisations')->cascadeOnDelete();
            $table->enum('module_source', ['ENDPOINT', 'SOCIAL', 'WEB', 'ID', 'CODE']);
            // Pas de contrainte FK stricte sur terminal_id/incident_id : les tables
            // `terminaux` (Phase 3, T024) et le rattachement d'incident sont ajoutés
            // par des increments ulterieurs sans bloquer l'ordre des migrations.
            $table->uuid('terminal_id')->nullable();
            $table->uuid('incident_id')->nullable();
            $table->enum('niveau_criticite', ['info', 'faible', 'moyen', 'eleve', 'critique']);
            $table->string('titre');
            $table->text('description_technique')->nullable();
            $table->enum('statut', ['nouvelle', 'accusee_reception', 'assignee', 'resolue', 'ignoree'])
                ->default('nouvelle');
            $table->timestamp('sla_echeance_le');
            $table->foreignId('assignee_utilisateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organisation_id', 'statut']);
            $table->index('sla_echeance_le');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alertes');
    }
};

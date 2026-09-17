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
        Schema::create('evenements_scan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('terminal_id')->constrained('terminaux')->cascadeOnDelete();
            // UUID généré côté client (terminal) — garantit l'idempotence lors des
            // resynchronisations après coupure réseau (contrat endpoint-sync-api.yaml).
            $table->uuid('client_event_id')->unique();
            $table->enum('declencheur', [
                'usb', 'apk_install', 'fichier_local', 'sms_lien', 'email_piece_jointe',
            ]);
            $table->char('hash_sha256', 64);
            // 3_ia hors périmètre de ce premier incrément (cf. research.md §3/§5).
            $table->enum('niveau_max_atteint', ['1_hash', '2_yara', '3_ia']);
            $table->string('regle_yara_correspondante')->nullable();
            $table->unsignedTinyInteger('score_confiance_ia')->nullable();
            $table->enum('classification', ['clean', 'suspect', 'probable_malware', 'malware_confirme']);
            $table->enum('action_prise', [
                'aucune', 'surveillance', 'quarantaine_proposee', 'quarantaine_auto',
            ]);
            $table->timestamp('survenu_le'); // horodatage local terminal, peut précéder la synchro
            $table->timestamp('synchronise_le')->useCurrent();
            $table->timestamps();

            $table->index(['terminal_id', 'survenu_le']);
            $table->index('classification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evenements_scan');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** T060 — GUARD WEB (module #2), cf. specs/001-guard-platform/guard-web-cadrage.md */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites_web_surveilles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organisation_id')->constrained('organisations')->cascadeOnDelete();
            $table->string('url');
            $table->string('hash_page_accueil')->nullable();
            $table->timestamp('derniere_verification_le')->nullable();
            $table->enum('statut_ssl', ['valide', 'expire_bientot', 'expire', 'absent'])->default('absent');
            $table->date('ssl_expiration_le')->nullable();
            $table->enum('statut', ['actif', 'en_pause', 'supprime'])->default('actif');
            $table->timestamps();

            // Unicité composite (organisation_id, url) et non globale : deux
            // organisations distinctes doivent pouvoir surveiller la même URL
            // sans collision (leçon du correctif T053 sur `terminaux`, cf.
            // guard-web-cadrage.md §3).
            $table->unique(['organisation_id', 'url']);
            $table->index(['organisation_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites_web_surveilles');
    }
};

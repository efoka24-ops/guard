<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** T061 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenements_web', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_web_id')->constrained('sites_web_surveilles')->cascadeOnDelete();
            $table->enum('type', [
                'defacement',
                'indisponible',
                'en_tete_manquant',
                'cookie_non_securise',
                'ssl_expiration_proche',
                'ssl_expire',
            ]);
            $table->json('detail')->nullable();
            $table->timestamp('detecte_le');
            $table->timestamps();

            $table->index(['site_web_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements_web');
    }
};

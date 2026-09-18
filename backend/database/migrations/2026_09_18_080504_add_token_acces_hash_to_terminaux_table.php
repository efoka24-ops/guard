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
        Schema::table('terminaux', function (Blueprint $table) {
            // Corrige M3 de la revue backend : /scan-events et /signatures/delta
            // n'authentifiaient aucun appelant. Un token d'accès est désormais
            // délivré en clair une seule fois à POST /terminals/register et
            // seul son hash est stocké (comme un mot de passe).
            $table->string('token_acces_hash')->nullable()->after('identifiant_appareil');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('terminaux', function (Blueprint $table) {
            $table->dropColumn('token_acces_hash');
        });
    }
};

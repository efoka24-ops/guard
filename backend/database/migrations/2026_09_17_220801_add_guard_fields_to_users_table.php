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
        Schema::table('users', function (Blueprint $table) {
            // Entité "Utilisateur" de data-model.md — table physique `users` conservée
            // (convention Laravel/Sanctum) plutôt que `utilisateurs`, pour rester
            // compatible avec Auth/Sanctum sans reconfiguration du guard.
            $table->foreignUuid('organisation_id')->nullable()->after('id')
                ->constrained('organisations')->nullOnDelete();
            $table->enum('role', [
                'dirigeant', 'it_manager', 'developpeur',
                'community_manager', 'admin_guard', 'auditeur',
            ])->default('dirigeant')->after('email');
            $table->boolean('mfa_active')->default(false)->after('role');
            $table->string('langue', 2)->default('fr')->after('mfa_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organisation_id']);
            $table->dropColumn(['organisation_id', 'role', 'mfa_active', 'langue']);
        });
    }
};

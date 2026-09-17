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
        Schema::create('organisations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('pays', 2); // CM, RW, SN, CI, ... — cf. data-model.md
            $table->json('modules_actifs')->default('[]');
            $table->unsignedTinyInteger('score_securite_global')->default(100);
            $table->boolean('mode_msp')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organisations');
    }
};

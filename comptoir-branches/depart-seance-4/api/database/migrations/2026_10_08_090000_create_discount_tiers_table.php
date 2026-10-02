<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->unsignedBigInteger('min_subtotal_cents')->unique();
            // Taux en points de base : 750 = 7,50 %. Entier pour éviter les flottants sur de l'argent.
            $table->unsignedSmallInteger('rate_bp');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_tiers');
    }
};

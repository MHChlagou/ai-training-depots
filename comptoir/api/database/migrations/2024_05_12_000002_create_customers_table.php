<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->nullable();
            $table->string('email', 190)->unique();
            $table->string('password')->nullable();
            $table->string('company', 190)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('type', 20)->default('pro');
            $table->char('country', 2)->default('FR');
            $table->string('vat_number', 32)->nullable();
            $table->boolean('is_admin')->default(false);
            $table->unsignedBigInteger('credit_limit')->default(0);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->string('logo_path', 255)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

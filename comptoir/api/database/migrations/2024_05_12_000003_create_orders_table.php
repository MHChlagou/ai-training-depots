<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->string('reference', 20)->unique();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->timestamp('placed_at')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('discount_code', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

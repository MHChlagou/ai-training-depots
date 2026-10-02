<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Couvre le filtre (customer_id) ET le tri (placed_at desc) de GET /api/orders?customer_id=...
            $table->index(['customer_id', 'placed_at'], 'orders_customer_id_placed_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_customer_id_placed_at_index');
        });
    }
};

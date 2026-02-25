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
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('order_date');
            $table->foreignId('payment_method_id')->constrained('payment_accounts')->onDelete('cascade');
            $table->foreignId('shipping_address_id')->constrained('addresses')->onDelete('cascade');
            $table->foreignId('shipping_method_id')->constrained('shipping_methods')->onDelete('cascade');
            $table->decimal('order_total', 12, 2);
            $table->foreignId('order_status_id')->constrained('order_statuses')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_orders');
    }
};

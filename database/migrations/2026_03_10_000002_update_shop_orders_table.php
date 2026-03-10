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
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->after('user_id')->constrained('promo_codes')->onDelete('set null');
            $table->decimal('subtotal', 12, 2)->after('shipping_method_id')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('shipping_fee', 12, 2)->default(0)->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn(['promo_code_id', 'subtotal', 'discount_amount', 'shipping_fee']);
        });
    }
};

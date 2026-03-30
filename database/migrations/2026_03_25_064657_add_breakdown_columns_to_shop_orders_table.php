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
            $table->decimal('subtotal', 12, 2)->default(0)->after('shipping_method_id');
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
            $table->dropColumn(['subtotal', 'discount_amount', 'shipping_fee']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->foreignId('payment_status_id')->nullable()->after('order_status_id')->constrained('payment_statuses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropForeign(['payment_status_id']);
            $table->dropColumn('payment_status_id');
        });
    }
};

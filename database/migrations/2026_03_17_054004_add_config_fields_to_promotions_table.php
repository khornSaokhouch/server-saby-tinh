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
        Schema::table('promotions', function (Blueprint $table) {
            $table->integer('priority')->default(0)->after('status');
            $table->enum('event_type', ['promotion', 'offer', 'seasonal', 'global-event'])->default('promotion')->after('priority');
            $table->enum('discount_type', ['percentage', 'fixed', 'none'])->default('none')->after('event_type');
            $table->decimal('discount_value', 10, 2)->default(0)->after('discount_type');
            $table->json('products')->nullable()->after('discount_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['priority', 'event_type', 'discount_type', 'discount_value', 'products']);
        });
    }
};

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
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'event_type',
                'discount_type',
                'discount_value',
                'products',
                'categories',
                'priority'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->enum('event_type', ['promotion', 'offer', 'seasonal', 'global-event'])->default('promotion');
            $table->enum('discount_type', ['percentage', 'fixed', 'none'])->default('none');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->json('products')->nullable();
            $table->json('categories')->nullable();
            $table->integer('priority')->default(0);
        });
    }
};

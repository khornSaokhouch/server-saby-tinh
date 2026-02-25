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
        Schema::table('order_history', function (Blueprint $table) {
            $table->string('status_update')->nullable()->after('order_id');
            $table->text('description')->nullable()->after('status_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_history', function (Blueprint $table) {
            $table->dropColumn(['status_update', 'description']);
        });
    }
};

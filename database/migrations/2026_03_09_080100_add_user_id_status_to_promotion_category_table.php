<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_category', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('category_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('status')->default(1)->after('user_id'); // 1=active, 0=inactive
        });
    }

    public function down(): void
    {
        Schema::table('promotion_category', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'status']);
        });
    }
};

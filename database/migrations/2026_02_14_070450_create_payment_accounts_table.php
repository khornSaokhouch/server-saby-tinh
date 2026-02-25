<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('account_name');
            $table->string('account_id');
            $table->string('type_value');
            $table->string('account_city')->nullable();
            $table->string('currency')->comment('KHR or USD');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'account_id']); // optional uniqueness per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_accounts');
    }
};

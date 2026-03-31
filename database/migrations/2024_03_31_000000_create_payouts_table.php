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
        Schema::create('payouts', function (Blueprint $col) {
            $col->id();
            
            $col->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $col->foreignId('store_id')->constrained('stores')->onDelete('cascade');

            $col->decimal('amount', 15, 2);
            $col->string('currency')->default('USD')->comment('KHR or USD');

            $col->foreignId('payment_status_id')->constrained('payment_statuses');

            $col->timestamp('paid_at')->nullable();
            $col->string('transaction_reference')->nullable();

            $col->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};

<?php
use App\Models\Invoice;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$invoices = Invoice::with('order.orderLines.productItemVariant.productItem.product.store')->get();

foreach ($invoices as $inv) {
    $store = $inv->order->orderLines->first()->productItemVariant->productItem->product->store ?? null;
    echo "Invoice: {$inv->invoice_number} | Status ID: {$inv->payment_status_id} | Store ID: " . ($store->id ?? 'N/A') . " | Store Name: " . ($store->name ?? 'N/A') . PHP_EOL;
}

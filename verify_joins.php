<?php
use App\Models\Invoice;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$storeId = 1;
$status = 2;

$query = Invoice::query()
    ->select('invoices.*')
    ->with(['order.user', 'order.orderLines.productItemVariant.productItem.product.store', 'order.userPayments.paymentMethod', 'paymentStatus'])
    ->latest('invoices.created_at');

$query->where('invoices.payment_status_id', $status);

$query->join('shop_orders', 'invoices.order_id', '=', 'shop_orders.id')
    ->join('order_lines', 'shop_orders.id', '=', 'order_lines.order_id')
    ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
    ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
    ->join('products', 'product_items.product_id', '=', 'products.id')
    ->where('products.store_id', $storeId)
    ->distinct();

$sql = $query->toSql();
echo "SQL: $sql" . PHP_EOL;

$invoices = $query->get();
echo "Count: " . $invoices->count() . PHP_EOL;

foreach ($invoices as $inv) {
    echo "Invoice ID: {$inv->id} | Number: {$inv->invoice_number} | Store Name: " . ($inv->order->orderLines->first()->productItemVariant->productItem->product->store->name ?? 'N/A') . PHP_EOL;
}

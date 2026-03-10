<?php
use App\Models\Invoice;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$storeId = 1;
$status = 2;

$query = Invoice::with(['order.user', 'order.orderLines.productItemVariant.productItem.product.store', 'order.userPayments.paymentMethod', 'paymentStatus'])
    ->where('payment_status_id', $status)
    ->whereHas('order.orderLines.productItemVariant.productItem.product', function($q) use ($storeId) {
        $q->where('store_id', $storeId);
    });

$sql = $query->toSql();
$bindings = $query->getBindings();
echo "SQL: $sql" . PHP_EOL;
echo "Bindings: " . json_encode($bindings) . PHP_EOL;

$invoices = $query->get();
echo "Count: " . $invoices->count() . PHP_EOL;

foreach ($invoices as $inv) {
    echo "Invoice ID: {$inv->id} | Number: {$inv->invoice_number}" . PHP_EOL;
}

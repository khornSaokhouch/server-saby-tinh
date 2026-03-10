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

$invoices = $query->paginate(15);

echo json_encode($invoices, JSON_PRETTY_PRINT);

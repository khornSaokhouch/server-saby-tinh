<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PromoCode;

$id = 4;
$promo = PromoCode::find($id);

if ($promo) {
    echo "Promo Code ID: " . $promo->id . "\n";
    echo "Code: " . $promo->code . "\n";
    echo "Status: " . ($promo->status ? 'Active' : 'Inactive') . "\n";
    echo "Start Date: " . ($promo->start_date ? $promo->start_date->toDateTimeString() : 'N/A') . "\n";
    echo "End Date: " . ($promo->end_date ? $promo->end_date->toDateTimeString() : 'N/A') . "\n";
    echo "Usage Limit: " . $promo->usage_limit . "\n";
    echo "Usage Count: " . $promo->usage_count . "\n";
    echo "Min Order: " . $promo->min_order_amount . "\n";
    echo "Now (Server Time): " . now()->toDateTimeString() . "\n";
} else {
    echo "Promo Code ID $id not found.\n";
}

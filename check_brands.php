<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Brand;

$countWithImg = Brand::whereNotNull('brand_image')->where('brand_image', '!=', '')->count();
$total = Brand::count();

file_put_contents('brand_check.txt', "Brands with image: $countWithImg / $total\n");
if ($total > 0) {
    $first = Brand::first();
    file_put_contents('brand_check.txt', "First brand image: " . $first->brand_image . "\n", FILE_APPEND);
}

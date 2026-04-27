<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Brand;

$img = 'https://images.unsplash.com/photo-1599305096181-01e5d7a3f157?q=80&w=1000';
$count = Brand::count();
Brand::query()->update(['brand_image' => $img]);

echo "Updated $count brands with image: $img\n";

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Brand;
use Illuminate\Support\Facades\Route;

$request = Illuminate\Http\Request::create('/api/brands', 'GET');
$response = Route::dispatch($request);

file_put_contents('api_response.json', $response->getContent());
echo "API response saved to api_response.json\n";

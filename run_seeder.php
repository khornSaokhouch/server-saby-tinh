<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Database\Seeders\CatalogSeeder;

try {
    echo "Starting seeder...\n";
    $seeder = new CatalogSeeder();
    echo "Running seeder logic...\n";
    $seeder->run();
    echo "Seeding completed successfully!\n";
} catch (\Throwable $e) {
    echo "Seeding failed: " . $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentStatus;

class PaymentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['id' => 1, 'status' => 'Pending'],
            ['id' => 2, 'status' => 'Success'],
            ['id' => 3, 'status' => 'Failed'],
        ];

        foreach ($statuses as $status) {
            PaymentStatus::updateOrCreate(['id' => $status['id']], $status);
        }
    }
}

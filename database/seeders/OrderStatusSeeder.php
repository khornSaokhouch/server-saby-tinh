<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrderStatus;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['id' => 1, 'status' => 'Pending'],
            ['id' => 2, 'status' => 'Processing'],
            ['id' => 3, 'status' => 'Shipped'],
            ['id' => 4, 'status' => 'Delivered'],
            ['id' => 5, 'status' => 'Cancelled'],
        ];

        // Clear existing statuses to avoid duplicates if ID 19 exists etc.
        // But be careful if there are many orders. 
        // Since only ID 1 is used, we can truncate if safe or just update.
        
        foreach ($statuses as $status) {
            OrderStatus::updateOrCreate(['id' => $status['id']], ['status' => $status['status']]);
        }

        // Remove any other IDs that were not in our clean list
        OrderStatus::whereNotIn('id', array_column($statuses, 'id'))->delete();
    }
}

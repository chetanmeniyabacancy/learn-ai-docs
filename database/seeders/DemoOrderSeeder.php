<?php

namespace Database\Seeders;

use App\Models\DemoOrder;
use Illuminate\Database\Seeder;

/**
 * Fake orders for the tool-calling playground. Claude never sees this table —
 * it only sees whatever the tool functions choose to return.
 */
class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            ['ORD-1043', 'Priya Shah', 'priya@example.com', 'shipped', 129.00, '2026-08-02', '2026-08-14', 'DHL Express'],
            ['ORD-1044', 'Priya Shah', 'priya@example.com', 'delivered', 42.50, '2026-07-11', '2026-07-15', 'Royal Mail'],
            ['ORD-1077', 'Marcus Bell', 'marcus@example.com', 'processing', 310.75, '2026-08-09', '2026-08-19', null],
            ['ORD-1099', 'Aiko Tanaka', 'aiko@example.com', 'awaiting_payment', 89.99, '2026-08-11', null, null],
            ['ORD-1102', 'Marcus Bell', 'marcus@example.com', 'cancelled', 55.00, '2026-08-05', null, null],
            ['ORD-1110', 'Sofia Rossi', 'sofia@example.com', 'delivered', 245.00, '2026-07-28', '2026-08-01', 'UPS'],
        ];

        foreach ($orders as [$ref, $name, $email, $status, $total, $placed, $expected, $carrier]) {
            DemoOrder::updateOrCreate(['reference' => $ref], [
                'customer_name' => $name,
                'customer_email' => $email,
                'status' => $status,
                'total' => $total,
                'currency' => 'USD',
                'placed_on' => $placed,
                'expected_on' => $expected,
                'carrier' => $carrier,
            ]);
        }
    }
}

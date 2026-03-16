<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DomainSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('customers')->insert([
            ['id' => 'CUST-001', 'name' => 'Alice Tanaka', 'email' => 'alice@example.com'],
            ['id' => 'CUST-002', 'name' => 'Bob Yamamoto', 'email' => 'bob@example.com'],
            ['id' => 'CUST-003', 'name' => 'Carol Suzuki', 'email' => 'carol@example.com'],
        ]);

        DB::table('suppliers')->insert([
            ['id' => 'SUP-001', 'name' => 'Acme Manufacturing'],
        ]);

        DB::table('products')->insert([
            [
                'id' => 'PROD-001',
                'name' => 'Widget A',
                'price' => 1000,
                'delivery_area' => 'domestic_only',
                'product_type' => 'standard',
                'supplier_id' => null,
            ],
            [
                'id' => 'PROD-002',
                'name' => 'Gadget B',
                'price' => 2500,
                'delivery_area' => 'international',
                'product_type' => 'standard',
                'supplier_id' => null,
            ],
            [
                'id' => 'PROD-003',
                'name' => 'Custom Frame C',
                'price' => 15000,
                'delivery_area' => 'domestic_only',
                'product_type' => 'made_to_order',
                'supplier_id' => 'SUP-001',
            ],
        ]);
    }
}

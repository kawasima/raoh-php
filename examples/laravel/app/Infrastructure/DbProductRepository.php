<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Product;
use App\Gateway\ProductRepository;
use Illuminate\Support\Facades\DB;

final class DbProductRepository implements ProductRepository
{
    public function findById(string $productId): ?Product
    {
        $row = DB::table('products')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->where('products.id', $productId)
            ->select('products.*', 'suppliers.name as supplier_name')
            ->first();

        if ($row === null) {
            return null;
        }

        return RowDecoders::product()->decode((array) $row)->getOrThrow();
    }
}

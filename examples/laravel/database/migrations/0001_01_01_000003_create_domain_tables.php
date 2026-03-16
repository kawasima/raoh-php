<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('name');
            $table->string('email');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('name');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('name');
            $table->integer('price');
            $table->string('delivery_area', 20);   // 'domestic_only' | 'international'
            $table->string('product_type', 20);     // 'standard' | 'made_to_order'
            $table->string('supplier_id', 36)->nullable();
            $table->foreign('supplier_id')->references('id')->on('suppliers');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('customer_id', 36);
            $table->string('status', 30)->default('created');
            $table->foreign('customer_id')->references('id')->on('customers');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 36);
            $table->string('product_id', 36);
            $table->integer('quantity');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
    }
};

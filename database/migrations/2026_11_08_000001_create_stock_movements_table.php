<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 100)->index();
            $table->string('movement_type', 20)->index();
            $table->integer('quantity_change')->default(0);
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });

        $baselineAt = now();

        DB::table('products')
            ->select(['id', 'sku', 'stock'])
            ->orderBy('id')
            ->chunkById(500, function ($products) use ($baselineAt): void {
                $rows = $products->map(fn (object $product): array => [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'movement_type' => 'BASELINE',
                    'quantity_change' => 0,
                    'stock_before' => $product->stock,
                    'stock_after' => $product->stock,
                    'order_id' => null,
                    'performed_by' => null,
                    'created_at' => $baselineAt,
                    'updated_at' => $baselineAt,
                ])->all();

                if ($rows !== []) {
                    DB::table('stock_movements')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

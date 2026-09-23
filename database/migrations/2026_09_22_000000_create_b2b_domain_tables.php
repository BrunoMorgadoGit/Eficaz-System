<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->enum('commercial_profile', ['STANDARD', 'GOLD', 'PREMIUM'])->default('STANDARD')->index();
            $table->decimal('credit_limit', 15, 2)->unsigned()->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->unsigned();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('unit_price', 15, 2)->unsigned();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['cart_id', 'product_id']);
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->restrictOnDelete();
            $table->decimal('subtotal', 15, 2)->unsigned();
            $table->decimal('discount', 15, 2)->unsigned()->default(0);
            $table->decimal('total', 15, 2)->unsigned();
            $table->timestamps();

            $table->index(['reseller_id', 'created_at']);
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('unit_price', 15, 2)->unsigned();
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 15, 2)->unsigned();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->restrictOnDelete();
            $table->foreignId('quote_id')->unique()->constrained()->restrictOnDelete();
            $table->enum('status', ['PENDENTE', 'APROVADO', 'CONCLUIDO'])->default('PENDENTE')->index();
            $table->decimal('subtotal', 15, 2)->unsigned();
            $table->decimal('discount', 15, 2)->unsigned()->default(0);
            $table->decimal('total', 15, 2)->unsigned();
            $table->timestamps();

            $table->index(['reseller_id', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('unit_price', 15, 2)->unsigned();
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 15, 2)->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('products');
        Schema::dropIfExists('resellers');
    }
};

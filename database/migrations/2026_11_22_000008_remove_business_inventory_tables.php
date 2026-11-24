<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('business_inventory_movements');
        Schema::dropIfExists('business_inventory_items');
    }

    public function down(): void
    {
        Schema::create('business_inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 180);
            $table->string('category', 50)->index();
            $table->string('brand', 120)->nullable();
            $table->string('material_type', 80)->nullable();
            $table->string('color', 80)->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('minimum_quantity', 12, 2)->default(0);
            $table->string('unit', 30)->default('pcs');
            $table->decimal('original_spool_weight_grams', 10, 2)->nullable();
            $table->string('supplier', 180)->nullable();
            $table->date('purchase_date')->nullable();
            $table->timestamps();
        });

        Schema::create('business_inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('business_inventory_items')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('business_orders')->nullOnDelete();
            $table->decimal('quantity_delta', 12, 2);
            $table->string('reason', 60);
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
};

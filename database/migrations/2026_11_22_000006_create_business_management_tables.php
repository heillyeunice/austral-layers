<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('company', 150)->nullable();
            $table->string('email', 190)->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->string('social_media_link', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('business_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->foreignId('customer_id')->constrained('business_customers')->restrictOnDelete();
            $table->foreignId('quote_inquiry_id')->nullable()->unique()->constrained('quote_inquiries')->nullOnDelete();
            $table->string('item_name', 180);
            $table->text('description');
            $table->text('specifications')->nullable();
            $table->date('order_date')->index();
            $table->date('deadline')->nullable()->index();
            $table->decimal('total_price', 12, 2)->default(0);
            $table->string('status', 40)->default('new_order')->index();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->string('shipping_method', 100)->nullable();
            $table->string('tracking_number', 120)->nullable();
            $table->string('shipping_status', 40)->default('not_shipped');
            $table->timestamps();
        });

        Schema::create('business_order_status_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('business_orders')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('business_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('business_orders')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 40);
            $table->date('payment_date')->index();
            $table->string('reference', 150)->nullable();
            $table->text('note')->nullable();
            $table->string('proof_file_path')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('business_expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('category', 60)->index();
            $table->decimal('amount', 12, 2);
            $table->date('expense_date')->index();
            $table->boolean('recurring')->default(false);
            $table->text('notes')->nullable();
            $table->string('receipt_file_path')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

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

        Schema::create('business_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('business_orders')->cascadeOnDelete();
            $table->string('document_number', 40)->unique();
            $table->string('type', 20);
            $table->string('status', 30)->default('issued');
            $table->date('issued_on');
            $table->date('due_on')->nullable();
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_documents');
        Schema::dropIfExists('business_inventory_movements');
        Schema::dropIfExists('business_inventory_items');
        Schema::dropIfExists('business_expenses');
        Schema::dropIfExists('business_payments');
        Schema::dropIfExists('business_order_status_history');
        Schema::dropIfExists('business_orders');
        Schema::dropIfExists('business_customers');
    }
};

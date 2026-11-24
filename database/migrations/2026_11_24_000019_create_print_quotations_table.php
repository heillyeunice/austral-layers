<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_name', 150)->nullable();
            $table->string('item_name', 180);
            $table->string('filament_type', 50);
            $table->decimal('weight_grams', 12, 2);
            $table->decimal('filament_cost_per_kg', 12, 2);
            $table->unsignedSmallInteger('print_hours');
            $table->unsignedTinyInteger('print_minutes');
            $table->decimal('electricity_rate', 12, 4);
            $table->decimal('printer_power_watts', 10, 2);
            $table->decimal('printer_price', 12, 2);
            $table->decimal('printer_lifespan_hours', 12, 2);
            $table->foreignId('printer_preset_id')->nullable()->constrained('printer_presets')->nullOnDelete();
            $table->string('printer_brand', 120)->nullable();
            $table->string('printer_model', 150)->nullable();
            $table->decimal('labor_rate', 12, 2);
            $table->unsignedSmallInteger('labor_hours');
            $table->unsignedTinyInteger('labor_minutes');
            $table->decimal('packaging_fee', 12, 2);
            $table->decimal('failure_rate', 6, 3);
            $table->decimal('profit_margin', 7, 3);
            $table->decimal('material_cost', 12, 2);
            $table->decimal('electricity_cost', 12, 2);
            $table->decimal('printer_wear_cost', 12, 2);
            $table->decimal('labor_cost', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('failure_cost', 12, 2);
            $table->decimal('profit_amount', 12, 2);
            $table->decimal('final_price', 12, 2);
            $table->string('view_mode', 20);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_quotations');
    }
};

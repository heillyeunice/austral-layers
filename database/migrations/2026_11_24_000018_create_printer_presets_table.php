<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printer_presets', function (Blueprint $table): void {
            $table->id();
            $table->string('brand', 120);
            $table->string('model', 150);
            $table->decimal('power_watts', 10, 2);
            $table->decimal('price', 12, 2);
            $table->decimal('lifespan_hours', 12, 2);
            $table->timestamps();
            $table->unique(['brand', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printer_presets');
    }
};

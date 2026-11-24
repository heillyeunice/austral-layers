<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_customers', function (Blueprint $table): void {
            $table->text('shipping_address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('business_customers', function (Blueprint $table): void {
            $table->dropColumn('shipping_address');
        });
    }
};

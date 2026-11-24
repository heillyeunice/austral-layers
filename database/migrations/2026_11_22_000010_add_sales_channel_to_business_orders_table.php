<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_orders', function (Blueprint $table): void {
            $table->string('sales_channel', 40)->nullable()->after('total_price')->index();
        });
    }

    public function down(): void
    {
        Schema::table('business_orders', function (Blueprint $table): void {
            $table->dropIndex(['sales_channel']);
            $table->dropColumn('sales_channel');
        });
    }
};

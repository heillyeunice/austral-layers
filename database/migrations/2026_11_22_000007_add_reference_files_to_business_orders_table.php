<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('business_orders', 'reference_files')) {
            Schema::table('business_orders', function (Blueprint $table): void {
                $table->json('reference_files')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('business_orders', 'reference_files')) {
            Schema::table('business_orders', function (Blueprint $table): void {
                $table->dropColumn('reference_files');
            });
        }
    }
};

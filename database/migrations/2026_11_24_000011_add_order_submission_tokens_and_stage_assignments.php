<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_orders', function (Blueprint $table): void {
            $table->uuid('submission_token')->nullable()->unique();
        });

        Schema::create('business_order_stage_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('business_orders')->cascadeOnDelete();
            $table->string('stage', 40);
            $table->foreignId('assigned_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_order_stage_assignments');

        Schema::table('business_orders', function (Blueprint $table): void {
            $table->dropUnique(['submission_token']);
            $table->dropColumn('submission_token');
        });
    }
};

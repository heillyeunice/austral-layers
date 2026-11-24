<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_orders', function (Blueprint $table): void {
            $table->softDeletes();
            $table->text('deletion_reason')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('business_expenses', function (Blueprint $table): void {
            $table->uuid('submission_token')->nullable()->unique();
            $table->softDeletes();
            $table->text('deletion_reason')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('business_expenses', function (Blueprint $table): void {
            $table->dropForeign(['deleted_by']);
            $table->dropUnique(['submission_token']);
            $table->dropColumn(['submission_token', 'deleted_at', 'deletion_reason', 'deleted_by']);
        });

        Schema::table('business_orders', function (Blueprint $table): void {
            $table->dropForeign(['deleted_by']);
            $table->dropColumn(['deleted_at', 'deletion_reason', 'deleted_by']);
        });
    }
};

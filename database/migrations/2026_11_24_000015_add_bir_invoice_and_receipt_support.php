<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_documents', function (Blueprint $table): void {
            $table->string('invoice_number', 100)->nullable()->unique();
            $table->foreignId('invoice_document_id')
                ->nullable()
                ->constrained('business_documents')
                ->nullOnDelete();
            $table->foreignId('payment_id')
                ->nullable()
                ->unique()
                ->constrained('business_payments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('business_documents', function (Blueprint $table): void {
            $table->dropForeign(['invoice_document_id']);
            $table->dropForeign(['payment_id']);
            $table->dropUnique(['invoice_number']);
            $table->dropUnique(['payment_id']);
            $table->dropColumn(['invoice_number', 'invoice_document_id', 'payment_id']);
        });
    }
};

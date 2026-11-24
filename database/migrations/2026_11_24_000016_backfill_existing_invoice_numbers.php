<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $invoices = DB::table('business_documents')
            ->where('type', 'invoice')
            ->whereNull('invoice_number')
            ->orderBy('id')
            ->get(['id', 'document_number']);

        foreach ($invoices as $invoice) {
            $invoiceNumber = $invoice->document_number;
            $suffix = 1;

            while (DB::table('business_documents')->where('invoice_number', $invoiceNumber)->exists()) {
                $invoiceNumber = 'AUTO-'.$invoice->document_number.'-'.$suffix++;
            }

            DB::table('business_documents')
                ->where('id', $invoice->id)
                ->update(['invoice_number' => $invoiceNumber]);
        }
    }

    public function down(): void
    {
        $invoices = DB::table('business_documents')
            ->where('type', 'invoice')
            ->whereNotNull('invoice_number')
            ->get(['id', 'document_number', 'invoice_number']);

        foreach ($invoices as $invoice) {
            $isGeneratedNumber = $invoice->invoice_number === $invoice->document_number
                || str_starts_with($invoice->invoice_number, 'AUTO-'.$invoice->document_number.'-');

            if ($isGeneratedNumber) {
                DB::table('business_documents')
                    ->where('id', $invoice->id)
                    ->update(['invoice_number' => null]);
            }
        }
    }
};

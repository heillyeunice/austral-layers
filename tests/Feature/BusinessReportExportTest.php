<?php

namespace Tests\Feature;

use App\Models\BusinessCustomer;
use App\Models\BusinessDocument;
use App\Models\BusinessExpense;
use App\Models\BusinessOrder;
use App\Models\BusinessPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class BusinessReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_workbook_contains_all_sheets_and_excludes_archived_records(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $reportDate = now()->startOfMonth()->addDays(2);
        $customer = BusinessCustomer::query()->create([
            'name' => 'Monthly Report Customer',
            'company' => 'Report Works',
            'email' => 'report@example.com',
        ]);
        $activeOrder = $this->makeOrder($customer, [
            'order_number' => 'AL-REPORT-ACTIVE',
            'item_name' => 'Report wall holder',
            'order_date' => $reportDate,
            'total_price' => 1000,
            'status' => 'production',
        ]);
        $outsideOrder = $this->makeOrder($customer, [
            'order_number' => 'AL-REPORT-OUTSIDE',
            'item_name' => 'Outside period item',
            'order_date' => now()->subMonth()->toDateString(),
        ]);
        $archivedOrder = $this->makeOrder($customer, [
            'order_number' => 'AL-REPORT-ARCHIVED',
            'item_name' => 'Archived order item',
            'order_date' => $reportDate,
        ]);
        $archivedOrder->delete();

        $payment = BusinessPayment::query()->create([
            'order_id' => $activeOrder->id,
            'amount' => 400,
            'method' => 'gcash',
            'payment_date' => $reportDate,
            'reference' => 'PAY-REPORT-1',
            'recorded_by' => $admin->id,
        ]);
        BusinessPayment::query()->create([
            'order_id' => $archivedOrder->id,
            'amount' => 900,
            'method' => 'cash',
            'payment_date' => $reportDate,
        ]);
        BusinessExpense::query()->create([
            'title' => 'Monthly report materials',
            'category' => 'materials',
            'amount' => 100,
            'expense_date' => $reportDate,
            'recorded_by' => $admin->id,
        ]);
        $archivedExpense = BusinessExpense::query()->create([
            'title' => 'Archived report expense',
            'category' => 'other',
            'amount' => 500,
            'expense_date' => $reportDate,
        ]);
        $archivedExpense->delete();

        $invoice = BusinessDocument::query()->create([
            'order_id' => $activeOrder->id,
            'document_number' => 'DOC-REPORT-INVOICE',
            'invoice_number' => 'INV-REPORT-1',
            'type' => 'invoice',
            'status' => 'issued',
            'issued_on' => $reportDate,
            'snapshot' => ['total_price' => 1000],
            'created_by' => $admin->id,
        ]);
        BusinessDocument::query()->create([
            'order_id' => $activeOrder->id,
            'document_number' => 'DOC-REPORT-RECEIPT',
            'invoice_document_id' => $invoice->id,
            'payment_id' => $payment->id,
            'type' => 'receipt',
            'status' => 'issued',
            'issued_on' => $reportDate,
            'snapshot' => ['payment_amount' => 400],
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.business.reports.export', [
                'period' => 'monthly',
                'year' => $reportDate->year,
                'month' => $reportDate->month,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename="austral-layers-business-monthly-'.$reportDate->format('Y-m').'.xlsx"');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'business-report-');

        try {
            file_put_contents($temporaryFile, $response->getContent());
            $workbook = new ZipArchive;
            $this->assertSame(true, $workbook->open($temporaryFile));

            $workbookXml = $workbook->getFromName('xl/workbook.xml');
            $sharedStrings = $workbook->getFromName('xl/sharedStrings.xml');
            $workbook->close();

            $this->assertStringContainsString('Summary', $workbookXml);
            $this->assertStringContainsString('Orders', $workbookXml);
            $this->assertStringContainsString('Payments', $workbookXml);
            $this->assertStringContainsString('Expenses', $workbookXml);
            $this->assertStringContainsString('Customers', $workbookXml);
            $this->assertStringContainsString('Invoices &amp; Receipts', $workbookXml);
            $this->assertStringContainsString('AL-REPORT-ACTIVE', $sharedStrings);
            $this->assertStringContainsString('Monthly Report Customer', $sharedStrings);
            $this->assertStringNotContainsString('AL-REPORT-OUTSIDE', $sharedStrings);
            $this->assertStringNotContainsString('AL-REPORT-ARCHIVED', $sharedStrings);
            $this->assertStringNotContainsString('Archived report expense', $sharedStrings);
        } finally {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    public function test_annual_export_does_not_require_a_month_and_reports_page_is_available(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->get(route('admin.business.reports.index'))
            ->assertOk()
            ->assertSee('Reports & exports')
            ->assertSee('Invoices & receipts');

        $this->get(route('admin.business.reports.export', [
            'period' => 'annual',
            'year' => now()->year,
        ]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="austral-layers-business-annual-'.now()->year.'.xlsx"');
    }

    private function makeOrder(BusinessCustomer $customer, array $attributes): BusinessOrder
    {
        return BusinessOrder::query()->create([
            'order_number' => 'AL-REPORT-'.strtoupper(bin2hex(random_bytes(5))),
            'customer_id' => $customer->id,
            'item_name' => 'Report item',
            'description' => 'Report test order.',
            'order_date' => today(),
            'total_price' => 200,
            'status' => 'new_order',
            ...$attributes,
        ]);
    }

    private function makeUser(string $role): User
    {
        $user = new User;
        $user->forceFill([
            'name' => ucfirst($role).' User',
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => 'password',
            'role' => $role,
        ])->save();

        return $user;
    }
}

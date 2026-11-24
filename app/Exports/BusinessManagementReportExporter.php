<?php

namespace App\Exports;

use App\Models\BusinessCustomer;
use App\Models\BusinessDocument;
use App\Models\BusinessExpense;
use App\Models\BusinessOrder;
use App\Models\BusinessPayment;
use Carbon\CarbonImmutable;
use Shuchkin\SimpleXLSXGen;

class BusinessManagementReportExporter
{
    private const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'gcash' => 'GCash',
        'maya' => 'Maya',
        'bank_transfer' => 'Bank transfer',
        'other' => 'Other',
    ];

    private const EXPENSE_CATEGORIES = [
        'materials' => 'Materials',
        'utilities' => 'Utilities',
        'shipping' => 'Shipping',
        'salaries' => 'Salaries',
        'equipment' => 'Equipment',
        'marketing' => 'Marketing',
        'other' => 'Other',
    ];

    public function make(
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $periodLabel,
    ): string {
        $range = [$start->toDateString(), $end->toDateString()];
        $orders = BusinessOrder::query()
            ->with(['customer', 'assignedStaff'])
            ->whereBetween('order_date', $range)
            ->orderBy('order_date')
            ->orderBy('id')
            ->get();
        $payments = BusinessPayment::query()
            ->with(['order.customer', 'recordedBy'])
            ->whereBetween('payment_date', $range)
            ->whereHas('order')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();
        $expenses = BusinessExpense::query()
            ->with('recordedBy')
            ->whereBetween('expense_date', $range)
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();
        $customers = BusinessCustomer::query()
            ->where(function ($query) use ($range, $start, $end): void {
                $query
                    ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
                    ->orWhereHas('orders', fn ($orders) => $orders->whereBetween('order_date', $range));
            })
            ->withCount(['orders as orders_in_period_count' => fn ($orders) => $orders->whereBetween('order_date', $range)])
            ->orderBy('name')
            ->get();
        $documents = BusinessDocument::query()
            ->with(['order.customer', 'invoiceDocument'])
            ->whereBetween('issued_on', $range)
            ->whereIn('type', ['invoice', 'receipt'])
            ->whereHas('order')
            ->orderBy('issued_on')
            ->orderBy('id')
            ->get();

        $paymentTotal = round((float) $payments->sum('amount'), 2);
        $expenseTotal = round((float) $expenses->sum('amount'), 2);
        $orderValue = round((float) $orders
            ->reject(fn (BusinessOrder $order): bool => $order->status === 'cancelled')
            ->sum(fn (BusinessOrder $order): float => (float) $order->total_price), 2);

        $xlsx = SimpleXLSXGen::fromArray(
            $this->summaryRows(
                $periodLabel,
                $paymentTotal,
                $expenseTotal,
                $orderValue,
                $orders->count(),
                $customers->count(),
                $documents->count(),
            ),
            'Summary'
        );
        $xlsx
            ->setDefaultFont('Aptos')
            ->setDefaultFontSize(10)
            ->setAuthor('Austral Layers')
            ->setCompany('Austral Layers')
            ->setTitle('Business report · '.$periodLabel)
            ->setSubject('Business management export')
            ->setDescription('Monthly or annual business management report.');

        $this->formatSummarySheet($xlsx);
        $this->addDetailSheet(
            $xlsx,
            'Orders',
            $periodLabel,
            ['Order number', 'Product / service', 'Customer', 'Order date', 'Deadline', 'Status', 'Sales channel', 'Order owner', 'Total price (PHP)'],
            $orders->map(fn (BusinessOrder $order): array => [
                $this->text($order->order_number),
                $this->text($order->item_name),
                $this->text($order->customer?->name),
                $this->date($order->order_date),
                $this->date($order->deadline),
                $this->text(BusinessOrder::STATUSES[$order->status] ?? $order->status),
                $this->text(BusinessOrder::SALES_CHANNELS[$order->sales_channel] ?? $order->sales_channel),
                $this->text($order->assignedStaff?->name),
                $this->currency((float) $order->total_price),
            ])->all(),
            [22, 32, 24, 16, 16, 24, 26, 24, 20],
            8,
            $orderValue,
            'TOTAL (EXCLUDING CANCELLED)',
        );
        $this->addDetailSheet(
            $xlsx,
            'Payments',
            $periodLabel,
            ['Payment date', 'Order number', 'Customer', 'Amount (PHP)', 'Method', 'Reference', 'Note', 'Recorded by'],
            $payments->map(fn (BusinessPayment $payment): array => [
                $this->date($payment->payment_date),
                $this->text($payment->order?->order_number),
                $this->text($payment->order?->customer?->name),
                $this->currency((float) $payment->amount),
                $this->text(self::PAYMENT_METHODS[$payment->method] ?? $payment->method),
                $this->text($payment->reference),
                $this->text($payment->note),
                $this->text($payment->recordedBy?->name),
            ])->all(),
            [18, 22, 24, 20, 20, 24, 40, 24],
            3,
            $paymentTotal,
        );
        $this->addDetailSheet(
            $xlsx,
            'Expenses',
            $periodLabel,
            ['Expense date', 'Expense', 'Category', 'Amount (PHP)', 'Recurring', 'Notes', 'Recorded by'],
            $expenses->map(fn (BusinessExpense $expense): array => [
                $this->date($expense->expense_date),
                $this->text($expense->title),
                $this->text(self::EXPENSE_CATEGORIES[$expense->category] ?? $expense->category),
                $this->currency((float) $expense->amount),
                $this->text($expense->recurring ? 'Yes' : 'No'),
                $this->text($expense->notes),
                $this->text($expense->recordedBy?->name),
            ])->all(),
            [18, 32, 20, 20, 14, 42, 24],
            3,
            $expenseTotal,
        );
        $this->addDetailSheet(
            $xlsx,
            'Customers',
            $periodLabel,
            ['Customer', 'Company', 'Email', 'Phone', 'Shipping address', 'Social link', 'Orders in period', 'Customer since'],
            $customers->map(fn (BusinessCustomer $customer): array => [
                $this->text($customer->name),
                $this->text($customer->company),
                $this->text($customer->email),
                $this->text($customer->phone),
                $this->text($customer->shipping_address),
                $this->text($customer->social_media_link),
                (int) $customer->orders_in_period_count,
                $this->date($customer->created_at),
            ])->all(),
            [28, 28, 32, 20, 42, 36, 20, 18],
        );
        $this->addDetailSheet(
            $xlsx,
            'Invoices & Receipts',
            $periodLabel,
            ['Type', 'Document number', 'Invoice number', 'Order number', 'Customer', 'Issued on', 'Due on', 'Amount (PHP)'],
            $documents->map(fn (BusinessDocument $document): array => [
                $this->text(ucfirst($document->type)),
                $this->text($document->document_number),
                $this->text($document->type === 'receipt'
                    ? $document->invoiceDocument?->invoice_number
                    : $document->invoice_number),
                $this->text($document->order?->order_number),
                $this->text($document->order?->customer?->name),
                $this->date($document->issued_on),
                $this->date($document->due_on),
                $this->currency((float) data_get(
                    $document->snapshot,
                    $document->type === 'receipt' ? 'payment_amount' : 'total_price',
                    0
                )),
            ])->all(),
            [18, 24, 24, 22, 28, 18, 18, 20],
        );

        return (string) $xlsx;
    }

    private function summaryRows(
        string $periodLabel,
        float $payments,
        float $expenses,
        float $orderValue,
        int $orderCount,
        int $customerCount,
        int $documentCount,
    ): array {
        return [
            [$this->titleCell('AUSTRAL LAYERS  |  BUSINESS REPORT'), null, null],
            [$this->subtitleCell('Reporting period: '.$periodLabel.' · All currency amounts are in PHP.'), null, null],
            [null, null, null],
            [$this->headerCell('Metric'), $this->headerCell('Amount / count'), $this->headerCell('Details')],
            [
                $this->text('Payments received'),
                $this->currency($payments),
                $this->text('Payments dated within the selected period; archived orders are excluded.'),
            ],
            [
                $this->text('Expenses'),
                $this->currency($expenses),
                $this->text('Expenses dated within the selected period; archived expenses are excluded.'),
            ],
            [
                $this->text('Net cash flow'),
                $this->currency(round($payments - $expenses, 2)),
                $this->text('Payments received less recorded expenses.'),
            ],
            [
                $this->text('Orders in period'),
                $orderCount,
                $this->text('Non-archived orders, including cancelled orders.'),
            ],
            [
                $this->text('Order value, excluding cancelled'),
                $this->currency($orderValue),
                $this->text('Order totals for non-cancelled, non-archived orders.'),
            ],
            [
                $this->text('Customers included'),
                $customerCount,
                $this->text('Created or placed an order during the selected period.'),
            ],
            [
                $this->text('Invoices and receipts issued'),
                $documentCount,
                $this->text('Issued documents linked to non-archived orders.'),
            ],
        ];
    }

    private function formatSummarySheet(SimpleXLSXGen $xlsx): void
    {
        $xlsx
            ->mergeCells('A1:C1')
            ->mergeCells('A2:C2')
            ->setColWidth('A', 38)
            ->setColWidth('B', 22)
            ->setColWidth('C', 78)
            ->freezePanes('A5');
    }

    private function addDetailSheet(
        SimpleXLSXGen $xlsx,
        string $sheetName,
        string $periodLabel,
        array $headers,
        array $records,
        array $widths,
        ?int $totalColumn = null,
        ?float $total = null,
        string $totalLabel = 'TOTAL',
    ): void {
        $columnCount = count($headers);
        $lastColumn = $this->columnName($columnCount);
        $rows = [
            [$this->titleCell('AUSTRAL LAYERS  |  '.$sheetName), ...array_fill(0, $columnCount - 1, null)],
            [$this->subtitleCell('Reporting period: '.$periodLabel.' · Archived records are excluded.'), ...array_fill(0, $columnCount - 1, null)],
            array_fill(0, $columnCount, null),
            array_map(fn (string $header): string => $this->headerCell($header), $headers),
        ];

        if ($records === []) {
            $rows[] = [$this->text('No records for this period.'), ...array_fill(0, $columnCount - 1, null)];
        } else {
            array_push($rows, ...$records);
        }

        $lastDataRow = 4 + count($records);

        if ($totalColumn !== null && $total !== null) {
            $totalRow = array_fill(0, $columnCount, null);
            $totalRow[0] = '<b>'.$totalLabel.'</b>';
            $totalRow[$totalColumn] = $this->currency($total);
            $rows[] = $totalRow;
        }

        $xlsx->addSheet($rows, $sheetName)
            ->mergeCells('A1:'.$lastColumn.'1')
            ->mergeCells('A2:'.$lastColumn.'2')
            ->freezePanes('A5')
            ->autoFilter('A4:'.$lastColumn.$lastDataRow);

        foreach ($widths as $index => $width) {
            $xlsx->setColWidth($index + 1, $width);
        }
    }

    private function titleCell(string $text): string
    {
        return '<style bgcolor="#14201F" color="#FFFFFF" font-size="18"><b>'.$text.'</b></style>';
    }

    private function subtitleCell(string $text): string
    {
        return '<style bgcolor="#EAF0EE" color="#40534D"><i>'.$text.'</i></style>';
    }

    private function headerCell(string $text): string
    {
        return '<style bgcolor="#E17A55" color="#FFFFFF"><b>'.$text.'</b></style>';
    }

    private function currency(float $amount): string
    {
        return '<style nf="&quot;₱&quot;#,##0.00">'.number_format($amount, 2, '.', '').'</style>';
    }

    private function text(?string $value): string
    {
        return SimpleXLSXGen::raw($value === null || $value === '' ? '—' : $value);
    }

    private function date(?\DateTimeInterface $date): string
    {
        return $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : SimpleXLSXGen::raw('—');
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $name = chr(65 + $remainder).$name;
            $index = intdiv($index - 1, 26);
        }

        return $name;
    }
}

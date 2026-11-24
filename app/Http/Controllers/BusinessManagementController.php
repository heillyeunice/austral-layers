<?php

namespace App\Http\Controllers;

use App\Models\BusinessCustomer;
use App\Models\BusinessDocument;
use App\Models\BusinessExpense;
use App\Models\BusinessOrder;
use App\Models\BusinessOrderStageAssignment;
use App\Models\BusinessOrderStageAssignmentHistory;
use App\Models\BusinessOrderStatusHistory;
use App\Models\BusinessPayment;
use App\Models\QuoteInquiry;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusinessManagementController extends Controller
{
    private const EXPENSE_CATEGORIES = [
        'materials' => 'Materials',
        'utilities' => 'Utilities',
        'shipping' => 'Shipping',
        'salaries' => 'Salaries',
        'equipment' => 'Equipment',
        'marketing' => 'Marketing',
        'other' => 'Other',
    ];

    private const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'gcash' => 'GCash',
        'maya' => 'Maya',
        'bank_transfer' => 'Bank transfer',
        'other' => 'Other',
    ];

    public function dashboard(): View
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $completedOrders = BusinessOrder::query()
            ->where('status', 'completed')
            ->whereDate('order_date', '>=', $monthStart)
            ->whereDate('order_date', '<=', $today);
        $paymentsReceived = (float) BusinessPayment::query()
            ->whereHas('order')
            ->whereDate('payment_date', '>=', $monthStart)
            ->whereDate('payment_date', '<=', $today)
            ->sum('amount');
        $expenses = (float) BusinessExpense::query()
            ->whereDate('expense_date', '>=', $monthStart)
            ->whereDate('expense_date', '<=', $today)
            ->sum('amount');
        $ordersWithBalance = BusinessOrder::query()
            ->where('status', '!=', 'cancelled')
            ->withSum('payments', 'amount')
            ->get(['id', 'total_price']);
        $openOrders = BusinessOrder::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
        $outstandingBalance = $ordersWithBalance->sum(
            fn (BusinessOrder $order): float => max(
                round((float) $order->total_price - (float) ($order->payments_sum_amount ?? 0), 2),
                0
            )
        );

        return view('admin.business.dashboard', [
            'monthGrossSales' => (float) $completedOrders->sum('total_price'),
            'monthPayments' => $paymentsReceived,
            'monthExpenses' => $expenses,
            'netCashFlow' => round($paymentsReceived - $expenses, 2),
            'outstandingBalance' => $outstandingBalance,
            'openOrderCount' => $openOrders,
            'overdueOrderCount' => BusinessOrder::query()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereDate('deadline', '<', $today)
                ->count(),
            'recentOrders' => BusinessOrder::query()
                ->with('customer')
                ->latest('order_date')
                ->limit(8)
                ->get(),
            'topItems' => BusinessOrder::query()
                ->select('item_name')
                ->selectRaw('COUNT(*) as order_count, SUM(total_price) as sales_total')
                ->where('status', 'completed')
                ->whereDate('order_date', '>=', $monthStart)
                ->whereDate('order_date', '<=', $today)
                ->groupBy('item_name')
                ->orderByDesc('sales_total')
                ->limit(5)
                ->get(),
            'noIndex' => true,
        ]);
    }

    public function customers(Request $request): View
    {
        return view('admin.business.customers.index', [
            'customers' => BusinessCustomer::query()
                ->withCount('orders')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'noIndex' => true,
        ]);
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('businessCustomer', [
            'name' => ['required', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'shipping_address' => ['nullable', 'string', 'max:2000'],
            'social_media_link' => ['nullable', 'url:http,https', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $validated['name'] = Str::squish($validated['name']);

        $customer = BusinessCustomer::query()->create($validated);

        return redirect()
            ->route('admin.business.customers.show', $customer)
            ->with('business_saved', 'Customer added.');
    }

    public function showCustomer(BusinessCustomer $customer): View
    {
        return view('admin.business.customers.show', [
            'customer' => $customer->load([
                'orders' => fn ($query) => $query->latest('order_date'),
            ]),
            'noIndex' => true,
        ]);
    }

    public function updateCustomer(Request $request, BusinessCustomer $customer): RedirectResponse
    {
        $validated = $request->validateWithBag('businessCustomer', [
            'name' => ['required', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'shipping_address' => ['nullable', 'string', 'max:2000'],
            'social_media_link' => ['nullable', 'url:http,https', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $validated['name'] = Str::squish($validated['name']);

        $customer->update($validated);

        return redirect()
            ->route('admin.business.customers.show', $customer)
            ->with('business_saved', 'Customer details updated.');
    }

    public function orders(Request $request): View
    {
        $status = $request->query('status');
        $channel = $request->query('channel');
        $query = BusinessOrder::query()->with('customer')->latest('order_date');
        $productionStatuses = array_diff_key(
            BusinessOrder::STATUSES,
            array_flip(['completed', 'cancelled'])
        );

        if ($status === 'in_progress') {
            $query->whereNotIn('status', ['completed', 'cancelled']);
        } elseif (is_string($status) && array_key_exists($status, BusinessOrder::STATUSES)) {
            $query->where('status', $status);
        } else {
            $status = null;
        }

        if (is_string($channel) && array_key_exists($channel, BusinessOrder::SALES_CHANNELS)) {
            $query->where('sales_channel', $channel);
        } else {
            $channel = null;
        }

        $kanbanOrders = BusinessOrder::query()
            ->with(['customer', 'stageAssignments.assignedStaff', 'statusHistory'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest('order_date')
            ->limit(100)
            ->get();
        $statusIndexes = array_flip(array_keys(BusinessOrder::STATUSES));
        $kanbanOrders->each(function (BusinessOrder $order) use ($statusIndexes): void {
            $furthestStatusIndex = $order->statusHistory
                ->pluck('to_status')
                ->push($order->status)
                ->map(fn (string $status): int => $statusIndexes[$status] ?? -1)
                ->max();
            $order->setAttribute('furthest_status_index', $furthestStatusIndex ?? -1);
        });

        return view('admin.business.orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'kanbanOrders' => $kanbanOrders,
            'productionStatuses' => $productionStatuses,
            'statuses' => BusinessOrder::STATUSES,
            'salesChannels' => BusinessOrder::SALES_CHANNELS,
            'staff' => $this->staffUsers(),
            'status' => $status,
            'channel' => $channel,
            'noIndex' => true,
        ]);
    }

    public function createOrder(Request $request): View
    {
        return view('admin.business.orders.form', [
            'customers' => BusinessCustomer::query()->orderBy('name')->get(),
            'prefillCustomer' => $request->integer('customer_id')
                ? BusinessCustomer::query()->find($request->integer('customer_id'))
                : null,
            'staff' => $this->staffUsers(),
            'statuses' => BusinessOrder::STATUSES,
            'salesChannels' => BusinessOrder::SALES_CHANNELS,
            'order' => null,
            'submissionToken' => (string) Str::uuid(),
            'noIndex' => true,
        ]);
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('businessOrder', $this->orderRules(forNewOrder: true));
        $submissionToken = $validated['submission_token'];
        $existingOrder = BusinessOrder::withTrashed()
            ->where('submission_token', $submissionToken)
            ->first();

        if ($existingOrder !== null) {
            if ($existingOrder->trashed()) {
                return redirect()
                    ->route('admin.business.archive.index')
                    ->with('business_saved', 'This order was already submitted and has been archived.');
            }

            return redirect()
                ->route('admin.business.orders.show', $existingOrder)
                ->with('business_saved', 'This order was already submitted.');
        }

        try {
            $order = DB::transaction(function () use ($validated, $request): BusinessOrder {
                $customerName = Str::squish($validated['customer_name']);
                unset($validated['customer_name'], $validated['reference_files']);
                $customer = BusinessCustomer::query()
                    ->whereRaw('LOWER(name) = ?', [Str::lower($customerName)])
                    ->first();

                if ($customer === null) {
                    $customer = BusinessCustomer::query()->create(['name' => $customerName]);
                }

                $order = BusinessOrder::query()->create([
                    ...$validated,
                    'order_number' => $this->newOrderNumber(),
                    'customer_id' => $customer->id,
                    'status' => $validated['status'] ?? 'new_order',
                    'shipping_status' => $validated['shipping_status'] ?? 'not_shipped',
                    'reference_files' => [],
                ]);
                $order->update([
                    'reference_files' => $this->storePrivateFiles(
                        $request->file('reference_files', []),
                        'business-order-references'
                    ),
                ]);

                BusinessOrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'to_status' => $order->status,
                    'note' => 'Order created.',
                    'changed_by' => auth()->id(),
                ]);

                return $order;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $order = BusinessOrder::withTrashed()
                ->where('submission_token', $submissionToken)
                ->first();

            if ($order === null) {
                throw $exception;
            }

            return $order->trashed()
                ? redirect()
                    ->route('admin.business.archive.index')
                    ->with('business_saved', 'This order was already submitted and has been archived.')
                : redirect()
                    ->route('admin.business.orders.show', $order)
                    ->with('business_saved', 'This order was already submitted.');
        }

        return redirect()
            ->route('admin.business.orders.show', $order)
            ->with('business_saved', 'Order created.');
    }

    public function createFromInquiry(QuoteInquiry $inquiry): RedirectResponse
    {
        $existingOrder = BusinessOrder::withTrashed()->where('quote_inquiry_id', $inquiry->id)->first();

        if ($existingOrder !== null) {
            if ($existingOrder->trashed()) {
                return redirect()
                    ->route('admin.business.archive.index')
                    ->with('business_saved', 'This inquiry is linked to an archived order.');
            }

            return redirect()->route('admin.business.orders.show', $existingOrder);
        }

        $customer = BusinessCustomer::query()->where('email', $inquiry->email)->first();

        if ($customer === null) {
            $customer = BusinessCustomer::query()->create([
                'name' => $inquiry->name,
                'company' => $inquiry->company,
                'email' => $inquiry->email,
                'social_media_link' => $inquiry->social_media_link,
            ]);
        }

        $order = DB::transaction(function () use ($customer, $inquiry): BusinessOrder {
            $order = BusinessOrder::query()->create([
                'order_number' => $this->newOrderNumber(),
                'customer_id' => $customer->id,
                'quote_inquiry_id' => $inquiry->id,
                'item_name' => $inquiry->service,
                'description' => $inquiry->message,
                'specifications' => null,
                'order_date' => today(),
                'deadline' => null,
                'total_price' => 0,
                'sales_channel' => 'website',
                'status' => 'pending_confirmation',
                'assigned_staff_id' => auth()->id(),
                'internal_notes' => 'Created from quote inquiry #'.$inquiry->id.'. Set the agreed total before recording payments or issuing an invoice.',
                'shipping_status' => 'not_shipped',
            ]);

            BusinessOrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'to_status' => $order->status,
                'note' => 'Converted from quote inquiry #'.$inquiry->id.'.',
                'changed_by' => auth()->id(),
            ]);

            return $order;
        });

        return redirect()
            ->route('admin.business.orders.show', $order)
            ->with('business_saved', 'Quote converted to an order. Set the agreed total from order details when it is available.');
    }

    public function showOrder(BusinessOrder $order): View
    {
        $order->load([
            'customer',
            'inquiry',
            'assignedStaff',
            'payments.recordedBy',
            'payments.receipt',
            'statusHistory.changedBy',
            'stageAssignmentHistory.assignedStaff',
            'stageAssignmentHistory.changedBy',
            'documents.invoiceDocument',
            'documents.payment',
        ]);
        $paidAmount = (float) $order->payments->sum('amount');
        $invoices = $order->documents
            ->filter(fn (BusinessDocument $document): bool => $document->type === 'invoice' && filled($document->invoice_number))
            ->values();
        $availableReceiptPayments = $order->payments
            ->filter(fn (BusinessPayment $payment): bool => $payment->receipt === null)
            ->values();

        return view('admin.business.orders.show', [
            'order' => $order,
            'statuses' => BusinessOrder::STATUSES,
            'staff' => $this->staffUsers(),
            'paymentMethods' => self::PAYMENT_METHODS,
            'paidAmount' => $paidAmount,
            'invoices' => $invoices,
            'availableReceiptPayments' => $availableReceiptPayments,
            'balanceDue' => max(round((float) $order->total_price - $paidAmount, 2), 0),
            'paymentStatus' => $this->paymentStatus((float) $order->total_price, $paidAmount),
            'paymentSubmissionToken' => (string) Str::uuid(),
            'noIndex' => true,
        ]);
    }

    public function updateOrder(Request $request, BusinessOrder $order): RedirectResponse
    {
        $validated = $request->validateWithBag('businessOrder', [
            'status' => ['required', Rule::in(array_keys(BusinessOrder::STATUSES))],
            'internal_notes' => ['nullable', 'string', 'max:4000'],
            'shipping_method' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'shipping_status' => ['nullable', Rule::in(['not_shipped', 'in_transit', 'delivered', 'exception'])],
            'reference_files' => ['nullable', 'array', 'max:5'],
            'reference_files.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:10240'],
        ]);
        $newReferenceFiles = $request->file('reference_files', []);
        $newReferenceFiles = is_array($newReferenceFiles) ? $newReferenceFiles : [];

        if (count($order->reference_files ?? []) + count($newReferenceFiles) > 5) {
            return back()
                ->withErrors(['reference_files' => 'An order can have up to five reference files.'], 'businessOrder')
                ->withInput($request->except('reference_files'));
        }

        DB::transaction(function () use ($order, $validated, $request): void {
            $lockedOrder = BusinessOrder::query()->lockForUpdate()->findOrFail($order->id);
            $previousStatus = $lockedOrder->status;
            $referenceFiles = $this->storePrivateFiles($request->file('reference_files', []), 'business-order-references');
            unset($validated['reference_files']);
            $lockedOrder->update([
                ...$validated,
                'shipping_status' => $validated['shipping_status'] ?? 'not_shipped',
                'reference_files' => array_merge($lockedOrder->reference_files ?? [], $referenceFiles),
            ]);

            if ($previousStatus !== $lockedOrder->status) {
                BusinessOrderStatusHistory::query()->create([
                    'order_id' => $lockedOrder->id,
                    'from_status' => $previousStatus,
                    'to_status' => $lockedOrder->status,
                    'note' => 'Status updated.',
                    'changed_by' => auth()->id(),
                ]);
            }
        });

        return redirect()
            ->route('admin.business.orders.show', $order)
            ->with('business_saved', 'Order updated.');
    }

    public function moveOrder(Request $request, BusinessOrder $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(BusinessOrder::STATUSES))],
        ]);
        $status = DB::transaction(function () use ($order, $validated): string {
            $lockedOrder = BusinessOrder::query()->lockForUpdate()->findOrFail($order->id);
            $previousStatus = $lockedOrder->status;

            if ($previousStatus !== $validated['status']) {
                $lockedOrder->update(['status' => $validated['status']]);
                BusinessOrderStatusHistory::query()->create([
                    'order_id' => $lockedOrder->id,
                    'from_status' => $previousStatus,
                    'to_status' => $lockedOrder->status,
                    'note' => 'Moved on the order board.',
                    'changed_by' => auth()->id(),
                ]);
            }

            return $lockedOrder->status;
        });

        return response()->json([
            'status' => $status,
            'label' => BusinessOrder::STATUSES[$status],
        ]);
    }

    public function updateStageAssignment(Request $request, BusinessOrder $order): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['required', Rule::in(BusinessOrder::ASSIGNABLE_STAGES)],
            'assigned_staff_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
                ),
            ],
        ]);
        $assignment = DB::transaction(function () use ($request, $order, $validated): BusinessOrderStageAssignment {
            $lockedOrder = BusinessOrder::query()->lockForUpdate()->findOrFail($order->id);
            $statusIndexes = array_flip(array_keys(BusinessOrder::STATUSES));
            $furthestStatusIndex = $lockedOrder->statusHistory()
                ->pluck('to_status')
                ->push($lockedOrder->status)
                ->map(fn (string $status): int => $statusIndexes[$status] ?? -1)
                ->max() ?? -1;
            $stageIndex = $statusIndexes[$validated['stage']];

            abort_if(
                $stageIndex < $furthestStatusIndex,
                409,
                'This assignment is fixed because the order has advanced past this stage.'
            );

            $assignment = BusinessOrderStageAssignment::query()->firstOrNew([
                'order_id' => $lockedOrder->id,
                'stage' => $validated['stage'],
            ]);
            $newStaffId = $validated['assigned_staff_id'] ?? null;

            if ((string) ($assignment->assigned_staff_id ?? '') === (string) ($newStaffId ?? '')) {
                return $assignment->load('assignedStaff');
            }

            $assignment->assigned_staff_id = $newStaffId;
            $assignment->save();
            $assignment->load('assignedStaff');
            $actor = $request->user();

            BusinessOrderStageAssignmentHistory::query()->create([
                'order_id' => $lockedOrder->id,
                'stage' => $assignment->stage,
                'assigned_staff_id' => $assignment->assigned_staff_id,
                'assigned_staff_name' => $assignment->assignedStaff?->name,
                'changed_by' => $actor?->id,
                'changed_by_name' => $actor?->name,
            ]);

            return $assignment;
        });

        return response()->json([
            'stage' => $assignment->stage,
            'stage_label' => BusinessOrder::STATUSES[$assignment->stage],
            'assigned_staff_id' => $assignment->assigned_staff_id,
            'staff_name' => $assignment->assignedStaff?->name,
        ]);
    }

    public function storePayment(Request $request, BusinessOrder $order): RedirectResponse
    {
        $validated = $request->validateWithBag('businessPayment', [
            'submission_token' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'method' => ['required', Rule::in(array_keys(self::PAYMENT_METHODS))],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:1000'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);
        $submissionToken = $validated['submission_token'];

        try {
            $payment = DB::transaction(function () use ($order, $validated, $request, $submissionToken): BusinessPayment|false {
                $lockedOrder = BusinessOrder::query()->lockForUpdate()->findOrFail($order->id);
                $existingPayment = BusinessPayment::query()
                    ->where('submission_token', $submissionToken)
                    ->first();

                if ($existingPayment !== null) {
                    abort_if($existingPayment->order_id !== $lockedOrder->id, 409, 'Payment submission token has already been used.');

                    return $existingPayment;
                }

                $paidAmount = (float) $lockedOrder->payments()->sum('amount');
                $balanceDue = max(round((float) $lockedOrder->total_price - $paidAmount, 2), 0);

                if ((float) $validated['amount'] > $balanceDue) {
                    return false;
                }

                $payment = BusinessPayment::query()->create([
                    'submission_token' => $submissionToken,
                    'order_id' => $lockedOrder->id,
                    'amount' => $validated['amount'],
                    'method' => $validated['method'],
                    'payment_date' => $validated['payment_date'],
                    'reference' => $validated['reference'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'recorded_by' => auth()->id(),
                ]);
                $payment->update([
                    'proof_file_path' => $this->storePrivateFile($request->file('proof'), 'business-payment-proofs'),
                ]);

                return $payment;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $payment = BusinessPayment::query()
                ->where('submission_token', $submissionToken)
                ->first();

            if ($payment === null) {
                throw $exception;
            }

            abort_if($payment->order_id !== $order->id, 409, 'Payment submission token has already been used.');
        }

        if ($payment === false) {
            $balanceDue = max(round((float) $order->fresh()->total_price - (float) $order->payments()->sum('amount'), 2), 0);

            return back()
                ->withErrors(['amount' => 'Payment cannot exceed the remaining balance of PHP (₱) '.number_format($balanceDue, 2).'.'], 'businessPayment')
                ->withInput($request->except('proof'));
        }

        return redirect()
            ->route('admin.business.orders.show', $order)
            ->with('business_saved', $payment->wasRecentlyCreated ? 'Payment recorded.' : 'This payment was already recorded.');
    }

    public function payments(): View
    {
        $outstandingOrders = BusinessOrder::query()
            ->with('customer')
            ->withSum('payments', 'amount')
            ->where('status', '!=', 'cancelled')
            ->orderBy('deadline')
            ->get()
            ->map(function (BusinessOrder $order): BusinessOrder {
                $paid = (float) ($order->payments_sum_amount ?? 0);
                $order->setAttribute('balance_due', max(round((float) $order->total_price - $paid, 2), 0));
                $order->setAttribute('payment_status', $this->paymentStatus((float) $order->total_price, $paid));

                return $order;
            })
            ->filter(fn (BusinessOrder $order): bool => $order->balance_due > 0)
            ->values();

        return view('admin.business.payments.index', [
            'payments' => BusinessPayment::query()
                ->whereHas('order')
                ->with('order.customer')
                ->latest('payment_date')
                ->paginate(25),
            'outstandingOrders' => $outstandingOrders,
            'noIndex' => true,
        ]);
    }

    public function paymentProof(Request $request, BusinessPayment $payment): View
    {
        $path = $this->paymentProofPath($payment);
        $returnUrl = $request->query('from') === 'order'
            ? route('admin.business.orders.show', $payment->order_id)
            : route('admin.business.payments.index');

        return view('admin.attachments.preview', [
            'fileName' => basename($path),
            'mimeType' => Storage::disk('local')->mimeType($path) ?: 'application/octet-stream',
            'contentUrl' => route('admin.business.payments.proof.content', $payment),
            'downloadUrl' => route('admin.business.payments.proof.download', $payment),
            'returnUrl' => $returnUrl,
            'noIndex' => true,
        ]);
    }

    public function paymentProofContent(BusinessPayment $payment): Response
    {
        return Storage::disk('local')->response($this->paymentProofPath($payment), headers: [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function downloadPaymentProof(BusinessPayment $payment): StreamedResponse
    {
        return Storage::disk('local')->download($this->paymentProofPath($payment));
    }

    public function orderReference(BusinessOrder $order, int $fileIndex): Response
    {
        $path = $this->orderReferencePath($order, $fileIndex);

        return Storage::disk('local')->response($path, headers: [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function orderReferencePreview(BusinessOrder $order, int $fileIndex): View
    {
        $path = $this->orderReferencePath($order, $fileIndex);

        return view('admin.attachments.preview', [
            'fileName' => basename($path),
            'mimeType' => Storage::disk('local')->mimeType($path) ?: 'application/octet-stream',
            'contentUrl' => route('admin.business.orders.references.show', [$order, $fileIndex]),
            'downloadUrl' => route('admin.business.orders.references.download', [$order, $fileIndex]),
            'returnUrl' => route('admin.business.orders.show', $order),
            'noIndex' => true,
        ]);
    }

    public function downloadOrderReference(BusinessOrder $order, int $fileIndex): StreamedResponse
    {
        return Storage::disk('local')->download($this->orderReferencePath($order, $fileIndex));
    }

    public function storeDocument(Request $request, BusinessOrder $order): RedirectResponse
    {
        $validated = $request->validateWithBag('businessDocument', [
            'type' => ['required', Rule::in(['invoice', 'receipt'])],
            'invoice_number' => [
                'nullable',
                'string',
                'max:100',
                'unique:business_documents,invoice_number',
            ],
            'invoice_document_id' => [
                Rule::requiredIf($request->input('type') === 'receipt'),
                'nullable',
                'integer',
                Rule::exists('business_documents', 'id')->where(
                    fn ($query) => $query
                        ->where('order_id', $order->id)
                        ->where('type', 'invoice')
                        ->whereNotNull('invoice_number')
                ),
            ],
            'payment_id' => [
                Rule::requiredIf($request->input('type') === 'receipt'),
                'nullable',
                'integer',
                Rule::exists('business_payments', 'id')->where('order_id', $order->id),
            ],
            'due_on' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        if ((float) $order->total_price <= 0) {
            return back()
                ->withErrors(['type' => 'Add the order price before issuing an invoice or receipt.'], 'businessDocument')
                ->withInput();
        }

        try {
            $document = DB::transaction(function () use ($order, $validated): BusinessDocument {
                if ($validated['type'] === 'receipt') {
                    $existingReceipt = BusinessDocument::query()
                        ->where('payment_id', $validated['payment_id'])
                        ->first();

                    if ($existingReceipt !== null) {
                        abort_unless($existingReceipt->order_id === $order->id, 409);

                        return $existingReceipt;
                    }
                }

                $invoice = $validated['type'] === 'receipt'
                    ? BusinessDocument::query()
                        ->whereKey($validated['invoice_document_id'])
                        ->where('order_id', $order->id)
                        ->where('type', 'invoice')
                        ->whereNotNull('invoice_number')
                        ->firstOrFail()
                    : null;
                $payment = $validated['type'] === 'receipt'
                    ? BusinessPayment::query()
                        ->whereKey($validated['payment_id'])
                        ->where('order_id', $order->id)
                        ->firstOrFail()
                    : null;
                $documentNumber = strtoupper(substr($validated['type'], 0, 1)).'-'.now()->format('Y').'-'.Str::upper(Str::random(8));
                $invoiceNumber = $validated['type'] === 'invoice'
                    ? (filled($validated['invoice_number'] ?? null) ? trim($validated['invoice_number']) : $documentNumber)
                    : $invoice?->invoice_number;
                $total = (float) $order->total_price;
                $half = number_format(round($total / 2, 2), 2, '.', '');
                $snapshot = [
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer->name,
                    'customer_company' => $order->customer->company,
                    'customer_email' => $order->customer->email,
                    'customer_social_media_link' => $order->customer->social_media_link,
                    'item_name' => $order->item_name,
                    'description' => $order->description,
                    'specifications' => $order->specifications,
                    'total_price' => (string) $order->total_price,
                    'issued_on' => today()->toDateString(),
                    'due_on' => $validated['type'] === 'invoice' ? ($validated['due_on'] ?? null) : null,
                    'invoice_number' => $invoiceNumber,
                    'down_payment' => $half,
                    'balance_after_down_payment' => $half,
                ];

                if ($payment !== null) {
                    $snapshot['payment_amount'] = (string) $payment->amount;
                    $snapshot['payment_method'] = self::PAYMENT_METHODS[$payment->method] ?? $payment->method;
                    $snapshot['payment_date'] = $payment->payment_date->toDateString();
                    $snapshot['payment_reference'] = $payment->reference;
                }

                return BusinessDocument::query()->create([
                    'order_id' => $order->id,
                    'document_number' => $documentNumber,
                    'invoice_number' => $validated['type'] === 'invoice' ? $invoiceNumber : null,
                    'invoice_document_id' => $invoice?->id,
                    'payment_id' => $payment?->id,
                    'type' => $validated['type'],
                    'status' => 'issued',
                    'issued_on' => today(),
                    'due_on' => $validated['type'] === 'invoice' ? ($validated['due_on'] ?? null) : null,
                    'snapshot' => $snapshot,
                    'created_by' => auth()->id(),
                ]);
            });
        } catch (UniqueConstraintViolationException $exception) {
            $providedInvoiceNumber = $validated['invoice_number'] ?? null;

            if ($validated['type'] === 'invoice' && filled($providedInvoiceNumber)) {
                $existingInvoice = BusinessDocument::query()
                    ->where('invoice_number', $providedInvoiceNumber)
                    ->first();

                if ($existingInvoice !== null) {
                    return back()
                        ->withErrors(['invoice_number' => 'This invoice number has already been used.'], 'businessDocument')
                        ->withInput();
                }
            }

            if ($validated['type'] === 'receipt') {
                $existingReceipt = BusinessDocument::query()
                    ->where('payment_id', $validated['payment_id'])
                    ->first();

                if ($existingReceipt !== null) {
                    return redirect()->route('admin.business.documents.show', $existingReceipt);
                }
            }

            throw $exception;
        }

        return redirect()->route('admin.business.documents.show', $document);
    }

    public function documents(): View
    {
        return view('admin.business.documents.index', [
            'documents' => BusinessDocument::query()
                ->whereHas('order')
                ->with('order.customer', 'invoiceDocument', 'payment')
                ->latest('issued_on')
                ->paginate(25),
            'noIndex' => true,
        ]);
    }

    public function showDocument(BusinessDocument $document): View
    {
        abort_unless($document->order()->exists(), 404);
        $document->loadMissing('order', 'invoiceDocument', 'payment');

        return view('admin.business.documents.show', [
            'document' => $document,
            'noIndex' => true,
        ]);
    }

    public function expenses(): View
    {
        $startOfYear = now()->startOfYear()->toDateString();
        $today = now()->toDateString();

        return view('admin.business.expenses.index', [
            'expenses' => BusinessExpense::query()->latest('expense_date')->paginate(25),
            'yearTotal' => (float) BusinessExpense::query()->whereBetween('expense_date', [$startOfYear, $today])->sum('amount'),
            'categories' => self::EXPENSE_CATEGORIES,
            'submissionToken' => (string) Str::uuid(),
            'noIndex' => true,
        ]);
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('businessExpense', [
            'submission_token' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', Rule::in(array_keys(self::EXPENSE_CATEGORIES))],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'expense_date' => ['required', 'date'],
            'recurring' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);
        $submissionToken = $validated['submission_token'];
        $existingExpense = BusinessExpense::withTrashed()->where('submission_token', $submissionToken)->first();

        if ($existingExpense !== null) {
            return redirect()
                ->route('admin.business.expenses.index')
                ->with('business_saved', 'This expense was already recorded.');
        }

        try {
            DB::transaction(function () use ($validated, $request, $submissionToken): void {
                $expense = BusinessExpense::query()->create([
                    'submission_token' => $submissionToken,
                    'title' => $validated['title'],
                    'category' => $validated['category'],
                    'amount' => $validated['amount'],
                    'expense_date' => $validated['expense_date'],
                    'recurring' => $request->boolean('recurring'),
                    'notes' => $validated['notes'] ?? null,
                    'recorded_by' => auth()->id(),
                ]);
                $expense->update([
                    'receipt_file_path' => $this->storePrivateFile($request->file('receipt'), 'business-expense-receipts'),
                ]);
            });
        } catch (UniqueConstraintViolationException $exception) {
            $existingExpense = BusinessExpense::withTrashed()->where('submission_token', $submissionToken)->first();

            if ($existingExpense === null) {
                throw $exception;
            }

            return redirect()
                ->route('admin.business.expenses.index')
                ->with('business_saved', 'This expense was already recorded.');
        }

        return redirect()
            ->route('admin.business.expenses.index')
            ->with('business_saved', 'Expense recorded.');
    }

    public function archive(): View
    {
        return view('admin.business.archive.index', [
            'archivedOrders' => BusinessOrder::onlyTrashed()
                ->with(['customer', 'deletedBy'])
                ->latest('deleted_at')
                ->paginate(25, ['*'], 'orders_page'),
            'archivedExpenses' => BusinessExpense::onlyTrashed()
                ->with('deletedBy')
                ->latest('deleted_at')
                ->paginate(25, ['*'], 'expenses_page'),
            'noIndex' => true,
        ]);
    }

    public function archiveOrder(Request $request, BusinessOrder $order): RedirectResponse
    {
        $validated = $request->validateWithBag('businessOrderArchive', [
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $order->update([
            'deletion_reason' => $validated['reason'],
            'deleted_by' => $request->user()->id,
        ]);
        $order->delete();

        return redirect()
            ->route('admin.business.orders.index')
            ->with('business_saved', 'Order moved to the archive.');
    }

    public function archiveExpense(Request $request, BusinessExpense $expense): RedirectResponse
    {
        $validated = $request->validateWithBag('businessExpenseArchive', [
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $expense->update([
            'deletion_reason' => $validated['reason'],
            'deleted_by' => $request->user()->id,
        ]);
        $expense->delete();

        return redirect()
            ->route('admin.business.expenses.index')
            ->with('business_saved', 'Expense moved to the archive.');
    }

    public function expenseReceipt(BusinessExpense $expense): Response
    {
        abort_unless(
            is_string($expense->receipt_file_path)
                && str_starts_with($expense->receipt_file_path, 'business-expense-receipts/')
                && Storage::disk('local')->exists($expense->receipt_file_path),
            404
        );

        return Storage::disk('local')->response($expense->receipt_file_path, headers: [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function orderRules(bool $forNewOrder = false): array
    {
        $rules = [
            'item_name' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:2000'],
            'specifications' => ['nullable', 'string', 'max:2000'],
            'reference_files' => ['nullable', 'array', 'max:5'],
            'reference_files.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:10240'],
            'order_date' => ['required', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:order_date'],
            'total_price' => ['required', 'numeric', 'min:50', 'max:9999999999.99'],
            'sales_channel' => $forNewOrder
                ? ['required', Rule::in(array_keys(BusinessOrder::SALES_CHANNELS))]
                : ['nullable', Rule::in(array_keys(BusinessOrder::SALES_CHANNELS))],
            'status' => ['required', Rule::in(array_keys(BusinessOrder::STATUSES))],
            'assigned_staff_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
                ),
            ],
            'internal_notes' => ['nullable', 'string', 'max:4000'],
            'shipping_method' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'shipping_status' => ['nullable', Rule::in(['not_shipped', 'in_transit', 'delivered', 'exception'])],
        ];

        if ($forNewOrder) {
            $rules['submission_token'] = ['required', 'uuid'];
        }

        $rules[$forNewOrder ? 'customer_name' : 'customer_id'] = $forNewOrder
            ? ['required', 'string', 'max:150']
            : ['required', 'integer', 'exists:business_customers,id'];

        return $rules;
    }

    private function orderReferencePath(BusinessOrder $order, int $fileIndex): string
    {
        $path = $order->reference_files[$fileIndex] ?? null;

        abort_unless(
            is_string($path)
                && str_starts_with($path, 'business-order-references/')
                && Storage::disk('local')->exists($path),
            404
        );

        return $path;
    }

    private function paymentProofPath(BusinessPayment $payment): string
    {
        abort_unless($payment->order()->exists(), 404);

        $path = $payment->proof_file_path;

        abort_unless(
            is_string($path)
                && str_starts_with($path, 'business-payment-proofs/')
                && Storage::disk('local')->exists($path),
            404
        );

        return $path;
    }

    private function staffUsers()
    {
        return User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
            ->orderBy('name')
            ->get(['id', 'name', 'position']);
    }

    private function newOrderNumber(): string
    {
        return 'AL-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
    }

    private function paymentStatus(float $total, float $paid): string
    {
        if ($total <= 0 && $paid <= 0) {
            return 'Not set';
        }

        if ($total > 0 && $paid >= $total) {
            return 'Paid';
        }

        return $paid > 0 ? 'Partially paid' : 'Unpaid';
    }

    private function storePrivateFile(?UploadedFile $file, string $directory): ?string
    {
        if ($file === null) {
            return null;
        }

        $path = $file->store($directory, 'local');

        if ($path === false) {
            throw new RuntimeException('Failed to store a business record attachment.');
        }

        return $path;
    }

    private function storePrivateFiles(?array $files, string $directory): array
    {
        return collect($files ?? [])
            ->map(fn (UploadedFile $file): ?string => $this->storePrivateFile($file, $directory))
            ->filter()
            ->values()
            ->all();
    }
}

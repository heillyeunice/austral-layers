<?php

namespace Tests\Feature;

use App\Models\BusinessCustomer;
use App\Models\BusinessDocument;
use App\Models\BusinessExpense;
use App\Models\BusinessOrder;
use App\Models\BusinessOrderStageAssignment;
use App\Models\BusinessPayment;
use App\Models\QuoteInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class BusinessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_management_requires_staff_access(): void
    {
        $this->get(route('admin.business.dashboard'))
            ->assertRedirect(route('admin.login'));

        $customerAccount = $this->makeUser(null);
        $this->actingAs($customerAccount)
            ->get(route('admin.business.dashboard'))
            ->assertForbidden();

        $superadmin = $this->makeUser(User::ROLE_SUPERADMIN);
        $this->actingAs($superadmin)
            ->get(route('admin.business.dashboard'))
            ->assertOk();
    }

    public function test_staff_backend_renders_the_theme_toggle(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->get(route('admin.business.dashboard'))
            ->assertOk()
            ->assertSee('class="staff-backend"', false)
            ->assertSee('data-theme="dark"', false)
            ->assertSee('data-staff-theme-toggle', false);
    }

    public function test_admin_dashboard_shows_only_current_ongoing_tasks_assigned_to_them(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $otherAdmin = $this->makeUser(User::ROLE_ADMIN);
        $customer = BusinessCustomer::query()->create(['name' => 'Task Customer']);

        $activeOrder = $this->makeOrder($customer, [
            'item_name' => 'Current stage task',
            'status' => 'modeling',
            'deadline' => today()->addDays(2),
        ]);
        BusinessOrderStageAssignment::query()->create([
            'order_id' => $activeOrder->id,
            'stage' => 'modeling',
            'assigned_staff_id' => $admin->id,
        ]);

        $staleOrder = $this->makeOrder($customer, [
            'item_name' => 'Past stage task',
            'status' => 'production',
        ]);
        BusinessOrderStageAssignment::query()->create([
            'order_id' => $staleOrder->id,
            'stage' => 'modeling',
            'assigned_staff_id' => $admin->id,
        ]);

        $completedOrder = $this->makeOrder($customer, [
            'item_name' => 'Completed task',
            'status' => 'completed',
        ]);
        BusinessOrderStageAssignment::query()->create([
            'order_id' => $completedOrder->id,
            'stage' => 'shipped',
            'assigned_staff_id' => $admin->id,
        ]);

        $cancelledOrder = $this->makeOrder($customer, [
            'item_name' => 'Cancelled task',
            'status' => 'cancelled',
        ]);
        BusinessOrderStageAssignment::query()->create([
            'order_id' => $cancelledOrder->id,
            'stage' => 'modeling',
            'assigned_staff_id' => $admin->id,
        ]);

        $otherAdminOrder = $this->makeOrder($customer, [
            'item_name' => 'Another admin task',
            'status' => 'modeling',
        ]);
        BusinessOrderStageAssignment::query()->create([
            'order_id' => $otherAdminOrder->id,
            'stage' => 'modeling',
            'assigned_staff_id' => $otherAdmin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('My Tasks')
            ->assertSee('Current stage task')
            ->assertSee($activeOrder->order_number)
            ->assertSee('Modeling / Design')
            ->assertDontSee('Past stage task')
            ->assertDontSee('Completed task')
            ->assertDontSee('Cancelled task')
            ->assertDontSee('Another admin task');
    }

    public function test_dashboard_calculates_cash_flow_without_stock_alerts(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = BusinessCustomer::query()->create(['name' => 'Taylor Customer']);
        $order = $this->makeOrder($customer, [
            'status' => 'completed',
            'total_price' => 1000,
        ]);
        BusinessPayment::query()->create([
            'order_id' => $order->id,
            'amount' => 400,
            'method' => 'gcash',
            'payment_date' => today(),
        ]);
        BusinessExpense::query()->create([
            'title' => 'Filament purchase',
            'category' => 'materials',
            'amount' => 100,
            'expense_date' => today(),
        ]);
        $this->assertSame(1, BusinessOrder::query()
            ->where('status', 'completed')
            ->whereDate('order_date', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('order_date', '<=', now()->toDateString())
            ->count());

        $this->actingAs($admin)
            ->get(route('admin.business.dashboard'))
            ->assertOk()
            ->assertSee('₱1,000.00')
            ->assertSee('₱400.00')
            ->assertSee('₱100.00')
            ->assertSee('₱300.00')
            ->assertSee('₱600.00')
            ->assertDontSee('Low-stock items')
            ->assertDontSee('Inventory &amp; stock alerts');
    }

    public function test_expense_submission_token_prevents_duplicate_records_and_archives_with_reason(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $token = (string) Str::uuid();
        $expenseData = [
            'submission_token' => $token,
            'title' => 'Printer repair',
            'category' => 'equipment',
            'amount' => 125,
            'expense_date' => today()->toDateString(),
        ];

        $this->actingAs($admin)
            ->post(route('admin.business.expenses.store'), $expenseData)
            ->assertRedirect(route('admin.business.expenses.index'));
        $this->post(route('admin.business.expenses.store'), $expenseData)
            ->assertRedirect(route('admin.business.expenses.index'))
            ->assertSessionHas('business_saved', 'This expense was already recorded.');

        $this->assertDatabaseCount('business_expenses', 1);
        $expense = BusinessExpense::query()->firstOrFail();
        $this->from(route('admin.business.expenses.index'))
            ->post(route('admin.business.expenses.archive', $expense), ['reason' => ''])
            ->assertRedirect(route('admin.business.expenses.index'))
            ->assertSessionHasErrorsIn('businessExpenseArchive', ['reason']);
        $this->assertDatabaseHas('business_expenses', [
            'id' => $expense->id,
            'deleted_at' => null,
        ]);
        $this->post(route('admin.business.expenses.archive', $expense), [
            'reason' => 'Entered in the wrong category.',
        ])->assertRedirect(route('admin.business.expenses.index'));

        $activeExpenses = $this->get(route('admin.business.expenses.index'))->assertOk();
        $this->assertSame(0, $activeExpenses->viewData('expenses')->total());
        $this->assertSame(0.0, $activeExpenses->viewData('yearTotal'));
        $this->get(route('admin.business.archive.index'))
            ->assertOk()
            ->assertSee('Printer repair')
            ->assertSee('Entered in the wrong category.');
        $this->assertSoftDeleted('business_expenses', [
            'id' => $expense->id,
            'deletion_reason' => 'Entered in the wrong category.',
            'deleted_by' => $admin->id,
        ]);
    }

    public function test_archived_orders_and_related_records_are_excluded_from_active_records_and_dashboard(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = BusinessCustomer::query()->create(['name' => 'Archived Order Customer']);
        $activeOrder = $this->makeOrder($customer, [
            'status' => 'completed',
            'total_price' => 1000,
        ]);
        $archivedOrder = $this->makeOrder($customer, [
            'status' => 'completed',
            'total_price' => 2000,
        ]);
        BusinessPayment::query()->create([
            'order_id' => $activeOrder->id,
            'amount' => 400,
            'method' => 'cash',
            'payment_date' => today(),
        ]);
        BusinessPayment::query()->create([
            'order_id' => $archivedOrder->id,
            'amount' => 600,
            'method' => 'cash',
            'payment_date' => today(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.business.orders.documents.store', $archivedOrder), [
                'type' => 'invoice',
                'invoice_number' => 'INV-ARCHIVE-0001',
            ])
            ->assertRedirect();
        $document = BusinessDocument::query()->firstOrFail();

        $this->from(route('admin.business.orders.show', $archivedOrder))
            ->post(route('admin.business.orders.archive', $archivedOrder), ['reason' => ''])
            ->assertRedirect(route('admin.business.orders.show', $archivedOrder))
            ->assertSessionHasErrorsIn('businessOrderArchive', ['reason']);
        $this->assertDatabaseHas('business_orders', [
            'id' => $archivedOrder->id,
            'deleted_at' => null,
        ]);
        $this->post(route('admin.business.orders.archive', $archivedOrder), [
            'reason' => 'Duplicate order.',
        ])->assertRedirect(route('admin.business.orders.index'));

        $this->get(route('admin.business.dashboard'))
            ->assertOk()
            ->assertSee('₱1,000.00')
            ->assertSee('₱400.00')
            ->assertDontSee('₱2,000.00');
        $payments = $this->get(route('admin.business.payments.index'))->assertOk();
        $this->assertSame(1, $payments->viewData('payments')->total());
        $documents = $this->get(route('admin.business.documents.index'))->assertOk();
        $this->assertSame(0, $documents->viewData('documents')->total());
        $this->get(route('admin.business.documents.show', $document))->assertNotFound();
        $this->get(route('admin.business.orders.show', $archivedOrder))->assertNotFound();
        $this->get(route('admin.business.archive.index'))
            ->assertOk()
            ->assertSee('Archived Order Customer')
            ->assertSee('Duplicate order.');
        $this->assertSoftDeleted('business_orders', [
            'id' => $archivedOrder->id,
            'deletion_reason' => 'Duplicate order.',
            'deleted_by' => $admin->id,
        ]);
    }

    public function test_payment_proof_is_required_when_recording_a_payment(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $order = $this->makeOrder(BusinessCustomer::query()->create(['name' => 'Proof Customer']), [
            'total_price' => 300,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.business.orders.show', $order))
            ->post(route('admin.business.orders.payments.store', $order), [
                'submission_token' => (string) Str::uuid(),
                'amount' => 100,
                'method' => 'cash',
                'payment_date' => today()->toDateString(),
            ])
            ->assertRedirect(route('admin.business.orders.show', $order))
            ->assertSessionHasErrorsIn('businessPayment', ['proof']);

        $this->assertDatabaseCount('business_payments', 0);
        $this->get(route('admin.business.orders.show', $order))
            ->assertOk()
            ->assertSee('name="proof" accept=".jpg,.jpeg,.png,.pdf" required', false);
    }

    public function test_retrying_payment_submission_with_the_same_token_records_only_once(): void
    {
        Storage::fake('local');
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $order = $this->makeOrder(BusinessCustomer::query()->create(['name' => 'Payment Retry Customer']), [
            'total_price' => 1000,
        ]);
        $submissionToken = (string) Str::uuid();
        $paymentData = [
            'submission_token' => $submissionToken,
            'amount' => 500,
            'method' => 'bank_transfer',
            'payment_date' => today()->toDateString(),
            'reference' => 'PAYMENT-RETRY-1',
            'proof' => UploadedFile::fake()->createWithContent('proof.pdf', '%PDF-1.4 test proof'),
        ];

        $this->actingAs($admin)
            ->post(route('admin.business.orders.payments.store', $order), $paymentData)
            ->assertRedirect(route('admin.business.orders.show', $order))
            ->assertSessionHas('business_saved', 'Payment recorded.');
        $this->post(route('admin.business.orders.payments.store', $order), [
            ...$paymentData,
            'proof' => UploadedFile::fake()->createWithContent('proof.pdf', '%PDF-1.4 test proof'),
        ])
            ->assertRedirect(route('admin.business.orders.show', $order))
            ->assertSessionHas('business_saved', 'This payment was already recorded.');

        $this->assertDatabaseCount('business_payments', 1);
        $this->assertSame(1, count(Storage::disk('local')->allFiles('business-payment-proofs')));
        $this->assertSame(500.0, (float) $order->payments()->sum('amount'));
    }

    public function test_quote_inquiry_can_be_converted_into_an_unpriced_linked_order(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $inquiry = QuoteInquiry::query()->create([
            'name' => 'Jordan Customer',
            'email' => 'jordan@example.com',
            'company' => 'Jordan Studio',
            'social_media_link' => 'https://instagram.com/jordan',
            'service' => 'Custom 3D print',
            'quantity' => '2 pieces',
            'message' => 'A custom desk model.',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inquiries.show', $inquiry))
            ->assertOk()
            ->assertDontSee('Initial order total')
            ->assertSee('Convert to business order');

        $response = $this->post(route('admin.business.orders.from-inquiry', $inquiry));
        $response->assertRedirect()
            ->assertSessionHasNoErrors();

        $order = BusinessOrder::query()->firstOrFail();
        $response->assertRedirect(route('admin.business.orders.show', $order));
        $this->assertDatabaseHas('business_customers', [
            'email' => 'jordan@example.com',
            'social_media_link' => 'https://instagram.com/jordan',
        ]);
        $this->assertDatabaseHas('business_orders', [
            'id' => $order->id,
            'quote_inquiry_id' => $inquiry->id,
            'item_name' => 'Custom 3D print',
            'total_price' => '0.00',
            'sales_channel' => 'website',
            'status' => 'pending_confirmation',
        ]);

        $this->get(route('admin.business.orders.show', $order))
            ->assertOk()
            ->assertSee('Order total')
            ->assertSee('Not set')
            ->assertSee('Set the agreed total in Order details before recording payments.')
            ->assertDontSee('name="amount"', false);

        $this->post(route('admin.business.orders.from-inquiry', $inquiry))
            ->assertRedirect(route('admin.business.orders.show', $order));

        $this->assertDatabaseCount('business_orders', 1);
    }

    public function test_customer_shipping_address_can_be_created_and_updated(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->post(route('admin.business.customers.store'), [
                'name' => 'Taylor Customer',
                'shipping_address' => '12 Layer Lane, Manila',
            ])
            ->assertRedirect();

        $customer = BusinessCustomer::query()->firstOrFail();
        $this->assertSame('12 Layer Lane, Manila', $customer->shipping_address);

        $this->put(route('admin.business.customers.update', $customer), [
            'name' => $customer->name,
            'shipping_address' => '34 Filament Road, Quezon City',
        ])->assertRedirect();

        $this->assertSame('34 Filament Road, Quezon City', $customer->fresh()->shipping_address);
    }

    public function test_orders_can_create_customers_by_name_and_suggest_existing_customers(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $existingCustomer = BusinessCustomer::query()->create(['name' => 'Casey Customer']);

        $this->actingAs($admin)
            ->get(route('admin.business.orders.create'))
            ->assertOk()
            ->assertSee('list="business-customer-suggestions"', false)
            ->assertSee('<option value="Casey Customer">', false)
            ->assertSee('TikTok')
            ->assertSee('Facebook')
            ->assertSee('Instagram')
            ->assertSee('Lazada')
            ->assertSee('Shopee');

        $this->post(route('admin.business.orders.store'), [
            'submission_token' => (string) Str::uuid(),
            'customer_name' => 'New customer',
            'item_name' => 'Custom display',
            'description' => 'A custom display.',
            'order_date' => today()->toDateString(),
            'total_price' => 750,
            'sales_channel' => 'tiktok',
            'status' => 'new_order',
        ])->assertRedirect();

        $newOrder = BusinessOrder::query()->latest('id')->firstOrFail();
        $this->assertSame('New customer', $newOrder->customer->name);
        $this->assertSame('tiktok', $newOrder->sales_channel);
        $this->get(route('admin.business.orders.show', $newOrder))
            ->assertOk()
            ->assertSee(route('admin.business.customers.show', $newOrder->customer), false);

        $this->post(route('admin.business.orders.store'), [
            'submission_token' => (string) Str::uuid(),
            'customer_name' => 'casey customer',
            'item_name' => 'Key holder',
            'description' => 'A custom key holder.',
            'order_date' => today()->toDateString(),
            'total_price' => 250,
            'sales_channel' => 'facebook',
            'status' => 'new_order',
        ])->assertRedirect();

        $existingOrder = BusinessOrder::query()->latest('id')->firstOrFail();
        $this->assertSame($existingCustomer->id, $existingOrder->customer_id);
        $this->assertDatabaseCount('business_customers', 2);
    }

    public function test_retrying_order_creation_does_not_duplicate_an_order_with_multiple_reference_files(): void
    {
        Storage::fake('local');
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $submissionToken = (string) Str::uuid();
        $payload = [
            'submission_token' => $submissionToken,
            'customer_name' => 'Morgan Customer',
            'item_name' => 'Custom display',
            'description' => 'A custom display.',
            'order_date' => today()->toDateString(),
            'total_price' => 750,
            'sales_channel' => 'tiktok',
            'status' => 'new_order',
            'reference_files' => [
                UploadedFile::fake()->createWithContent('front.pdf', '%PDF-1.4 front'),
                UploadedFile::fake()->createWithContent('side.pdf', '%PDF-1.4 side'),
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.business.orders.store'), $payload)
            ->assertRedirect();

        $order = BusinessOrder::query()->firstOrFail();
        $this->post(route('admin.business.orders.store'), [
            ...$payload,
            'reference_files' => [
                UploadedFile::fake()->createWithContent('front.pdf', '%PDF-1.4 front'),
                UploadedFile::fake()->createWithContent('side.pdf', '%PDF-1.4 side'),
            ],
        ])
            ->assertRedirect(route('admin.business.orders.show', $order))
            ->assertSessionHas('business_saved', 'This order was already submitted.');

        $this->assertDatabaseCount('business_orders', 1);
        $this->assertCount(2, $order->fresh()->reference_files);
        $this->assertCount(2, Storage::disk('local')->allFiles('business-order-references'));
    }

    public function test_order_total_must_be_at_least_fifty_when_creating(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = BusinessCustomer::query()->create(['name' => 'Alex Customer']);
        $createUrl = route('admin.business.orders.create');

        $this->actingAs($admin)
            ->from($createUrl)
            ->post(route('admin.business.orders.store'), [
                'submission_token' => (string) Str::uuid(),
                'customer_name' => $customer->name,
                'item_name' => 'Small print',
                'description' => 'A small print.',
                'order_date' => today()->toDateString(),
                'total_price' => 49.99,
                'sales_channel' => 'instagram',
                'status' => 'new_order',
            ])
            ->assertRedirect($createUrl)
            ->assertSessionHasErrorsIn('businessOrder', ['total_price']);
        $this->assertDatabaseCount('business_orders', 0);
    }

    public function test_order_creation_details_are_read_only_after_creation(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $originalOwner = $this->makeUser(User::ROLE_ADMIN);
        $replacementOwner = $this->makeUser(User::ROLE_ADMIN);
        $originalCustomer = BusinessCustomer::query()->create(['name' => 'Original Customer']);
        $replacementCustomer = BusinessCustomer::query()->create(['name' => 'Replacement Customer']);
        $order = $this->makeOrder($originalCustomer, [
            'item_name' => 'Original item',
            'description' => 'Original description.',
            'specifications' => 'Original specifications.',
            'order_date' => today()->subDay(),
            'deadline' => today()->addDays(10),
            'total_price' => 250,
            'sales_channel' => 'facebook',
            'assigned_staff_id' => $originalOwner->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.business.orders.update', $order), [
                'item_name' => 'Changed item',
                'customer_id' => $replacementCustomer->id,
                'description' => 'Changed description.',
                'specifications' => 'Changed specifications.',
                'order_date' => today()->toDateString(),
                'deadline' => today()->addDays(20)->toDateString(),
                'total_price' => 999,
                'sales_channel' => 'tiktok',
                'assigned_staff_id' => $replacementOwner->id,
                'status' => 'production',
                'shipping_status' => 'in_transit',
                'shipping_method' => 'Courier',
                'tracking_number' => 'TRACK-123',
                'internal_notes' => 'Updated operational note.',
            ])
            ->assertRedirect(route('admin.business.orders.show', $order))
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('Original item', $order->item_name);
        $this->assertSame($originalCustomer->id, $order->customer_id);
        $this->assertSame('Original description.', $order->description);
        $this->assertSame('Original specifications.', $order->specifications);
        $this->assertSame(today()->subDay()->toDateString(), $order->order_date->toDateString());
        $this->assertSame(today()->addDays(10)->toDateString(), $order->deadline->toDateString());
        $this->assertSame('250.00', $order->total_price);
        $this->assertSame('facebook', $order->sales_channel);
        $this->assertSame($originalOwner->id, $order->assigned_staff_id);
        $this->assertSame('production', $order->status);
        $this->assertSame('in_transit', $order->shipping_status);

        $this->get(route('admin.business.orders.show', $order))
            ->assertOk()
            ->assertSee('Original item')
            ->assertSee('Original description.')
            ->assertDontSee('name="item_name"', false)
            ->assertDontSee('name="customer_id"', false)
            ->assertDontSee('name="order_date"', false)
            ->assertDontSee('name="deadline"', false)
            ->assertDontSee('name="total_price"', false)
            ->assertDontSee('name="sales_channel"', false)
            ->assertDontSee('name="assigned_staff_id"', false)
            ->assertDontSee('<textarea name="description"', false)
            ->assertDontSee('name="specifications"', false);
    }

    public function test_order_list_filters_all_in_progress_and_by_sales_channel(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = BusinessCustomer::query()->create(['name' => 'Jordan Customer']);
        $this->makeOrder($customer, [
            'item_name' => 'Active TikTok order',
            'status' => 'production',
            'sales_channel' => 'tiktok',
        ]);
        $this->makeOrder($customer, [
            'item_name' => 'Completed Facebook order',
            'status' => 'completed',
            'sales_channel' => 'facebook',
        ]);
        $this->makeOrder($customer, [
            'item_name' => 'Cancelled Shopee order',
            'status' => 'cancelled',
            'sales_channel' => 'shopee',
        ]);

        $this->actingAs($admin);

        $inProgress = $this->get(route('admin.business.orders.index', ['status' => 'in_progress']));
        $inProgress->assertOk()
            ->assertSee('All orders in progress')
            ->assertSee('Active TikTok order')
            ->assertDontSee('Completed Facebook order')
            ->assertDontSee('Cancelled Shopee order');
        $this->assertSame(1, $inProgress->viewData('orders')->total());

        $facebookOrders = $this->get(route('admin.business.orders.index', ['channel' => 'facebook']));
        $facebookOrders->assertOk()
            ->assertSee('Where ordered')
            ->assertSee('Completed Facebook order')
            ->assertSee('Facebook');
        $this->assertSame(1, $facebookOrders->viewData('orders')->total());
    }

    public function test_payment_cannot_make_an_order_overpaid(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $order = $this->makeOrder(BusinessCustomer::query()->create(['name' => 'Casey Customer']), [
            'total_price' => 300,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.business.orders.payments.store', $order), [
                'submission_token' => (string) Str::uuid(),
                'amount' => 100,
                'method' => 'gcash',
                'payment_date' => today()->toDateString(),
                'proof' => UploadedFile::fake()->create('payment.pdf', 12, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->from(route('admin.business.orders.show', $order))
            ->post(route('admin.business.orders.payments.store', $order), [
                'submission_token' => (string) Str::uuid(),
                'amount' => 250,
                'method' => 'cash',
                'payment_date' => today()->toDateString(),
                'proof' => UploadedFile::fake()->create('payment.pdf', 12, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.business.orders.show', $order))
            ->assertSessionHasErrorsIn('businessPayment', ['amount']);

        $this->assertDatabaseCount('business_payments', 1);
        $this->assertSame('300.00', $order->fresh()->total_price);
    }

    public function test_payment_proof_preview_has_a_close_link_back_to_its_source(): void
    {
        Storage::fake('local');
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $order = $this->makeOrder(
            BusinessCustomer::query()->create(['name' => 'Payment Customer']),
            ['total_price' => 500]
        );
        $proof = UploadedFile::fake()->createWithContent(
            'payment.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/r3cAAAAASUVORK5CYII=')
        );
        $proofPath = $proof->store('business-payment-proofs', 'local');
        $payment = BusinessPayment::query()->create([
            'order_id' => $order->id,
            'amount' => 100,
            'method' => 'cash',
            'payment_date' => today(),
            'proof_file_path' => $proofPath,
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.business.payments.proof', [$payment, 'from' => 'order']))
            ->assertOk()
            ->assertSee('aria-label="Close attachment preview"', false)
            ->assertSee('href="'.route('admin.business.orders.show', $order).'"', false)
            ->assertSee('<img ', false);

        $this->get(route('admin.business.payments.proof', $payment))
            ->assertOk()
            ->assertSee('href="'.route('admin.business.payments.index').'"', false);
    }

    public function test_kanban_status_move_records_order_history(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $order = $this->makeOrder(BusinessCustomer::query()->create(['name' => 'Robin Customer']));

        $this->actingAs($admin)
            ->patchJson(route('admin.business.orders.status', $order), [
                'status' => 'production',
            ])
            ->assertOk()
            ->assertExactJson([
                'status' => 'production',
                'label' => 'Production',
            ]);

        $this->assertSame('production', $order->fresh()->status);
        $this->assertDatabaseHas('business_order_status_history', [
            'order_id' => $order->id,
            'from_status' => 'new_order',
            'to_status' => 'production',
        ]);
    }

    public function test_order_tracker_saves_a_different_staff_assignment_for_each_stage(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $modeler = $this->makeUser(User::ROLE_ADMIN);
        $producer = $this->makeUser(User::ROLE_ADMIN);
        $order = $this->makeOrder(BusinessCustomer::query()->create(['name' => 'Robin Customer']));

        $this->actingAs($admin)
            ->get(route('admin.business.orders.index'))
            ->assertOk()
            ->assertSee('data-stage-assignee-select', false)
            ->assertSee('data-stage="modeling"', false)
            ->assertSee('data-stage="production"', false)
            ->assertDontSee('data-stage="new_order"', false)
            ->assertDontSee('data-stage="pending_confirmation"', false);

        $this->patchJson(route('admin.business.orders.stage-assignment', $order), [
            'stage' => 'modeling',
            'assigned_staff_id' => $modeler->id,
        ])
            ->assertOk()
            ->assertJson([
                'stage' => 'modeling',
                'stage_label' => 'Modeling / Design',
                'assigned_staff_id' => $modeler->id,
                'staff_name' => $modeler->name,
            ]);

        $this->patchJson(route('admin.business.orders.stage-assignment', $order), [
            'stage' => 'production',
            'assigned_staff_id' => $producer->id,
        ])->assertOk();

        $this->assertDatabaseCount('business_order_stage_assignments', 2);
        $this->assertDatabaseHas('business_order_stage_assignments', [
            'order_id' => $order->id,
            'stage' => 'modeling',
            'assigned_staff_id' => $modeler->id,
        ]);
        $this->assertDatabaseHas('business_order_stage_assignments', [
            'order_id' => $order->id,
            'stage' => 'production',
            'assigned_staff_id' => $producer->id,
        ]);

        $this->patchJson(route('admin.business.orders.stage-assignment', $order), [
            'stage' => 'modeling',
            'assigned_staff_id' => $producer->id,
        ])->assertOk();

        $this->assertDatabaseCount('business_order_stage_assignments', 2);
        $this->assertDatabaseHas('business_order_stage_assignments', [
            'order_id' => $order->id,
            'stage' => 'modeling',
            'assigned_staff_id' => $producer->id,
        ]);

        $this->patchJson(route('admin.business.orders.status', $order), [
            'status' => 'production',
        ])->assertOk();

        $this->patchJson(route('admin.business.orders.stage-assignment', $order), [
            'stage' => 'modeling',
            'assigned_staff_id' => $admin->id,
        ])
            ->assertStatus(409)
            ->assertJson([
                'message' => 'This assignment is fixed because the order has advanced past this stage.',
            ]);

        $this->patchJson(route('admin.business.orders.stage-assignment', $order), [
            'stage' => 'new_order',
            'assigned_staff_id' => $admin->id,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('business_order_stage_assignment_history', 3);
        $this->assertDatabaseHas('business_order_stage_assignment_history', [
            'order_id' => $order->id,
            'stage' => 'modeling',
            'assigned_staff_name' => $modeler->name,
            'changed_by' => $admin->id,
            'changed_by_name' => $admin->name,
        ]);
        $this->assertDatabaseHas('business_order_stage_assignment_history', [
            'order_id' => $order->id,
            'stage' => 'modeling',
            'assigned_staff_name' => $producer->name,
            'changed_by' => $admin->id,
        ]);
        $this->get(route('admin.business.orders.show', $order))
            ->assertOk()
            ->assertSee('Production assignment history')
            ->assertSee($modeler->name)
            ->assertSee($producer->name);
    }

    public function test_completed_and_cancelled_orders_stay_in_order_list_not_production_board(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = BusinessCustomer::query()->create(['name' => 'Robin Customer']);
        $activeOrder = $this->makeOrder($customer, [
            'item_name' => 'Active model',
            'status' => 'production',
        ]);
        $completedOrder = $this->makeOrder($customer, [
            'item_name' => 'Completed model',
            'status' => 'completed',
        ]);
        $cancelledOrder = $this->makeOrder($customer, [
            'item_name' => 'Cancelled model',
            'status' => 'cancelled',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.business.orders.index'))
            ->assertOk()
            ->assertSee('Production tracking')
            ->assertSee('Order list')
            ->assertSee('data-order-url="'.route('admin.business.orders.show', $activeOrder).'"', false)
            ->assertSee('data-status-target="production"', false)
            ->assertSee('data-progress-step="production"', false)
            ->assertSee('draggable="true"', false)
            ->assertDontSee('data-order-url="'.route('admin.business.orders.show', $completedOrder).'"', false)
            ->assertDontSee('data-order-url="'.route('admin.business.orders.show', $cancelledOrder).'"', false)
            ->assertDontSee('data-status-column="completed"', false)
            ->assertDontSee('data-status-column="cancelled"', false)
            ->assertSee('Active model')
            ->assertSee('Completed model')
            ->assertSee('Cancelled model');
    }

    public function test_empty_production_queue_keeps_stage_tracking_without_an_empty_state_message(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = BusinessCustomer::query()->create(['name' => 'Robin Customer']);
        $this->makeOrder($customer, [
            'item_name' => 'Completed model',
            'status' => 'completed',
        ]);
        $this->makeOrder($customer, [
            'item_name' => 'Cancelled model',
            'status' => 'cancelled',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.business.orders.index'))
            ->assertOk()
            ->assertSee('Production tracking')
            ->assertSee('data-status-target="new_order"', false)
            ->assertSee('data-status-target="ready_for_shipping"', false)
            ->assertDontSee('data-order-card', false)
            ->assertDontSee('No active orders are currently in production.')
            ->assertSee('Completed model')
            ->assertSee('Cancelled model')
            ->assertDontSee('data-order-kanban', false);
    }

    public function test_documents_keep_a_snapshot(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = BusinessCustomer::query()->create([
            'name' => 'Morgan Customer',
            'email' => 'morgan@example.com',
        ]);
        $order = $this->makeOrder($customer, ['total_price' => 500]);

        $this->actingAs($admin)
            ->post(route('admin.business.orders.documents.store', $order), [
                'type' => 'invoice',
                'invoice_number' => 'INV-2026-00001',
            ])
            ->assertRedirect();

        $document = BusinessDocument::query()->firstOrFail();
        $this->assertSame('Morgan Customer', $document->snapshot['customer_name']);
        $this->assertSame('500.00', $document->snapshot['total_price']);
        $this->assertSame('INV-2026-00001', $document->invoice_number);
        $this->assertSame('250.00', $document->snapshot['down_payment']);
        $this->get(route('admin.business.documents.show', $document))
            ->assertOk()
            ->assertSee('Morgan Customer')
            ->assertSee('INVOICE NO. INV-2026-00001')
            ->assertSee('ORDER SUMMARY')
            ->assertSee('50% Down Payment')
            ->assertSee('₱500.00')
            ->assertDontSee('value="quotation"', false);
        $this->get(route('admin.business.orders.show', $order))
            ->assertOk()
            ->assertSee('data-document-type-switch', false)
            ->assertSee('value="invoice"', false)
            ->assertSee('value="receipt"', false)
            ->assertSee('Invoice number')
            ->assertSee('Leave blank to auto-generate a number.');
    }

    public function test_receipt_uses_its_payment_and_invoice_number_and_can_only_be_issued_once_per_payment(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = BusinessCustomer::query()->create(['name' => 'Receipt Customer']);
        $order = $this->makeOrder($customer, [
            'item_name' => 'Wall Mounted Automotive Key Holder',
            'description' => "Quantity: 1\nColor: Red",
            'total_price' => 1000,
        ]);
        $payment = BusinessPayment::query()->create([
            'order_id' => $order->id,
            'amount' => 500,
            'method' => 'gcash',
            'payment_date' => today(),
            'reference' => 'TX-12345',
            'proof_file_path' => 'business-payment-proofs/proof.png',
        ]);

        $invoiceResponse = $this->actingAs($admin)
            ->post(route('admin.business.orders.documents.store', $order), ['type' => 'invoice']);
        $invoiceResponse->assertRedirect();
        $invoice = BusinessDocument::query()->where('type', 'invoice')->firstOrFail();
        $this->assertSame($invoice->document_number, $invoice->invoice_number);
        $this->assertStringStartsWith('I-', $invoice->invoice_number);

        $receiptResponse = $this->post(route('admin.business.orders.documents.store', $order), [
            'type' => 'invoice',
            'invoice_number' => 'INV-RECEIPT-0001',
        ]);
        $manualInvoice = BusinessDocument::query()->where('invoice_number', 'INV-RECEIPT-0001')->firstOrFail();
        $receiptResponse->assertRedirect(route('admin.business.documents.show', $manualInvoice));

        $receiptResponse = $this->post(route('admin.business.orders.documents.store', $order), [
            'type' => 'receipt',
            'invoice_document_id' => $manualInvoice->id,
            'payment_id' => $payment->id,
        ]);
        $receipt = BusinessDocument::query()->where('type', 'receipt')->firstOrFail();
        $receiptResponse->assertRedirect(route('admin.business.documents.show', $receipt));

        $this->assertSame($manualInvoice->id, $receipt->invoice_document_id);
        $this->assertSame($payment->id, $receipt->payment_id);
        $this->assertSame('INV-RECEIPT-0001', $receipt->snapshot['invoice_number']);
        $this->assertSame('500.00', $receipt->snapshot['payment_amount']);
        $this->get(route('admin.business.documents.show', $receipt))
            ->assertOk()
            ->assertSee('business-document-receipt', false)
            ->assertSee('INV-RECEIPT-0001')
            ->assertSee('AMOUNT RECEIVED')
            ->assertSee('₱500.00')
            ->assertSee('TX-12345')
            ->assertSee('Print receipt');

        $this->post(route('admin.business.orders.documents.store', $order), [
            'type' => 'receipt',
            'invoice_document_id' => $manualInvoice->id,
            'payment_id' => $payment->id,
        ])->assertRedirect(route('admin.business.documents.show', $receipt));
        $this->assertSame(1, BusinessDocument::query()->where('payment_id', $payment->id)->count());
    }

    public function test_invoice_numbers_cannot_be_reused(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = BusinessCustomer::query()->create(['name' => 'Invoice Customer']);
        $firstOrder = $this->makeOrder($customer, ['total_price' => 500]);
        $secondOrder = $this->makeOrder($customer, ['total_price' => 750]);

        $this->actingAs($admin)
            ->post(route('admin.business.orders.documents.store', $firstOrder), [
                'type' => 'invoice',
                'invoice_number' => 'INV-UNIQUE-0001',
            ])->assertRedirect();
        $this->post(route('admin.business.orders.documents.store', $secondOrder), [
            'type' => 'invoice',
            'invoice_number' => 'INV-UNIQUE-0001',
        ])->assertSessionHasErrorsIn('businessDocument', ['invoice_number']);

        $this->assertSame(1, BusinessDocument::query()->where('invoice_number', 'INV-UNIQUE-0001')->count());
    }

    public function test_business_screens_render_and_order_references_are_private(): void
    {
        Storage::fake('local');
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $customer = BusinessCustomer::query()->create(['name' => 'Jamie Customer']);

        $this->actingAs($admin)
            ->post(route('admin.business.orders.store'), [
                'submission_token' => (string) Str::uuid(),
                'customer_name' => $customer->name,
                'item_name' => 'Display model',
                'description' => 'A custom display model.',
                'order_date' => today()->toDateString(),
                'total_price' => 750,
                'sales_channel' => 'instagram',
                'status' => 'new_order',
                'reference_files' => [
                    UploadedFile::fake()->createWithContent('reference.pdf', '%PDF-1.4 test'),
                    UploadedFile::fake()->createWithContent(
                        'reference.png',
                        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/r3cAAAAASUVORK5CYII=')
                    ),
                ],
            ])
            ->assertRedirect();

        $order = BusinessOrder::query()->firstOrFail();
        $referencePath = $order->reference_files[0];
        Storage::disk('local')->assertExists($referencePath);

        auth()->logout();
        $this->get(route('admin.business.orders.references.show', [$order, 0]))
            ->assertRedirect(route('admin.login'));

        $this->actingAs($admin)
            ->get(route('admin.business.orders.references.show', [$order, 0]))
            ->assertOk();

        $this->get(route('admin.business.orders.references.preview', [$order, 0]))
            ->assertOk()
            ->assertSee('aria-label="Close attachment preview"', false)
            ->assertSee('href="'.route('admin.business.orders.show', $order).'"', false)
            ->assertSee('Download')
            ->assertSee('<iframe', false);

        $this->get(route('admin.business.orders.references.preview', [$order, 1]))
            ->assertOk()
            ->assertSee('aria-label="Close attachment preview"', false)
            ->assertSee('href="'.route('admin.business.orders.show', $order).'"', false)
            ->assertSee('<img ', false);

        foreach ([
            route('admin.business.customers.index'),
            route('admin.business.customers.show', $customer),
            route('admin.business.orders.index'),
            route('admin.business.orders.show', $order),
            route('admin.business.documents.index'),
            route('admin.business.expenses.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('admin.business.orders.index'))
            ->assertOk()
            ->assertSee('data-order-url="'.route('admin.business.orders.show', $order).'"', false)
            ->assertSee('Instagram');
        $this->get('/admin/business/inventory')->assertNotFound();
        $this->get(route('admin.business.payments.index'))
            ->assertOk()
            ->assertSee('PHP (₱) 750.00')
            ->assertSee('Unpaid')
            ->assertSee('Balance due');
    }

    private function makeUser(?string $role): User
    {
        $user = new User;
        $user->forceFill([
            'name' => ucfirst($role ?? 'regular').' User',
            'email' => ($role ?? 'regular').'-'.uniqid().'@example.com',
            'password' => 'password',
            'role' => $role,
        ])->save();

        return $user;
    }

    private function makeOrder(BusinessCustomer $customer, array $attributes = []): BusinessOrder
    {
        return BusinessOrder::query()->create([
            'order_number' => 'AL-'.strtoupper(bin2hex(random_bytes(5))),
            'customer_id' => $customer->id,
            'item_name' => 'Custom print',
            'description' => 'Test order description.',
            'order_date' => today(),
            'total_price' => 200,
            'status' => 'new_order',
            ...$attributes,
        ]);
    }
}

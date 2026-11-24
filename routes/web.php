<?php

use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\BusinessManagementController;
use App\Http\Controllers\BusinessReportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PortfolioManagementController;
use App\Http\Controllers\PrintCalculatorController;
use App\Http\Controllers\PrinterPresetController;
use App\Http\Controllers\ProductManagementController;
use App\Http\Controllers\QuoteInquiryManagementController;
use App\Http\Controllers\SiteSettingsController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffNotificationController;
use App\Http\Controllers\StaffProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/portfolio', [HomeController::class, 'portfolio'])->name('portfolio');
Route::get('/portfolio/{slug}', [HomeController::class, 'portfolioProject'])->name('portfolio.show');
Route::get('/products', [HomeController::class, 'products'])->name('products.index');
Route::get('/products/{slug}', [HomeController::class, 'product'])->name('products.show');
Route::post('/quote', [HomeController::class, 'quote'])->name('quote.store');

Route::middleware(['auth', 'role:admin,superadmin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/business', fn () => redirect()->route('admin.business.dashboard'))->name('business.index');
    Route::get('/business/reports', [BusinessReportController::class, 'index'])->name('business.reports.index');
    Route::get('/business/reports/export', [BusinessReportController::class, 'export'])->name('business.reports.export');
    Route::prefix('business/print-calculator')->name('business.print-calculator.')->group(function (): void {
        Route::get('/', [PrintCalculatorController::class, 'index'])->name('index');
        Route::get('/quotation/{printQuotation}/download', [PrintCalculatorController::class, 'download'])->name('quotation.download');
        Route::post('/quotation', [PrintCalculatorController::class, 'generate'])->name('quotation');
        Route::post('/order', [PrintCalculatorController::class, 'prepareOrder'])->name('order');
        Route::get('/printers', [PrinterPresetController::class, 'index'])->name('printers');
        Route::post('/printers', [PrinterPresetController::class, 'store'])->name('printers.store');
        Route::put('/printers/{printerPreset}', [PrinterPresetController::class, 'update'])->name('printers.update');
        Route::delete('/printers/{printerPreset}', [PrinterPresetController::class, 'destroy'])->name('printers.destroy');
    });
    Route::prefix('business')->name('business.')->controller(BusinessManagementController::class)->group(function (): void {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('/archive', 'archive')->name('archive.index');

        Route::get('/customers', 'customers')->name('customers.index');
        Route::post('/customers', 'storeCustomer')->name('customers.store');
        Route::get('/customers/{customer}', 'showCustomer')->name('customers.show');
        Route::put('/customers/{customer}', 'updateCustomer')->name('customers.update');

        Route::get('/orders', 'orders')->name('orders.index');
        Route::get('/orders/create', 'createOrder')->name('orders.create');
        Route::post('/orders', 'storeOrder')->name('orders.store');
        Route::post('/orders/from-inquiry/{inquiry}', 'createFromInquiry')->name('orders.from-inquiry');
        Route::post('/orders/{order}/archive', 'archiveOrder')->name('orders.archive');
        Route::get('/orders/{order}', 'showOrder')->name('orders.show');
        Route::put('/orders/{order}', 'updateOrder')->name('orders.update');
        Route::patch('/orders/{order}/status', 'moveOrder')->name('orders.status');
        Route::patch('/orders/{order}/stage-assignment', 'updateStageAssignment')->name('orders.stage-assignment');
        Route::get('/orders/{order}/references/{fileIndex}', 'orderReference')
            ->whereNumber('fileIndex')
            ->name('orders.references.show');
        Route::get('/orders/{order}/references/{fileIndex}/preview', 'orderReferencePreview')
            ->whereNumber('fileIndex')
            ->name('orders.references.preview');
        Route::get('/orders/{order}/references/{fileIndex}/download', 'downloadOrderReference')
            ->whereNumber('fileIndex')
            ->name('orders.references.download');
        Route::post('/orders/{order}/payments', 'storePayment')->name('orders.payments.store');
        Route::post('/orders/{order}/documents', 'storeDocument')->name('orders.documents.store');

        Route::get('/documents', 'documents')->name('documents.index');
        Route::get('/documents/{document}', 'showDocument')->name('documents.show');
        Route::get('/payments', 'payments')->name('payments.index');
        Route::get('/payments/{payment}/proof', 'paymentProof')->name('payments.proof');
        Route::get('/payments/{payment}/proof/content', 'paymentProofContent')->name('payments.proof.content');
        Route::get('/payments/{payment}/proof/download', 'downloadPaymentProof')->name('payments.proof.download');

        Route::get('/expenses', 'expenses')->name('expenses.index');
        Route::post('/expenses', 'storeExpense')->name('expenses.store');
        Route::post('/expenses/{expense}/archive', 'archiveExpense')->name('expenses.archive');
        Route::get('/expenses/{expense}/receipt', 'expenseReceipt')->name('expenses.receipt');

    });

    Route::get('/profile/photo', [StaffProfileController::class, 'photo'])->name('profile.photo');
    Route::post('/profile/photo', [StaffProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::get('/profile', [StaffProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [StaffProfileController::class, 'update'])->name('profile.update');
    Route::get('/notifications/{notificationId}', [StaffNotificationController::class, 'show'])->name('notifications.show');
    Route::get('/portfolio', [PortfolioManagementController::class, 'index'])->name('portfolio.index');
    Route::get('/portfolio/create', [PortfolioManagementController::class, 'create'])->name('portfolio.create');
    Route::post('/portfolio', [PortfolioManagementController::class, 'store'])->name('portfolio.store');
    Route::get('/portfolio/{slug}/edit', [PortfolioManagementController::class, 'edit'])->name('portfolio.edit');
    Route::put('/portfolio/{slug}', [PortfolioManagementController::class, 'update'])->name('portfolio.update');
    Route::delete('/portfolio/{slug}', [PortfolioManagementController::class, 'destroy'])->name('portfolio.destroy');

    Route::get('/products', [ProductManagementController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductManagementController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductManagementController::class, 'store'])->name('products.store');
    Route::get('/products/{slug}/edit', [ProductManagementController::class, 'edit'])->name('products.edit');
    Route::put('/products/{slug}', [ProductManagementController::class, 'update'])->name('products.update');
    Route::delete('/products/{slug}', [ProductManagementController::class, 'destroy'])->name('products.destroy');

    Route::get('/inquiries', [QuoteInquiryManagementController::class, 'index'])->name('inquiries.index');
    Route::get('/inquiries/{inquiry}', [QuoteInquiryManagementController::class, 'show'])->name('inquiries.show');
    Route::patch('/inquiries/{inquiry}', [QuoteInquiryManagementController::class, 'update'])->name('inquiries.update');
    Route::get('/inquiries/{inquiry}/attachments/{fileIndex}', [QuoteInquiryManagementController::class, 'attachment'])
        ->whereNumber('fileIndex')
        ->name('inquiries.attachments.show');
    Route::get('/inquiries/{inquiry}/attachments/{fileIndex}/preview', [QuoteInquiryManagementController::class, 'attachmentPreview'])
        ->whereNumber('fileIndex')
        ->name('inquiries.attachments.preview');
    Route::get('/inquiries/{inquiry}/attachments/{fileIndex}/preview-content', [QuoteInquiryManagementController::class, 'attachmentPreviewContent'])
        ->whereNumber('fileIndex')
        ->name('inquiries.attachments.preview-content');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [StaffAuthController::class, 'create'])->defaults('role', 'admin')->name('login');
    Route::post('/login', [StaffAuthController::class, 'store'])->defaults('role', 'admin')->middleware('throttle:5,1')->name('login.store');

    Route::middleware(['auth', 'role:admin'])->group(function (): void {
        Route::get('/dashboard', [StaffAuthController::class, 'dashboard'])->defaults('role', 'admin')->name('dashboard');
        Route::post('/logout', [StaffAuthController::class, 'destroy'])->defaults('role', 'admin')->name('logout');
    });
});

Route::prefix('superadmin')->name('superadmin.')->group(function (): void {
    Route::get('/login', [StaffAuthController::class, 'create'])->defaults('role', 'superadmin')->name('login');
    Route::post('/login', [StaffAuthController::class, 'store'])->defaults('role', 'superadmin')->middleware('throttle:5,1')->name('login.store');

    Route::middleware(['auth', 'role:superadmin'])->group(function (): void {
        Route::get('/dashboard', [StaffAuthController::class, 'dashboard'])->defaults('role', 'superadmin')->name('dashboard');
        Route::get('/audit-trail', [AuditTrailController::class, 'index'])->name('audit.index');
        Route::get('/site-settings', [SiteSettingsController::class, 'edit'])->name('site-settings.edit');
        Route::put('/site-settings', [SiteSettingsController::class, 'update'])->name('site-settings.update');
        Route::post('/admins', [StaffAuthController::class, 'nominateAdmin'])->middleware('throttle:10,1')->name('admins.store');
        Route::post('/logout', [StaffAuthController::class, 'destroy'])->defaults('role', 'superadmin')->name('logout');
    });
});

Route::post('/staff/logout-and-switch/{role}', [StaffAuthController::class, 'logoutAndSwitch'])
    ->middleware(['auth', 'role:admin,superadmin'])
    ->name('staff.logout-and-switch');

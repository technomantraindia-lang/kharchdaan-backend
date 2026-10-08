<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CashbackController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FailedJobController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\MlmCalculationController;
use App\Http\Controllers\Admin\MlmMemberController;
use App\Http\Controllers\Admin\MlmPayoutController;
use App\Http\Controllers\Admin\MlmReconciliationController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\RtoController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\TaxController;
use App\Http\Controllers\Admin\WooCommerceController;
use App\Http\Controllers\Admin\WooCommerceProductSyncController;
use App\Http\Controllers\Admin\WooCommerceSyncConflictController;
use App\Http\Controllers\Admin\WooCommerceSyncLogController;
use App\Models\Brand;
use App\Models\ProductAttribute;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::bind('shipping', fn ($value) => ShippingMethod::findOrFail($value));
Route::bind('customer', fn ($value) => User::findOrFail($value));
Route::bind('attribute', fn ($value) => ProductAttribute::findOrFail($value));
Route::bind('brand', fn ($value) => Brand::findOrFail($value));
Route::bind('user', fn ($value) => User::findOrFail($value));

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isSuperAdmin()
            ? redirect()->route('super-admin.dashboard')
            : redirect()->route('sub-admin.dashboard');
    }
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');

Route::middleware('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('/search', [GlobalSearchController::class, 'search'])->name('search');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Products & Variations
    Route::get('/products-bulk/create', [ProductController::class, 'bulkCreate'])->middleware('permission:products.bulk_manage')->name('products.bulkCreate');
    Route::post('/products-bulk/store', [ProductController::class, 'bulkStore'])->middleware('permission:products.bulk_manage')->name('products.bulkStore');
    Route::get('/products-import', [ProductController::class, 'importForm'])->middleware('permission:products.import')->name('products.import');
    Route::post('/products-import', [ProductController::class, 'importStore'])->middleware('permission:products.import')->name('products.importStore');
    Route::patch('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->middleware('permission:products.edit')->name('products.toggleStatus');
    Route::patch('/products/{product}/toggle-featured', [ProductController::class, 'toggleFeatured'])->middleware('permission:products.edit')->name('products.toggleFeatured');

    Route::get('/products/{product}/variations', [ProductVariationController::class, 'index'])->middleware('permission:products.view')->name('products.variations.index');
    Route::post('/products/{product}/variations', [ProductVariationController::class, 'store'])->middleware('permission:products.edit')->name('products.variations.store');
    Route::put('/products/{product}/variations/{variation}', [ProductVariationController::class, 'update'])->middleware('permission:products.edit')->name('products.variations.update');
    Route::delete('/products/{product}/variations/{variation}', [ProductVariationController::class, 'destroy'])->middleware('permission:products.edit')->name('products.variations.destroy');

    Route::resource('products', ProductController::class)->middleware('permission:products.view');

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show'])->middleware('permission:categories.view');

    // Brands
    Route::resource('brands', BrandController::class)->except(['show'])->middleware('permission:brands.view');

    // Attributes
    Route::resource('attributes', AttributeController::class)->except(['show'])->middleware('permission:attributes.view');

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->middleware('permission:inventory.view')->name('inventory.index');
    Route::put('/inventory/{product}', [InventoryController::class, 'update'])->middleware('permission:inventory.adjust')->name('inventory.update');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
    Route::post('/orders/bulk-action', [OrderController::class, 'bulkAction'])->middleware('permission:orders.edit')->name('orders.bulkAction');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->middleware('permission:orders.view')->name('orders.show');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->middleware('permission:orders.edit')->name('orders.update');
    Route::post('/orders/{order}/verify-payment', [OrderController::class, 'verifyPaymentManually'])->middleware('permission:orders.edit')->name('orders.verifyPayment');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:customers.view')->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('permission:customers.view')->name('customers.show');

    // Direct Selling / MLM Network & Hierarchy
    Route::get('/mlm/sponsor-network', [MlmMemberController::class, 'sponsorNetwork'])->middleware('permission:mlm.view')->name('mlm.sponsor-network');
    Route::get('/mlm/genealogy', [MlmMemberController::class, 'genealogy'])->middleware('permission:mlm.view')->name('mlm.genealogy');
    Route::get('/mlm/levels', [MlmMemberController::class, 'levels'])->middleware('permission:mlm.view')->name('mlm.levels');

    // MLM Placement Tree
    Route::prefix('mlm/tree')->name('mlm.tree.')->group(function (): void {
        Route::get('/', [MlmMemberController::class, 'tree'])->middleware('permission:mlm.view')->name('index');
        Route::get('/search', [MlmMemberController::class, 'treeSearch'])->middleware('permission:mlm.view')->name('search');
        Route::get('/move/{member}/parents/search', [MlmMemberController::class, 'moveParentSearch'])->middleware('permission:mlm.manage')->name('move.parents.search');
        Route::get('/move/{member}', [MlmMemberController::class, 'moveForm'])->middleware('permission:mlm.manage')->name('move.form');
        Route::post('/move/{member}/preview', [MlmMemberController::class, 'movePreview'])->middleware('permission:mlm.manage')->name('move.preview');
        Route::post('/move/{member}', [MlmMemberController::class, 'move'])->middleware('permission:mlm.manage')->name('move');
        Route::get('/{member}/children', [MlmMemberController::class, 'treeChildren'])->middleware('permission:mlm.view')->name('children');
    });

    // MLM Calculation Engine (manual only; no live order integration)
    Route::prefix('mlm/calculations')->name('mlm.calculations.')->group(function (): void {
        Route::get('/', [MlmCalculationController::class, 'index'])->middleware('permission:mlm.view')->name('index');
        Route::post('/preview', [MlmCalculationController::class, 'preview'])->middleware('permission:mlm.manage')->name('preview');
        Route::post('/', [MlmCalculationController::class, 'store'])->middleware('permission:mlm.manage')->name('store');
    });

    Route::prefix('mlm/reconciliation')->name('mlm.reconciliation.')->group(function (): void {
        Route::get('/', [MlmReconciliationController::class, 'index'])->middleware('permission:mlm.view')->name('index');
        Route::post('/orders/{order}/retry', [MlmReconciliationController::class, 'retry'])->middleware('permission:mlm.manage')->name('retry');
    });

    Route::prefix('cashback')->name('cashback.')->group(function (): void {
        Route::get('/', [CashbackController::class, 'index'])->middleware('permission:cashback.view')->name('index');
        Route::get('/reconciliation', [CashbackController::class, 'reconciliation'])->middleware('permission:cashback.view')->name('reconciliation');
        Route::post('/reconciliation/{cashback}/retry', [CashbackController::class, 'retryReconciliation'])->middleware('permission:cashback.select')->name('reconciliation.retry');
        Route::post('/pools', [CashbackController::class, 'storePool'])->middleware('permission:cashback.pool.manage')->name('pools.store');
        Route::post('/select', [CashbackController::class, 'select'])->middleware('permission:cashback.select')->name('select');
        Route::post('/batches', [CashbackController::class, 'createBatch'])->middleware('permission:cashback.select')->name('batches.store');
        Route::patch('/batches/{batch}/approve', [CashbackController::class, 'approve'])->middleware('permission:cashback.approve')->name('batches.approve');
        Route::patch('/batches/{batch}/schedule', [CashbackController::class, 'schedule'])->middleware('permission:cashback.schedule')->name('batches.schedule');
        Route::patch('/batches/{batch}/process', [CashbackController::class, 'process'])->middleware('permission:cashback.pay')->name('batches.process');
        Route::patch('/batches/{batch}/paid', [CashbackController::class, 'paid'])->middleware('permission:cashback.pay')->name('batches.paid');
        Route::get('/batches/{batch}/payment-proof', [CashbackController::class, 'paymentProof'])->middleware('permission:cashback.view')->name('batches.paymentProof');
        Route::post('/records/{cashback}/hold', [CashbackController::class, 'hold'])->middleware('permission:cashback.hold')->name('records.hold');
        Route::post('/records/{cashback}/reverse', [CashbackController::class, 'reverse'])->middleware('permission:cashback.reverse')->name('records.reverse');
    });

    // MLM Income and manual payout controls
    Route::prefix('mlm/payouts')->name('mlm.payouts.')->group(function (): void {
        Route::get('/', [MlmPayoutController::class, 'index'])->middleware('permission:mlm.view')->name('index');
        Route::post('/cycles', [MlmPayoutController::class, 'storeCycle'])->middleware('permission:mlm.manage')->name('cycles.store');
        Route::patch('/cycles/{cycle}/approve', [MlmPayoutController::class, 'approve'])->middleware('permission:mlm.manage')->name('cycles.approve');
        Route::patch('/cycles/{cycle}/process', [MlmPayoutController::class, 'process'])->middleware('permission:mlm.manage')->name('cycles.process');
        Route::patch('/cycles/{cycle}/paid', [MlmPayoutController::class, 'paid'])->middleware('permission:mlm.manage')->name('cycles.paid');
        Route::patch('/cycles/{cycle}/failed', [MlmPayoutController::class, 'failed'])->middleware('permission:mlm.manage')->name('cycles.failed');
        Route::post('/cycles/{cycle}/hold', [MlmPayoutController::class, 'hold'])->middleware('permission:mlm.manage')->name('cycles.hold');
        Route::patch('/cycles/{cycle}/release-hold', [MlmPayoutController::class, 'releaseHold'])->middleware('permission:mlm.manage')->name('cycles.releaseHold');
        Route::get('/cycles/{cycle}/payment-proof', [MlmPayoutController::class, 'paymentProof'])->middleware('permission:mlm.view')->name('cycles.paymentProof');
        Route::post('/ledgers/{ledger}/reverse', [MlmPayoutController::class, 'reverse'])->middleware('permission:mlm.manage')->name('ledgers.reverse');
        Route::post('/adjustments', [MlmPayoutController::class, 'adjustment'])->middleware('permission:mlm.manage')->name('adjustments.store');
    });

    // MLM Members
    Route::prefix('mlm/members')->name('mlm.members.')->group(function (): void {
        Route::get('/', [MlmMemberController::class, 'index'])->middleware('permission:mlm.view')->name('index');
        Route::get('/create', [MlmMemberController::class, 'create'])->middleware('permission:mlm.manage')->name('create');
        Route::post('/', [MlmMemberController::class, 'store'])->middleware('permission:mlm.manage')->name('store');
        Route::get('/{member}', [MlmMemberController::class, 'show'])->middleware('permission:mlm.view')->name('show');
        Route::get('/{member}/edit', [MlmMemberController::class, 'edit'])->middleware('permission:mlm.manage')->name('edit');
        Route::put('/{member}', [MlmMemberController::class, 'update'])->middleware('permission:mlm.manage')->name('update');
        Route::delete('/{member}', [MlmMemberController::class, 'destroy'])->middleware('permission:mlm.manage')->name('destroy');
        Route::patch('/{member}/toggle-status', [MlmMemberController::class, 'toggleStatus'])->middleware('permission:mlm.manage')->name('toggleStatus');
        Route::get('/{member}/kyc', [MlmMemberController::class, 'kyc'])->middleware('permission:mlm.view')->name('kyc');
        Route::patch('/{member}/kyc', [MlmMemberController::class, 'reviewKyc'])->middleware('permission:mlm.manage')->name('kyc.update');
        Route::get('/{member}/kyc/cancelled-cheque', [MlmMemberController::class, 'downloadCancelledCheque'])->middleware('permission:mlm.view')->name('kyc.cancelledCheque');
    });

    // Coupons, Taxes, Shipping
    Route::resource('coupons', CouponController::class)->except(['show'])->middleware('permission:coupons.view');
    Route::resource('taxes', TaxController::class)->except(['show'])->middleware('permission:taxes.view');
    Route::resource('shipping', ShippingController::class)->except(['show'])->middleware('permission:shipping.view');

    // Payments & Invoices
    Route::get('/payments', [PaymentController::class, 'index'])->middleware('permission:payments.view')->name('payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->middleware('permission:payments.view')->name('payments.show');

    Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('permission:invoices.view')->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:invoices.view')->name('invoices.show');

    // Inquiries
    Route::get('/inquiries', [InquiryController::class, 'index'])->middleware('permission:inquiries.view')->name('inquiries.index');
    Route::get('/inquiries/{inquiry}', [InquiryController::class, 'show'])->middleware('permission:inquiries.view')->name('inquiries.show');
    Route::put('/inquiries/{inquiry}', [InquiryController::class, 'update'])->middleware('permission:inquiries.manage')->name('inquiries.update');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
    Route::get('/reports/network', [ReportController::class, 'network'])->middleware('permission:reports.view')->name('reports.network');
    Route::get('/reports/income', [ReportController::class, 'income'])->middleware('permission:reports.view')->name('reports.income');
    Route::get('/reports/payouts', [ReportController::class, 'payouts'])->middleware('permission:reports.view')->name('reports.payouts');
    Route::get('/reports/cashback', [ReportController::class, 'cashback'])->middleware('permission:reports.view')->name('reports.cashback');
    Route::get('/reports/profit', [ReportController::class, 'profit'])->middleware('permission:reports.view')->name('reports.profit');
    Route::get('/reports/gst', [ReportController::class, 'gst'])->middleware('permission:reports.view')->name('reports.gst');
    Route::get('/reports/export/{module}', [ReportController::class, 'export'])->middleware('permission:reports.view')->name('reports.export');

    // CMS (Banners, Pages)
    Route::resource('banners', BannerController::class)->except(['show'])->middleware('permission:cms.view');
    Route::resource('pages', PageController::class)->except(['show'])->middleware('permission:cms.view');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->middleware('permission:settings.view')->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->middleware('permission:settings.manage')->name('settings.update');

    // Admin Users Management (Staff)
    Route::get('/users', [AdminUserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->middleware('permission:users.manage')->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->middleware('permission:users.manage')->name('users.store');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->middleware('permission:users.manage')->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.manage')->name('users.update');
    Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->middleware('permission:users.manage')->name('users.toggleStatus');

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware('permission:activity_logs.view')->name('activity-logs.index');

    // WooCommerce Integration & Sync Modules
    Route::get('/woocommerce', [WooCommerceController::class, 'index'])->middleware('permission:woocommerce.view')->name('woocommerce.index');
    Route::post('/woocommerce/test-connection', [WooCommerceController::class, 'testConnection'])->middleware('permission:woocommerce.manage')->name('woocommerce.testConnection');

    Route::get('/woocommerce/products', [WooCommerceProductSyncController::class, 'index'])->middleware('permission:woocommerce.view')->name('woocommerce.products.index');
    Route::post('/woocommerce/products/{product}/sync', [WooCommerceProductSyncController::class, 'syncSingle'])->middleware('permission:woocommerce.manage')->name('woocommerce.products.syncSingle');
    Route::post('/woocommerce/products/bulk-sync', [WooCommerceProductSyncController::class, 'bulkSync'])->middleware('permission:woocommerce.manage')->name('woocommerce.products.bulkSync');
    Route::get('/woocommerce/products/{product}/error', [WooCommerceProductSyncController::class, 'showError'])->middleware('permission:woocommerce.view')->name('woocommerce.products.showError');

    Route::get('/woocommerce/sync-logs', [WooCommerceSyncLogController::class, 'index'])->middleware('permission:woocommerce.view')->name('woocommerce.sync-logs.index');
    Route::get('/woocommerce/sync-logs/{log}', [WooCommerceSyncLogController::class, 'show'])->middleware('permission:woocommerce.view')->name('woocommerce.sync-logs.show');
    Route::post('/woocommerce/sync-logs/{log}/retry', [WooCommerceSyncLogController::class, 'retry'])->middleware('permission:woocommerce.manage')->name('woocommerce.sync-logs.retry');

    Route::get('/woocommerce/conflicts', [WooCommerceSyncConflictController::class, 'index'])->middleware('permission:woocommerce.view')->name('woocommerce.conflicts.index');
    Route::post('/woocommerce/conflicts/{conflict}/resolve', [WooCommerceSyncConflictController::class, 'resolve'])->middleware('permission:woocommerce.manage')->name('woocommerce.conflicts.resolve');

    // Sales Returns
    Route::get('/returns', [ReturnController::class, 'index'])->middleware('permission:orders.view')->name('returns.index');
    Route::get('/returns/{return}', [ReturnController::class, 'show'])->middleware('permission:orders.view')->name('returns.show');
    Route::post('/returns/{return}/update-status', [ReturnController::class, 'updateStatus'])->middleware('permission:orders.edit')->name('returns.updateStatus');

    // Sales RTO
    Route::get('/rto', [RtoController::class, 'index'])->middleware('permission:orders.view')->name('rto.index');
    Route::get('/rto/create', [RtoController::class, 'create'])->middleware('permission:orders.edit')->name('rto.create');
    Route::post('/rto', [RtoController::class, 'store'])->middleware('permission:orders.edit')->name('rto.store');
    Route::get('/rto/{rto}', [RtoController::class, 'show'])->middleware('permission:orders.view')->name('rto.show');
    Route::post('/rto/{rto}/update-status', [RtoController::class, 'updateStatus'])->middleware('permission:orders.edit')->name('rto.updateStatus');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // System Queue & Failed Jobs (Super Admin Restricted)
    Route::get('/system-health', [SystemHealthController::class, 'index'])->middleware('permission:settings.view')->name('system-health.index');
    Route::get('/system/failed-jobs', [FailedJobController::class, 'index'])->middleware('permission:settings.manage')->name('system.failed-jobs.index');
    Route::post('/system/failed-jobs/retry-all', [FailedJobController::class, 'retryAll'])->middleware('permission:settings.manage')->name('system.failed-jobs.retry-all');
    Route::post('/system/failed-jobs/{id}/retry', [FailedJobController::class, 'retry'])->middleware('permission:settings.manage')->name('system.failed-jobs.retry');
    Route::delete('/system/failed-jobs/{id}', [FailedJobController::class, 'destroy'])->middleware('permission:settings.manage')->name('system.failed-jobs.destroy');

    // System Backups (Super Admin Restricted)
    Route::get('/system/backups', [BackupController::class, 'index'])->middleware('permission:settings.manage')->name('system.backups.index');
    Route::post('/system/backups', [BackupController::class, 'create'])->middleware('permission:settings.manage')->name('system.backups.create');
    Route::get('/system/backups/{filename}/download', [BackupController::class, 'download'])->middleware('permission:settings.manage')->name('system.backups.download');
    Route::delete('/system/backups/{filename}', [BackupController::class, 'destroy'])->middleware('permission:settings.manage')->name('system.backups.destroy');
});

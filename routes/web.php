<?php

use App\Http\Controllers\CariController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PendingBillingController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\Admin\MailSettingController as AdminMailSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\TriggersController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('caris', CariController::class)->except(['show']);
    Route::resource('service-providers', ServiceProviderController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('subscriptions', SubscriptionController::class)->except(['destroy']);
    Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('subscriptions/{subscription}/create-projection', [SubscriptionController::class, 'createProjection'])->name('subscriptions.create-projection');
    Route::get('subscriptions/{subscription}/order-summary-totals', [SubscriptionController::class, 'orderSummaryTotals'])->name('subscriptions.order-summary-totals');
    Route::get('subscriptions/{subscription}/update-quantity', [SubscriptionController::class, 'showUpdateQuantity'])->name('subscriptions.show-update-quantity');
    Route::post('subscriptions/{subscription}/update-quantity', [SubscriptionController::class, 'updateQuantity'])->name('subscriptions.update-quantity');
    Route::get('pending-billings', [PendingBillingController::class, 'index'])->name('pending-billings.index');
    Route::post('pending-billings/{pending_billing}/refresh-amounts', [PendingBillingController::class, 'refreshAmounts'])->name('pending-billings.refresh-amounts');
    Route::get('pending-billings/{pending_billing}/supplier-invoice', [PendingBillingController::class, 'showSupplierInvoice'])->name('pending-billings.supplier-invoice');
    Route::post('pending-billings/{pending_billing}/supplier-invoice', [PendingBillingController::class, 'storeSupplierInvoice'])->name('pending-billings.store-supplier-invoice');
    Route::get('pending-billings/supplier-invoice-xml', [PendingBillingController::class, 'showSupplierInvoiceXml'])->name('pending-billings.supplier-invoice-xml');
    Route::post('pending-billings/supplier-invoice-xml', [PendingBillingController::class, 'storeSupplierInvoiceXml'])->name('pending-billings.store-supplier-invoice-xml');
    Route::get('pending-billings/supplier-invoice-xml-preview', [PendingBillingController::class, 'showSupplierInvoiceXmlPreview'])->name('pending-billings.supplier-invoice-xml-preview');
    Route::get('pending-billings/supplier-invoice-xml-cancel', [PendingBillingController::class, 'cancelSupplierInvoiceXmlPreview'])->name('pending-billings.supplier-invoice-xml-cancel');
    Route::post('pending-billings/supplier-invoice-xml-apply', [PendingBillingController::class, 'applySupplierInvoiceXml'])->name('pending-billings.supplier-invoice-xml-apply');
    Route::get('sales-invoices', [SalesInvoiceController::class, 'index'])->name('sales-invoices.index');
    Route::get('sales-invoices/sales-invoice-xml', [SalesInvoiceController::class, 'showSalesInvoiceXml'])->name('sales-invoices.sales-invoice-xml');
    Route::post('sales-invoices/sales-invoice-xml', [SalesInvoiceController::class, 'storeSalesInvoiceXml'])->name('sales-invoices.store-sales-invoice-xml');
    Route::get('sales-invoices/sales-invoice-xml-match-preview', [SalesInvoiceController::class, 'showSalesInvoiceXmlMatchPreview'])->name('sales-invoices.sales-invoice-xml-match-preview');
    Route::get('sales-invoices/sales-invoice-xml-match-cancel', [SalesInvoiceController::class, 'cancelSalesInvoiceXmlMatch'])->name('sales-invoices.sales-invoice-xml-match-cancel');
    Route::post('sales-invoices/sales-invoice-xml-confirm', [SalesInvoiceController::class, 'confirmSalesInvoiceXmlMatch'])->name('sales-invoices.sales-invoice-xml-confirm');
    Route::get('sales-invoices/create', [SalesInvoiceController::class, 'create'])->name('sales-invoices.create');
    Route::post('sales-invoices', [SalesInvoiceController::class, 'store'])->name('sales-invoices.store');
    Route::get('sales-invoices/{sales_invoice}', [SalesInvoiceController::class, 'show'])->name('sales-invoices.show');
    Route::get('sales-invoices/{sales_invoice}/invoice-details', [SalesInvoiceController::class, 'editInvoiceDetails'])->name('sales-invoices.invoice-details');
    Route::patch('sales-invoices/{sales_invoice}/invoice-details', [SalesInvoiceController::class, 'updateInvoiceDetails'])->name('sales-invoices.update-invoice-details');

    Route::get('exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
    Route::post('exchange-rates/fetch-latest', [ExchangeRateController::class, 'fetchLatest'])->name('exchange-rates.fetch-latest');
    Route::get('exchange-rates/{exchangeRate}/edit', [ExchangeRateController::class, 'edit'])->name('exchange-rates.edit');
    Route::patch('exchange-rates/{exchangeRate}', [ExchangeRateController::class, 'update'])->name('exchange-rates.update');

    Route::get('triggers', [TriggersController::class, 'index'])->name('triggers.index');
    Route::post('triggers/run-renewals-up-to', [TriggersController::class, 'runRenewalsUpTo'])->name('triggers.run-renewals-up-to');
    Route::post('triggers/run-enqueue-missing', [TriggersController::class, 'runEnqueueMissingPeriods'])->name('triggers.run-enqueue-missing');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::patch('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::get('mail-settings', [AdminMailSettingController::class, 'edit'])->name('mail-settings.edit');
        Route::patch('mail-settings', [AdminMailSettingController::class, 'update'])->name('mail-settings.update');
        Route::post('mail-settings/test', [AdminMailSettingController::class, 'sendTest'])->name('mail-settings.test');
    });
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\CariController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PendingBillingController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\ExpenseSettlementController;
use App\Http\Controllers\SubscriptionMonitorController;
use App\Http\Controllers\Admin\MailSettingController as AdminMailSettingController;
use App\Http\Controllers\Admin\NotificationTemplateController as AdminNotificationTemplateController;
use App\Http\Controllers\Admin\AutomationJobController as AdminAutomationJobController;
use App\Http\Controllers\Admin\NotificationCariController as AdminNotificationCariController;
use App\Http\Controllers\Admin\AutomationRuleController as AdminAutomationRuleController;
use App\Http\Controllers\Admin\CariLedgerReportController as AdminCariLedgerReportController;
use App\Http\Controllers\Admin\PendingBillingAdminController as AdminPendingBillingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\IntegrationPreviewController as AdminIntegrationPreviewController;
use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\ApiSettingsController as AdminApiSettingsController;
use App\Http\Controllers\TriggersController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('caris', CariController::class)->except(['show']);
    Route::patch('caris/{cari}/quick', [CariController::class, 'quickUpdate'])->name('caris.quick-update');
    Route::resource('service-providers', ServiceProviderController::class)->except(['show']);
    Route::resource('products', ProductController::class);
    Route::get('products/api/data', [ProductController::class, 'api'])->name('products.api');
    Route::resource('subscriptions', SubscriptionController::class)->except(['destroy']);
    Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::delete('subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');
    Route::post('subscriptions/{subscription}/toggle-auto-renew', [SubscriptionController::class, 'toggleAutoRenew'])->name('subscriptions.toggle-auto-renew');
    Route::post('subscriptions/{subscription}/create-projection', [SubscriptionController::class, 'createProjection'])->name('subscriptions.create-projection');
    Route::get('subscriptions/{subscription}/order-summary-totals', [SubscriptionController::class, 'orderSummaryTotals'])->name('subscriptions.order-summary-totals');
    Route::get('subscriptions/{subscription}/update-quantity', [SubscriptionController::class, 'showUpdateQuantity'])->name('subscriptions.show-update-quantity');
    Route::post('subscriptions/{subscription}/update-quantity', [SubscriptionController::class, 'updateQuantity'])->name('subscriptions.update-quantity');
    Route::get('pending-billings', [PendingBillingController::class, 'index'])->name('pending-billings.index');
    Route::post('pending-billings/{pending_billing}/refresh-amounts', [PendingBillingController::class, 'refreshAmounts'])->name('pending-billings.refresh-amounts');
    Route::delete('pending-billings/{pending_billing}', [PendingBillingController::class, 'destroy'])->name('pending-billings.destroy');
    Route::post('pending-billings/{pending_billing_id}/restore', [PendingBillingController::class, 'restore'])->whereNumber('pending_billing_id')->name('pending-billings.restore');
    Route::get('pending-billings/{pending_billing}/supplier-invoice', [PendingBillingController::class, 'showSupplierInvoice'])->name('pending-billings.supplier-invoice');
    Route::post('pending-billings/{pending_billing}/supplier-invoice', [PendingBillingController::class, 'storeSupplierInvoice'])->name('pending-billings.store-supplier-invoice');
    Route::post('pending-billings/{pending_billing}/clear-supplier-invoice', [PendingBillingController::class, 'clearSupplierInvoice'])->name('pending-billings.clear-supplier-invoice');
    Route::post('pending-billings/{pending_billing}/postpone', [PendingBillingController::class, 'postpone'])->name('pending-billings.postpone');
    Route::post('pending-billings/bulk-postpone', [PendingBillingController::class, 'bulkPostpone'])->name('pending-billings.bulk-postpone');
    Route::post('pending-billings/bulk-to-pending', [PendingBillingController::class, 'bulkToPending'])->name('pending-billings.bulk-to-pending');
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
    Route::post('sales-invoices/{sales_invoice}/revert', [SalesInvoiceController::class, 'revert'])->name('sales-invoices.revert');
    Route::post('sales-invoices/{sales_invoice}/mark-paid', [SalesInvoiceController::class, 'markPaid'])->name('sales-invoices.mark-paid');
    Route::post('sales-invoices/{sales_invoice}/mark-unpaid', [SalesInvoiceController::class, 'markUnpaid'])->name('sales-invoices.mark-unpaid');

    Route::get('expense-settlements', [ExpenseSettlementController::class, 'index'])->name('expense-settlements.index');
    Route::get('expense-settlements/create', [ExpenseSettlementController::class, 'create'])->name('expense-settlements.create');
    Route::post('expense-settlements', [ExpenseSettlementController::class, 'store'])->name('expense-settlements.store');
    Route::get('expense-settlements/{expense_settlement}', [ExpenseSettlementController::class, 'show'])->name('expense-settlements.show');
    Route::patch('expense-settlements/{expense_settlement}/due-date', [ExpenseSettlementController::class, 'updateDueDate'])->name('expense-settlements.update-due-date');
    Route::post('expense-settlements/{expense_settlement}/revert', [ExpenseSettlementController::class, 'revert'])->name('expense-settlements.revert');
    Route::post('expense-settlements/{expense_settlement}/mark-closed', [ExpenseSettlementController::class, 'markClosed'])->name('expense-settlements.mark-closed');
    Route::post('expense-settlements/{expense_settlement}/mark-open', [ExpenseSettlementController::class, 'markOpen'])->name('expense-settlements.mark-open');

    Route::get('exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
    Route::post('exchange-rates/fetch-latest', [ExchangeRateController::class, 'fetchLatest'])->name('exchange-rates.fetch-latest');
    Route::get('exchange-rates/{exchangeRate}/edit', [ExchangeRateController::class, 'edit'])->name('exchange-rates.edit');
    Route::patch('exchange-rates/{exchangeRate}', [ExchangeRateController::class, 'update'])->name('exchange-rates.update');

    Route::get('triggers', [TriggersController::class, 'index'])->name('triggers.index');
    Route::post('triggers/run-renewals-up-to', [TriggersController::class, 'runRenewalsUpTo'])->name('triggers.run-renewals-up-to');
    Route::get('triggers/renewals-up-to-end-of-month', [TriggersController::class, 'showRenewalsUpToEndOfMonth'])->name('triggers.renewals-up-to-end-of-month');
    Route::post('triggers/renewals-up-to-end-of-month', [TriggersController::class, 'runRenewalsUpToEndOfMonth'])->name('triggers.run-renewals-up-to-end-of-month');
    Route::post('triggers/run-enqueue-missing', [TriggersController::class, 'runEnqueueMissingPeriods'])->name('triggers.run-enqueue-missing');

    Route::get('subscription-monitor', [SubscriptionMonitorController::class, 'index'])->name('subscription-monitor.index');
    Route::post('subscription-monitor/enqueue-missing-for-cari', [SubscriptionMonitorController::class, 'enqueueMissingForCari'])->name('subscription-monitor.enqueue-missing-for-cari');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::patch('users/{user}', [AdminUserController::class, 'update'])->name('users.update');

        Route::get('mail-settings', [AdminMailSettingController::class, 'edit'])->name('mail-settings.edit');
        Route::patch('mail-settings', [AdminMailSettingController::class, 'update'])->name('mail-settings.update');
        Route::post('mail-settings/test', [AdminMailSettingController::class, 'sendTest'])->name('mail-settings.test');
        Route::get('notifications', [AdminAutomationRuleController::class, 'index'])->name('notifications.edit');
        Route::get('notifications/templates', [AdminNotificationTemplateController::class, 'index'])->name('notifications.templates.index');
        Route::get('notifications/templates/{template}/edit', [AdminNotificationTemplateController::class, 'edit'])->name('notifications.templates.edit');
        Route::patch('notifications/templates/{template}', [AdminNotificationTemplateController::class, 'update'])->name('notifications.templates.update');
        Route::match(['get', 'post'], 'notifications/templates/{template}/preview', [AdminNotificationTemplateController::class, 'preview'])->name('notifications.templates.preview');
        Route::post('notifications/templates/{template}/test', [AdminNotificationTemplateController::class, 'sendTest'])->name('notifications.templates.test');
        Route::get('notifications/jobs', [AdminAutomationJobController::class, 'index'])->name('notifications.jobs.index');
        Route::get('notifications/jobs/{job}', [AdminAutomationJobController::class, 'show'])->name('notifications.jobs.show');
        Route::post('notifications/jobs/{job}/retry', [AdminAutomationJobController::class, 'retry'])->name('notifications.jobs.retry');
        Route::post('notifications/jobs/{job}/cancel', [AdminAutomationJobController::class, 'cancel'])->name('notifications.jobs.cancel');
        Route::patch('notifications/jobs/{job}/reschedule', [AdminAutomationJobController::class, 'reschedule'])->name('notifications.jobs.reschedule');
        Route::post('notifications/jobs/{job}/retrigger', [AdminAutomationJobController::class, 'retrigger'])->name('notifications.jobs.retrigger');
        Route::get('notifications/caris', [AdminNotificationCariController::class, 'index'])->name('notifications.caris.index');
        Route::patch('notifications/caris/{cari}', [AdminNotificationCariController::class, 'update'])->name('notifications.caris.update');
        Route::get('notifications/rules/create', [AdminAutomationRuleController::class, 'create'])->name('notifications.rules.create');
        Route::post('notifications/rules', [AdminAutomationRuleController::class, 'store'])->name('notifications.rules.store');
        Route::post('notifications/rules/reorder-process', [AdminAutomationRuleController::class, 'reorderByProcess'])->name('notifications.rules.reorder-process');
        Route::post('notifications/rules/{rule}/move', [AdminAutomationRuleController::class, 'move'])->name('notifications.rules.move');
        Route::get('notifications/rules/{rule}/edit', [AdminAutomationRuleController::class, 'edit'])->name('notifications.rules.edit');
        Route::patch('notifications/rules/{rule}', [AdminAutomationRuleController::class, 'update'])->name('notifications.rules.update');
        Route::get('reports/cari-ledger', [AdminCariLedgerReportController::class, 'index'])->name('reports.cari-ledger');
        Route::get('reports/cari-ledger/export', [AdminCariLedgerReportController::class, 'export'])->name('reports.cari-ledger.export');
        Route::get('reports/cari-ledger/totals', [AdminCariLedgerReportController::class, 'totals'])->name('reports.cari-ledger.totals');

        Route::get('activity-logs', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');

        Route::get('pending-billings/{pending_billing}/edit-sale', [AdminPendingBillingController::class, 'editSale'])->name('pending-billings.edit-sale');
        Route::patch('pending-billings/{pending_billing}/edit-sale', [AdminPendingBillingController::class, 'updateSale'])->name('pending-billings.update-sale');
        Route::get('pending-billings/{pending_billing}/edit-expected-purchase', [AdminPendingBillingController::class, 'editExpectedPurchase'])->name('pending-billings.edit-expected-purchase');
        Route::patch('pending-billings/{pending_billing}/edit-expected-purchase', [AdminPendingBillingController::class, 'updateExpectedPurchase'])->name('pending-billings.update-expected-purchase');

        Route::prefix('api-settings')->name('api-settings.')->group(function () {
            Route::get('/', [AdminApiSettingsController::class, 'index'])->name('index');
            Route::post('integrations', [AdminApiSettingsController::class, 'storeIntegration'])->name('integrations.store');
            Route::patch('integrations/{integration}', [AdminApiSettingsController::class, 'updateIntegration'])->name('integrations.update');
            Route::post('integrations/{integration}/keys', [AdminApiSettingsController::class, 'generateKey'])->name('keys.generate');
            Route::patch('keys/{apiKey}/revoke', [AdminApiSettingsController::class, 'revokeKey'])->name('keys.revoke');
            Route::patch('keys/{apiKey}/toggle', [AdminApiSettingsController::class, 'toggleKey'])->name('keys.toggle');
            Route::post('integrations/{integration}/webhooks', [AdminApiSettingsController::class, 'storeWebhook'])->name('webhooks.store');
            Route::patch('webhooks/{webhook}/toggle', [AdminApiSettingsController::class, 'toggleWebhook'])->name('webhooks.toggle');
            Route::post('webhooks/{webhook}/test', [AdminApiSettingsController::class, 'testWebhook'])->name('webhooks.test');
            Route::get('logs', [AdminApiSettingsController::class, 'logs'])->name('logs');
        });

        Route::prefix('integration')->name('integration.')->group(function () {
            Route::get('cari-preview', [AdminIntegrationPreviewController::class, 'cariIndex'])->name('cari-preview');
            Route::get('cari-preview/data', [AdminIntegrationPreviewController::class, 'cariData'])->name('cari-preview.data');
            Route::get('subscription-preview', [AdminIntegrationPreviewController::class, 'subscriptionIndex'])->name('subscription-preview');
            Route::get('subscription-preview/data', [AdminIntegrationPreviewController::class, 'subscriptionData'])->name('subscription-preview.data');
            Route::get('open-order-preview', [AdminIntegrationPreviewController::class, 'openOrderIndex'])->name('open-order-preview');
            Route::get('open-order-preview/data', [AdminIntegrationPreviewController::class, 'openOrderData'])->name('open-order-preview.data');
            Route::get('invoiced-order-preview', [AdminIntegrationPreviewController::class, 'invoicedOrderIndex'])->name('invoiced-order-preview');
            Route::get('invoiced-order-preview/data', [AdminIntegrationPreviewController::class, 'invoicedOrderData'])->name('invoiced-order-preview.data');
            Route::get('product-preview', [AdminIntegrationPreviewController::class, 'productIndex'])->name('product-preview');
            Route::get('product-preview/data', [AdminIntegrationPreviewController::class, 'productData'])->name('product-preview.data');
        });
    });
});

require __DIR__.'/auth.php';

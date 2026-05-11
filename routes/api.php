<?php

use App\Http\Controllers\Api\ApiCariController;
use App\Http\Controllers\Api\ApiPendingBillingController;
use App\Http\Controllers\Api\ApiProductRequestController;
use App\Http\Controllers\Api\ApiSalesInvoiceController;
use App\Http\Controllers\Api\ApiSubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Cari bazlı endpoint'ler
Route::prefix('caris')->name('api.caris.')->group(function () {
    Route::get('/search', [ApiCariController::class, 'search'])->name('search');
    Route::get('/{cari}/subscriptions', [ApiCariController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/{cari}/upcoming-renewals', [ApiCariController::class, 'upcomingRenewals'])->name('upcoming-renewals');
    Route::get('/{cari}/pending-billings', [ApiPendingBillingController::class, 'indexByCari'])->name('pending-billings');
    Route::get('/{cari}/sales-invoices', [ApiSalesInvoiceController::class, 'indexByCari'])->name('sales-invoices');
    Route::get('/{cari}/product-requests', [ApiProductRequestController::class, 'indexByCari'])->name('product-requests');
});

// Subscription endpoint'leri
Route::prefix('subscriptions')->name('api.subscriptions.')->group(function () {
    Route::get('/', [ApiSubscriptionController::class, 'index'])->name('index');
    Route::get('/{subscription}', [ApiSubscriptionController::class, 'show'])->name('show');
});

// PendingBilling endpoint'leri
Route::prefix('pending-billings')->name('api.pending-billings.')->group(function () {
    Route::get('/', [ApiPendingBillingController::class, 'index'])->name('index');
    Route::get('/{pendingBilling}', [ApiPendingBillingController::class, 'show'])->name('show');
});

// SalesInvoice endpoint'leri
Route::prefix('sales-invoices')->name('api.sales-invoices.')->group(function () {
    Route::get('/', [ApiSalesInvoiceController::class, 'index'])->name('index');
    Route::get('/{salesInvoice}', [ApiSalesInvoiceController::class, 'show'])->name('show');
});

// ProductRequest endpoint'leri
Route::prefix('product-requests')->name('api.product-requests.')->group(function () {
    Route::get('/', [ApiProductRequestController::class, 'index'])->name('index');
    Route::post('/', [ApiProductRequestController::class, 'store'])->name('store');
    Route::get('/{productRequest}', [ApiProductRequestController::class, 'show'])->name('show');
});

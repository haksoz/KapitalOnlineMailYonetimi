<?php

use App\Http\Controllers\CariController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\SubscriptionController;
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
    Route::resource('subscriptions', SubscriptionController::class);
    Route::post('subscriptions/{subscription}/create-projection', [SubscriptionController::class, 'createProjection'])->name('subscriptions.create-projection');

    Route::get('exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
    Route::post('exchange-rates/fetch-latest', [ExchangeRateController::class, 'fetchLatest'])->name('exchange-rates.fetch-latest');
    Route::get('exchange-rates/{exchangeRate}/edit', [ExchangeRateController::class, 'edit'])->name('exchange-rates.edit');
    Route::patch('exchange-rates/{exchangeRate}', [ExchangeRateController::class, 'update'])->name('exchange-rates.update');
});

require __DIR__.'/auth.php';

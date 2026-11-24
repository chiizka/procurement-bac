<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\RfqController;
use App\Http\Controllers\GroupPurchaseRequestController;
use App\Http\Controllers\TwgReportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/purchase-requests/{purchaseRequest}/print', [PurchaseRequestController::class, 'printPreview'])
        ->name('purchase-requests.print');
    Route::resource('purchase-requests', PurchaseRequestController::class);
    Route::resource('purchase-requests.rfqs', RfqController::class)->shallow()->except(['index']);
    Route::get('/rfqs/{rfq}/print', [RfqController::class, 'printPreview'])->name('rfqs.print');
    Route::get('/group-purchase-requests/create', [GroupPurchaseRequestController::class, 'create'])->name('group-purchase-requests.create');
    Route::post('/group-purchase-requests', [GroupPurchaseRequestController::class, 'store'])->name('group-purchase-requests.store');
    Route::delete('/group-purchase-requests/{purchaseRequest}', [GroupPurchaseRequestController::class, 'destroy'])->name('group-purchase-requests.destroy');
    Route::resource('rfqs.twg-reports', TwgReportController::class)->shallow()->except(['index']);
    Route::get('/twg-reports/{twgReport}/print', [TwgReportController::class, 'printPreview'])->name('twg-reports.print');
});

require __DIR__.'/auth.php';

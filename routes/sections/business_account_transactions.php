<?php

use App\Http\Controllers\BusinessAccountTransactionController;
use Illuminate\Support\Facades\Route;

Route::controller(BusinessAccountTransactionController::class)->prefix('business_account_transactions')->group(function () {
    Route::post('/read', 'read');
    Route::post('/create', 'create');
    Route::post('/update/{id}', 'update');
    Route::post('/delete/{id}', 'delete');
});

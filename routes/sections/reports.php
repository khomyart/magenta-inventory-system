<?php

use App\Http\Controllers\ReportController;

Route::get('/reports', [ReportController::class, 'read'])
    ->middleware('api.authorization:read,reports');

Route::get('/reports/summary', [ReportController::class, 'summary'])
    ->middleware('api.authorization:read,reports');

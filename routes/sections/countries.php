<?php
use App\Http\Controllers\CountryController;

Route::get('/countries', [CountryController::class,'read'])
->middleware('api.authentication');


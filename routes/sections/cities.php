<?php
use App\Http\Controllers\CityController;

Route::get('/countries/{id}/cities', [CityController::class,'read'])
->middleware('api.authentication')->whereNumber('id');


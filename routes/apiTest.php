<?php
use App\Http\Controllers\TypeController;

Route::get('/apitest', [TypeController::class,'show'])
->middleware('api.authorization:show,type');

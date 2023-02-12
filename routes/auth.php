<?php
//authenticate, authorizate
use App\Http\Controllers\UserController;

Route::post("login", [UserController::class, "authenticate"]);


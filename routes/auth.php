<?php
//authenticate, authorizate
use App\Http\Controllers\UserController;

Route::post("login", [UserController::class, "authenticate"]);
Route::post("logout", [UserController::class, "logout"]);

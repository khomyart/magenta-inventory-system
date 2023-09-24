<?php
//authenticate, authorizate
use App\Http\Controllers\UserController;

Route::post("login", [UserController::class, "authenticate"]);
Route::post("logout", [UserController::class, "logout"]);
// Route::post("create_user", [UserController::class, "create"]);

<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

include "sections/auth.php";
include "sections/types.php";
include "sections/sizes.php";
include "sections/genders.php";
include "sections/colors.php";
include "sections/warehouses.php";
include "sections/units.php";
include "sections/items.php";
//additional entities
include "sections/countries.php";
include "sections/cities.php";

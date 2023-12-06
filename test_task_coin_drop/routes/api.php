<?php

use App\Http\Controllers\api\CourseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:api')->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/course/{send_currency}/{receive_currency}', [CourseController::class, 'show']);
});

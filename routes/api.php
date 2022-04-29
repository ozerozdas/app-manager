<?php

use App\Http\Controllers\API\DefaultController;
use Illuminate\Support\Facades\Route;

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::middleware(['auth.basic.once'])->group(function () {
    Route::post('/register', [DefaultController::class, 'register'])->name('register');
    Route::post('/purchase', [DefaultController::class, 'purchase'])->name('purchase');
});

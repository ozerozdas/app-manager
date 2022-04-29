<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MockAPI\DefaultController;

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::middleware(['auth.basic.once'])->group(function () {
    Route::post('/check', [DefaultController::class, 'check'])->name('mock.check');
    Route::post('/purchase', [DefaultController::class, 'purchase'])->name('mock.purchase');
});

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/callback', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'status' => true,
        'data' => $request->all()
    ]);
});

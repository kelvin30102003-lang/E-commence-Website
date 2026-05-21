<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'laravel-backend',
    ]);
});

Route::get('/products', function () {
    return response()->json([
        ['id' => 1, 'name' => 'Cloudy Bunny Plushie', 'price' => 24.00],
        ['id' => 2, 'name' => 'Pastel Dreams Set', 'price' => 38.50],
        ['id' => 3, 'name' => 'Berry Glow Candle', 'price' => 14.00],
    ]);
});

Route::post('/contact', function (Request $request) {
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'subject' => ['required', 'string', 'max:255'],
        'message' => ['required', 'string', 'max:5000'],
    ]);

    // TODO: Persist to MySQL table or queue email notification.
    return response()->json([
        'message' => 'Contact request received.',
        'payload' => $validated,
    ], 201);
});
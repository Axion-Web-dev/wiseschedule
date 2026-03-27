<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/timezone-detect', function (Request $request) {
    $timezone = $request->input('timezone');

    if (in_array($timezone, timezone_identifiers_list())) {
        session(['user_timezone' => $timezone]);
    }
    
    return response()->json(['success' => true]);
})->middleware('web');
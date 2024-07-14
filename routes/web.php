<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => true,
        'message' => "Contact Admin for The API Documentation."
    ], 200);
    // return view('welcome');
});

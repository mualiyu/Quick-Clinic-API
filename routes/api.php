<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
});

// Routes for Patients
Route::middleware(['auth:sanctum', 'can:patient'])->prefix('/patient')->group(function () {

    Route::post('/profile', [PatientController::class, 'storeOrUpdateProfile']);
    Route::get('/profile', [PatientController::class, 'showProfile']);
    Route::delete('/profile', [PatientController::class, 'deleteProfile']);
    Route::delete('/account', [PatientController::class, 'deleteAccount']);
});

// Routes for Doctors
Route::middleware(['auth:sanctum', 'can:doctor'])->prefix('/doctor')->group(function () {

    Route::post('/profile', [DoctorController::class, 'storeOrUpdateProfile']);
    Route::get('/profile', [DoctorController::class, 'showProfile']);
    Route::delete('/profile', [DoctorController::class, 'deleteProfile']);
    Route::delete('/account', [DoctorController::class, 'deleteAccount']);
});

// Routes for Doctors
Route::middleware(['auth:sanctum', 'can:admin'])->prefix('/admin')->group(function () {

    // Route::post('/profile', [DoctorController::class, 'storeOrUpdateProfile']);
    // Route::get('/profile', [DoctorController::class, 'showProfile']);
    // Route::delete('/profile', [DoctorController::class, 'deleteProfile']);
    // Route::delete('/account', [DoctorController::class, 'deleteAccount']);
});

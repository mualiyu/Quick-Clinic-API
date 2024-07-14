<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\LanguageSupportController;
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

// Default's (LanguageSupport & )
Route::get('/languages', [LanguageSupportController::class, 'index']);


// Routes for Patients
Route::middleware(['auth:sanctum'])->prefix('/patient')->group(function () {

    Route::post('/profile', [PatientController::class, 'storeOrUpdateProfile']);
    Route::get('/profile', [PatientController::class, 'showProfile']);
    Route::delete('/profile', [PatientController::class, 'deleteProfile']);
    Route::delete('/account', [PatientController::class, 'deleteAccount']);
});

// Routes for Doctors
Route::middleware(['auth:sanctum'])->prefix('/doctor')->group(function () {

    Route::post('/profile', [DoctorController::class, 'storeOrUpdateProfile']);
    Route::get('/profile', [DoctorController::class, 'showProfile']);
    Route::delete('/profile', [DoctorController::class, 'deleteProfile']);
    Route::delete('/account', [DoctorController::class, 'deleteAccount']);
});

// Routes for Doctors
Route::middleware(['auth:sanctum'])->prefix('/admin')->group(function () {

    Route::prefix('/languages')->group(function () {
        Route::post('/store', [LanguageSupportController::class, 'addLanguage']);
        Route::delete('/{languageSupport}/delete', [LanguageSupportController::class, 'deleteLanguage']);

    });

    // Admin Patients section
    Route::prefix('/patients')->group(function () {
        Route::get('/list', [AdminController::class, 'get_all_patients']);

    });

    // Admin Doctors Section
    Route::prefix('/doctors')->group(function () {
        Route::get('/list', [AdminController::class, 'get_all_doctors']);

    });

    // Route::post('/profile', [DoctorController::class, 'storeOrUpdateProfile']);
    // Route::get('/profile', [DoctorController::class, 'showProfile']);
    // Route::delete('/profile', [DoctorController::class, 'deleteProfile']);
    // Route::delete('/account', [DoctorController::class, 'deleteAccount']);
});

<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\LanguageSupportController;
use App\Http\Controllers\PatientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth Routes
Route::post('/register', [RegisterController::class, 'register']);

Route::post('/verify_otp', [RegisterController::class, 'verify_otp']);

Route::post('/login', [LoginController::class, 'login']);

Route::post('/forgot-password', [RegisterController::class, 'forgot_password']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/reset-password', [RegisterController::class, 'password_reset']);

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

    // get doctors
    Route::get('/doctors-list', [PatientController::class, 'get_all_doctors']);

});


// Routes for Doctors
Route::middleware(['auth:sanctum'])->prefix('/doctor')->group(function () {

    Route::post('/profile', [DoctorController::class, 'storeOrUpdateProfile']);
    Route::get('/profile', [DoctorController::class, 'showProfile']);
    Route::delete('/profile', [DoctorController::class, 'deleteProfile']);
    Route::delete('/account', [DoctorController::class, 'deleteAccount']);

    Route::post('/profile/upload-file', [DoctorController::class, 'fileUpload']);

});

// Conversations routes
Route::middleware(['auth:sanctum'])->prefix('/conversations')->group(function () {
    // ai patient chat
    Route::prefix('/ai-patient-interaction')->group(function () {
        Route::post('/post', [ConversationController::class, 'post_ai_message']);
        Route::get('/list', [ConversationController::class, 'get_ai_messages']);
    });

    Route::get('/', [ConversationController::class, 'index']);
    Route::post('/', [ConversationController::class, 'store']);
    Route::get('/{conversation}', [ConversationController::class, 'show']);

    Route::post('/{conversation}/messages', [ConversationController::class, 'storeMsg']);
});


// Routes for Admin
Route::middleware(['auth:sanctum'])->prefix('/admin')->group(function () {
    Route::prefix('/languages')->group(function () {
        Route::post('/store', [LanguageSupportController::class, 'addLanguage']);
        Route::delete('/{languageSupport}/delete', [LanguageSupportController::class, 'deleteLanguage']);

    });
    Route::get('/list/all-users', [AdminController::class, 'get_all_registered_users']);
    // Admin Patients section
    Route::prefix('/patients')->group(function () {
        Route::get('/list', [AdminController::class, 'get_all_patients']);

    });
    // Admin Doctors Section
    Route::prefix('/doctors')->group(function () {
        Route::get('/list', [AdminController::class, 'get_all_doctors']);
        Route::post('/approve', [AdminController::class, 'approve_doctor']);
    });

    // Route::post('/profile', [DoctorController::class, 'storeOrUpdateProfile']);
    // Route::get('/profile', [DoctorController::class, 'showProfile']);
    // Route::delete('/profile', [DoctorController::class, 'deleteProfile']);
    // Route::delete('/account', [DoctorController::class, 'deleteAccount']);
});

<?php

use App\Http\Controllers\DoctorAppointmentController;
use App\Http\Controllers\PatientAppointmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => false,
        'message' => "Contact Admin for The Documentation."
    ], 200);
    // return view('welcome');
});

Route::get('/error-auth', function () {
    return response()->json([
        'status' => false,
        'message' => "Error Authentication failed."
    ], 401);
})->name('login');


Route::get('storage/{p}/{filename}', function ($p, $filename)
{
    $path = storage_path('app/public/'.$p.'/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});

Route::get('/pay/callback', function (Request $request) {
    return response()->json([
        'status' => true,
        'message' => $request->all(),
    ], 200);
})->name("pay.callback");

// Route::get("test/google/{appointment}", [DoctorAppointmentController::class, 'test']);

Route::get("payments/{appointment}", [PatientAppointmentController::class, 'initiate_appointment_payment'])->name('patients.payment.url');

Route::get("payments/{reference}/v", [PatientAppointmentController::class, 'handlePaystackCallbackWeb'])->name('patients.payment.callback');

// Route::get('ttt', function (){
//     $v = base64_encode("1");
//    return $v ."  \n ". base64_decode($v);
// });

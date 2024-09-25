<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    //
    public function store(Request $request, Appointment $appointment)
    {
        if ($request->user()->tokenCan('patient')) {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if ($appointment->patient_id !== $request->user()->patient->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized to review this appointment.',
                ], 403);
            }

            if ($appointment->status !== 'Completed') {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot review an appointment that is not completed.',
                ], 422);
            }

            if ($appointment->review()->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'A review for this appointment already exists.',
                ], 422);
            }

            $review = Review::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $request->user()->patient->id,
                'doctor_id' => $appointment->doctor_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Review submitted successfully.',
                'data' => $review,
            ], 201);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to Authorize Token!',
            ], 401);
        }
    }
}

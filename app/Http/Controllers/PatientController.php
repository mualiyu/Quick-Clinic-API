<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{
    public function storeOrUpdateProfile(Request $request)
    {
        if ($request->user()->tokenCan('patient')) {
            $user = $request->user();

            // Validate incoming request data
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'date_of_birth' => 'nullable',
                'gender' => 'nullable|string|in:Male,Female,Other',
                // 'contact_number' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'health_conditions' => 'nullable|string',
                'language_id' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Check if user already has a patient profile
            if ($user->patient()->exists()) {
                // Update existing profile
                $user->patient()->update([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'date_of_birth' => $request->date_of_birth,
                    'gender' => $request->gender,
                    // 'contact_number' => $request->contact_number,
                    'address' => $request->address,
                    'health_conditions' => $request->health_conditions,
                    'language_id' => $request->language_id,
                ]);
            } else {
                // Create new patient profile
                $patient = new Patient([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'date_of_birth' => $request->date_of_birth,
                    'gender' => $request->gender,
                    // 'contact_number' => $request->contact_number,
                    'address' => $request->address,
                    'health_conditions' => $request->health_conditions,
                    'language_id' => $request->language_id,
                ]);
                $user->patient()->save($patient);
            }

            return response()->json([
                'status' => true,
                'message' => 'Patient profile updated successfully',
                'data' => $user->patient, // Return updated patient profile if needed
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }

    public function showProfile(Request $request)
    {
        if ($request->user()->tokenCan('patient')) {
            $user = $request->user();

            if ($user->patient()->exists()) {
                return response()->json([
                    'status' => true,
                    'data' => $user->patient,
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => 'Patient profile not found'
            ], 404);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }

    public function deleteProfile(Request $request)
    {
        if ($request->user()->tokenCan('patient')) {
            $user = $request->user();

            if ($user->patient()->exists()) {
                $user->patient()->delete();

                return response()->json([
                    'status' => true,
                    'message' => 'Patient profile deleted successfully'
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => 'Patient profile not found'
            ], 404);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }

    public function deleteAccount(Request $request)
    {
        if ($request->user()->tokenCan('patient')) {
            $user = $request->user();

            if ($user) {
                $user->update([
                    'is_active' => 0, // Deactivate account
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'User Account has been deleted'
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => 'User account not found'
            ], 404);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }

    public function get_all_doctors(Request $request)
    {
        if ($request->user()->tokenCan('patient')) {

            $doctors = Doctor::all();
            if (count($doctors) > 0) {
                return response()->json([
                    'status' => true,
                    'message' => "List of all doctors below",
                    'data' => $doctors,
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "No Doctor is found!"
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }

    public function get_single_doctors(Request $request, Doctor $doctor)
    {
        if ($request->user()->tokenCan('patient')) {

            if ($doctor) {
                return response()->json([
                    'status' => true,
                    'data' => $doctor,
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "Doctor is not found!"
                ], 422);
            }

        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to Authorize Token!',
            ], 401);
        }
    }

}

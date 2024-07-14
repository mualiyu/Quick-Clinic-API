<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    public function storeOrUpdateProfile(Request $request)
    {
        if ($request->user()->tokenCan('doctor')) {

            $user = $request->user(); // Assuming user is authenticated

            // Validate incoming request data
            $validator = Validator::make($request->all(), [
                'firstname' => 'required|string',
                'lastname' => 'required|string',
                'specialization' => 'required|string',
                'licensenumber' => 'required|string',
                'contactnumber' => 'nullable|string',
                'address' => 'nullable|string|max:255',
                'language_id' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Check if user already has a doctor profile
            if ($user->doctor()->exists()) {
                // Update existing profile
                $user->doctor()->update([
                    'firstname' => $request->firstname,
                    'lastname' => $request->lastname,
                    'specialization' => $request->specialization,
                    'licensenumber' => $request->licensenumber,
                    'contactnumber' => $request->contactnumber,
                    'address' => $request->address,
                    'language_id' => $request->language_id,
                ]);
            } else {
                // Create new doctor profile
                $doctor = new Doctor([
                    'user_id' => $user->id,
                    'language_id' => $request->language_id,
                    'firstname' => $request->firstname,
                    'lastname' => $request->lastname,
                    'specialization' => $request->specialization,
                    'licensenumber' => $request->licensenumber,
                    'contactnumber' => $request->contactnumber,
                    'address' => $request->address,
                ]);
                $user->doctor()->save($doctor);
            }

            return response()->json([
                'status' => true,
                'data' => $user->doctor, // Return updated doctor profile if needed
                'message' => 'Doctor profile updated successfully',
            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }

    // Retrieve doctor profile
    public function showProfile(Request $request)
    {
        if ($request->user()->tokenCan('doctor')) {

            $user = $request->user(); // Assuming user is authenticated

            if ($user->doctor()->exists()) {
                return response()->json([
                    'status' => true,
                    'data' => $user->doctor // Return doctor profile
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => 'Doctor profile not found'
            ], 404);
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }

    // Delete doctor profile
    public function deleteProfile(Request $request)
    {
        if ($request->user()->tokenCan('doctor')) {

            $user = $request->user(); // Assuming user is authenticated

            if ($user->doctor()->exists()) {

                $user->doctor()->delete();

                return response()->json([
                    'status' => true,
                    'message' => 'Doctor profile deleted successfully'
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => 'Doctor profile not found'
            ], 404);
        }

    }

    public function deleteAccount(Request $request)
    {
        if ($request->user()->tokenCan('doctor')) {
            $user = $request->user(); // Assuming user is authenticated

            if ($user) {
                $user->update([
                    'is_active' => 0,
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
        }

    }
}

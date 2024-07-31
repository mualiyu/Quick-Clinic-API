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
                'language_id' => 'nullable|string',
                'first_name' => 'required|string',
                'last_name' => 'nullable|string',
                'specialization' => 'required|string',
                'license_number' => 'required|string',
                'address' => 'nullable|string',
                'gender' => 'nullable|string',
                'education_qualifications' => 'nullable|string',
                'years_of_experience' => 'nullable|string',
                'doctor_description' => 'nullable|string',
                'basic_pay_amount' => 'nullable|string',
                'id_card' => 'nullable|string',
                'license_document' => 'nullable|string',
                'document1' => 'nullable|string',
                'document2' => 'nullable|string',
                'document3' => 'nullable|string',
                'document4' => 'nullable|string',
                'document5' => 'nullable|string',
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
                    'language_id' => $request->language_id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'specialization' => $request->specialization,
                    'license_number' => $request->license_number,
                    // 'phone' => $request->phone,
                    'address' => $request->address,
                    'gender' => $request->gender,
                    'education_qualifications' => $request->education_qualifications,
                    'years_of_experience' => $request->years_of_experience,
                    'doctor_description' => $request->doctor_description,
                    'basic_pay_amount' => $request->basic_pay_amount,
                    'id_card' => $request->id_card,
                    'license_document' => $request->license_document,
                    'document1' => $request->document1,
                    'document2' => $request->document2,
                    'document3' => $request->document3,
                    'document4' => $request->document4,
                    'document5' => $request->document5,
                ]);
            } else {
                // Create new doctor profile
                $doctor = new Doctor([
                    'user_id' => $user->id,
                    'language_id' => $request->language_id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'specialization' => $request->specialization,
                    'license_number' => $request->license_number,
                    // 'phone' => $request->phone,
                    'address' => $request->address,
                    'gender' => $request->gender,
                    'education_qualifications' => $request->education_qualifications,
                    'years_of_experience' => $request->years_of_experience,
                    'doctor_description' => $request->doctor_description,
                    'basic_pay_amount' => $request->basic_pay_amount,
                    'id_card' => $request->id_card,
                    'license_document' => $request->license_document,
                    'document1' => $request->document1,
                    'document2' => $request->document2,
                    'document3' => $request->document3,
                    'document4' => $request->document4,
                    'document5' => $request->document5,
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

    public function fileUpload(Request $request)
    {
            $validator = Validator::make($request->all(), [
                'file' => 'required|max:9000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if ($request->hasFile("file")) {
                $fileNameWExt = $request->file("file")->getClientOriginalName();
                $fileName = pathinfo($fileNameWExt, PATHINFO_FILENAME);
                $fileExt = $request->file("file")->getClientOriginalExtension();
                $fileNameToStore = $fileName."_".time().".".$fileExt;
                $request->file("file")->storeAs("public/doctors", $fileNameToStore);

                $url = url('/storage/doctors/'.$fileNameToStore);

                return response()->json([
                    'status' => true,
                    'message' => "File is successfully uploaded.",
                    'data' => [
                        'url' => $url,
                    ],
                ]);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Error! File upload invalid. Try again."
                ], 422);
            }
    }
}

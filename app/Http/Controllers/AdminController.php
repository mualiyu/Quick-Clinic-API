<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpParser\Comment\Doc;

class AdminController extends Controller
{
    // public function get_all_patients(Request $request)
    // {
    //     if ($request->user()->tokenCan('admin')) {

    //     }else {
    //         return response()->json([
    //             'status' => false,
    //             'message' => trans('Failed to Authorize Token!')
    //         ], 401);
    //     }
    // }

    public function get_all_registered_users(Request $request)
    {
        if ($request->user()->tokenCan('admin')) {
            $patients = User::where(['role' => 'patient'])->get();
            $doctors = User::where(['role' => 'doctor'])->get();
            $admins = User::where(['role' => 'admin'])->get();

            $data = [
                'patients' => $patients,
                'doctors' => $doctors,
                'admins' => $admins,
            ];

            if (count($patients) > 0 || count($doctors) > 0 || count($admins) > 0) {
                return response()->json([
                    'status' => false,
                    'data' => $data,
                    'message' => "List of all users below"
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "No registered user is found!"
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }

    public function get_all_patients(Request $request)
    {
        if ($request->user()->tokenCan('admin')) {
            $patients = Patient::all();

            if (count($patients) > 0) {
                return response()->json([
                    'status' => true,
                    'data' => $patients,
                    'message' => "List of all patients below"
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "No Patient is found!"
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }

    // get all doctors

    public function get_all_doctors(Request $request)
    {
        if ($request->user()->tokenCan('admin')) {
            $doctors = Doctor::all();

            if (count($doctors) > 0) {
                return response()->json([
                    'status' => true,
                    'data' => $doctors,
                    'message' => "List of all doctors below"
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

    public function approve_doctor(Request $request)
    {
        if ($request->user()->tokenCan('admin')) {
            // Validate incoming request data
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'required|string',
                'is_approved' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $doctor = Doctor::find($request->doctor_id);

            if ($doctor) {
                $doctor->update([
                    "is_approved" => $request->is_approved,
                ]);
                return response()->json([
                    'status' => true,
                    'data' => $doctor,
                    'message' => $request->is_approved == 1 ? "Doctor has been approved":"Doctor has been disapproved"
                ], 200);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => trans('Failed! Doctor is not in our system.')
                ], 422);
            }


        } else {
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }
}

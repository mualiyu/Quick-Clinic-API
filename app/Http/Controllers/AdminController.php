<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
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

    public function get_all_patients(Request $request)
    {
        if ($request->user()->tokenCan('admin')) {
            $patients = Patient::all();

            if (count($patients)>0) {
                return response()->json([
                    'status' => false,
                    'data' =>$patients,
                    'message' => "List of all patients below"
                ], 200);
            }else {
                return response()->json([
                    'status' => false,
                    'message' => "No Patient is found!"
                ], 422);
            }
        }else {
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

            if (count($doctors)>0) {
                return response()->json([
                    'status' => false,
                    'data' =>$doctors,
                    'message' => "List of all doctors below"
                ], 200);
            }else {
                return response()->json([
                    'status' => false,
                    'message' => "No Doctor is found!"
                ], 422);
            }
        }else {
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }
}

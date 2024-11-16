<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Services\MukeeyMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
                    'message' => $request->is_approved == 1 ? "Doctor has been approved" : "Doctor has been disapproved"
                ], 200);
            } else {
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

    public function register_sys_doctor(Request $request)
    {
        if ($request->user()->tokenCan('admin')) {

            $validator = Validator::make($request->all(), [
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'phone' => 'required|string|unique:users',
                // 'role' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $otp = mt_rand(100000, 999999);

            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => "sys_doctor",
                'phone' => $request->phone,
                'is_active' => 1,
                'otp' => $otp,
            ]);

            if ($user) {

                $mailData = [
                    'title' => 'Welcome to Quick Clinic - System Consultant Registration',
                    'body' => [
                        "Dear $user->email,",
                        "",
                        "Welcome to Quick Clinic! We are delighted to have you join us as a System Consultant.",
                        "",
                        "As part of our mission to provide accessible healthcare, we are thrilled to have your expertise in offering free services to patients under the age of 18. Your role is crucial in helping us ensure that every young patient receives the care they need.",
                        "",
                        "To get started, please use the following credentials to access your dashboard:",
                        "",
                        "Email: $user->email",
                        "Password: $request->password",
                        "",
                        "Please log in to your account and update your password for security purposes. If you did not sign up for this role, please disregard this email.",
                        "",
                        "For any questions or support, feel free to reach out to our team at support@quick-clinic.org.",
                        "",
                        "Thank you for partnering with us in this meaningful journey to improve the health and well-being of our young patients.",
                        "",
                        // "Best regards,",
                        // "The Quick Clinic Team",
                    ],
                ];

                try {
                    MukeeyMailService::send($user->email, $mailData);
                    // PHPMailerService::send($user->email, $mailData);
                    // Mail::to($user->email)->send(new RegistrationMail($mailData));
                } catch (\Throwable $th) {
                    // Log the error for debugging
                }
            }

            return response()->json([
                'status' => true,
                'data' => $user,
                'message' => 'System Consultant has been registered successfully, An email has been sent to his email (' . $user->email . ')',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('Failed to Authorize Token!')
            ], 401);
        }
    }
}

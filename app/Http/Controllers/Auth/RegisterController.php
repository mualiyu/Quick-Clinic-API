<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string',
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
            'role' => $request->role,
            'is_active' => 0,
            'otp' => $otp,
        ]);

        if ($user) {
            $mailData = [
                'title' => 'Quick Clinic - Welcome',
                'body' => [
                    "Dear $user->email,",
                    "Welcome to Quick Clinic! We are thrilled to have you join our community.",
                    "To get started, please verify your email address by using the code below. This will ensure you can fully access all the features and benefits of your new account.",
                    "Your Verification Token Code: $otp",
                    "Please enter this code on the verification page of our platform. If you did not create an account, please disregard this email.",
                    "If you have any questions or need assistance, feel free to reach out to our support team at support@quick-clinic.org",
                    "Thank you for joining Quick Clinic. We look forward to helping you achieve your health goals!",
                    // "Best regards",
                ],
            ];

            // Mail::to($user->email)->send(new RegistrationMail($mailData));
        }

        return response()->json([
            'status' => true,
            'data' => $user,
            'otp' => $otp,
            'message' => 'User registered successfully',
        ], 200);
    }

    public function verify_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = User::where(['email' => $request->email, 'otp' => $request->otp])->get();
        // $user = User::where('otp', '=', $request->otp)->get();

        if (count($user) > 0) {
            $user = $user[0];

            $update = User::where(['email' => $request->email, 'otp' => $request->otp])->update([
                'is_active' => 1,
                'otp' => '',
            ]);

            $user = User::find($user->id);

            $token = $user->createToken($user->role, [$user->role])->plainTextToken;

            return response()->json([
                'status' => true,
                'data' => $user,
                'token' => $token,
                'message' => 'OTP verified successfully',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Oops, The OTP provided does not match our record. Try again!',
            ], 401);
        }
    }

    public function forgot_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = User::where(['email' => $request->email])->get();

        if (count($user) > 0) {
            $user = $user[0];
            $otp = mt_rand(100000, 999999);

            $update = User::where(['email' => $request->email])->update([
                'otp' => $otp,
            ]);

            if ($update) {
                $mailData = [
                    'title' => 'Quick Clinic Password Reset Request',
                    'body' => [
                        "Dear $user->email,",
                        "We received a request to reset your password for your Quick Clinic account. If you did not make this request, please ignore this email.",
                        "To reset your password, please use the verification token code provided below:",
                        "Your Password Reset Token Code: $otp",
                        "Please enter this code on the password reset page of our app. Once verified, you will be able to create a new password.",
                        "If you have any questions or need assistance, feel free to reach out to our support team at support@quick-clinic.org",
                        "Thank you for using Quick Clinic.",
                        // "Best regards",
                    ],
                ];
                // Mail::to($user->email)->send(new RegistrationMail($mailData));

                return response()->json([
                    'status' => true,
                    'otp' => $otp,
                    'message' => 'Email has been sent to you with futher instructions.',
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'data' => [],
                    'message' => 'Failed to send instructions to your email. Try again!',
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'data' => [],
                'message' => 'Oops, No user found with this email. Try again!',
            ], 401);
        }
    }

    public function password_reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string',
            'new_password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        if ($request->new_password == $request->new_password_confirmation) {
            $request->user()->update([
                'password' => Hash::make($request->new_password),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Successful, Password has been changed.',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => "Oops, The two passwords doesn't match. Try again!",
            ], 422);
        }
    }
}

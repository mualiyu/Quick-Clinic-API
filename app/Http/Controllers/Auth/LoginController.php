<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
        // $user = Applicant::where('username', $request->username)->first();
        $user = User::where('username', '=', $request->username)->get();

        if (!count($user) > 0) {
            $user = User::where('email', '=', $request->username)->get();
        } else {
            $user = User::where('username', '=', $request->username)->get();
        }
        $user = $user[0];

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => "Authentication Failed..."
            ], 401);
        }

        if ($user->is_active == 1) {
            return response()->json([
                'status' => true,
                'data' => [
                    'user' => $user,
                    'token' => $user->createToken($user->role, [$user->role])->plainTextToken
                ],
                'message' => 'Login successfull.'
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => "Sorry, your Account is not activated, Try again later..."
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'status' => true,
            'data' => [],
            'message' => 'Logged out successfully',
        ], 200);
    }
}

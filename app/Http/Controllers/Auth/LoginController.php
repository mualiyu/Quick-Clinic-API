<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if (!Auth::attempt($request->only('username', 'password'))) {
            return response()->json([
                'status' => true,
                'data' => [],
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();

        if (!$user->is_active == 1) {
            return response()->json([
                'status' => true,
                'data' => [],
                'message' => 'Your Account has been deactivated or deleted, please contact admin at support@quickclinic.com for support. Thank you',
            ], 401);
        }
        $token = $user->createToken($user->role)->plainTextToken;

        return response()->json([
            'status' => true,
            'data' => $user,
            'token' => $token,
            'message' => 'Login successfully',
        ], 200);
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

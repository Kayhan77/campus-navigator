<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // -------------------------
    // REGISTER
    // -------------------------
    public function register(Request $request)
    {
        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed', // password_confirmation field required
        ]);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return response
        return response()->json([
            'status' => true,
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // -------------------------
    // LOGIN
    // -------------------------
    public function login(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'user' => $user,
            'token' => $token,
        ]);
    }

    // -------------------------
    // LOGOUT
    // -------------------------
    public function logout(Request $request)
    {
        $user = $request->user(); // Authenticated user

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        // Delete all tokens for this user
        $user->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}

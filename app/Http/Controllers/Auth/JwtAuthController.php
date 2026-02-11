<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;


class JwtAuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
{
    $data = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6|confirmed',
    ]);

    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
    ]);

    // Generate JWT token
    $token = JWTAuth::fromUser($user);

    return response()->json([
        'status' => true,
        'user' => $user,
        'access_token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => JWTAuth::factory()->getTTL() * 60
    ]);
}


    /**
     * Login user and return JWT
     */
    public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    if (! $token = JWTAuth::attempt($credentials)) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    return response()->json([
        'status' => true,
        'user' => auth('api')->user(),
        'access_token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => JWTAuth::factory()->getTTL() * 60
    ]);
}


    /**
     * Get authenticated user
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Logout (invalidate token)
     */
    public function logout()
    {
        /** @var JWTGuard $auth */
        $auth = auth('api');
        $auth->logout();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Refresh JWT token
     */
    public function refresh()
    {
        /** @var JWTGuard $auth */
        $auth = auth('api');

        return $this->respondWithToken($auth->refresh());
    }

    /**
     * Token response structure
     */
    protected function respondWithToken(string $token)
    {
        /** @var JWTGuard $auth */
        $auth = auth('api');

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60,
        ]);
    }
}

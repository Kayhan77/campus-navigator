<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    public function redirect()
    {
        $url = Socialite::driver('google')->redirect()->getTargetUrl();

        return response()->json([
            'authorization_url' => $url
        ]);
    }

    public function callback(Request $request)
    {
        try {
            // Get the authorization code from the request
            if (!$request->has('code')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization code not provided',
                ], 400);
            }

            $googleUser = Socialite::driver('google')->user();

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'password' => bcrypt(uniqid()),
                    'is_verified' => true,
                    'email_verified_at' => now(),
                ]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Google authentication successful',
                'user' => $user,
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google authentication failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }
}


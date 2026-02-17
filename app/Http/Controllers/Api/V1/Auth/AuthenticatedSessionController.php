<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Log in a user (session-based).
     */
    public function store(LoginRequest $request): Response
    {
        $request->authenticate();
        $request->session()->regenerate();

        return response()->noContent();
    }

    /**
     * Log out the authenticated user.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}

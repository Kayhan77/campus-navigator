<?php 

namespace App\Http\Controllers\Api\V1\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function makeAdmin(Request $request, User $user)
    {
        $this->authorize('makeAdmin', User::class); // only admin can do this

        $user->role = 'admin';
        $user->save();

        return response()->json([
            'message' => "{$user->name} is now an admin.",
            'user' => $user
        ]);
    }
}
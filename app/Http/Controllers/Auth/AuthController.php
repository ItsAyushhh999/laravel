<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schedule;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

    $user = Auth::user();

    // Give specific ability
    $token = $user->createToken(
        'api-token', ['server:update'],   // abilities
        now() ->addWeek()             // expiration
    )->plainTextToken;

    return response()->json([
        'token' => $token
    ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
    ]);
    }

    public function revokeAllTokens(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'All tokens revoked']);
    }

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('sanctum:prune-expired --hours=24')->daily();
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    // Revoke all tokens
    public function revokeAll(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'All tokens revoked.']);
    }

    // Revoke a specific token
    public function revoke(Request $request, $tokenId)
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();
        return response()->json(['message' => 'Token revoked.']);
    }
}

<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/tokens/create', function (Request $request) {
    $token = $request->user()->createToken($request->token_name);
 
    return ['token' => $token->plainTextToken];
});

Route::get('/orders', function () {
    return "You can check status AND place orders";
})->middleware(['auth:sanctum', 'abilities:check-status,place-orders']);

Route::post('/server/update', function (Request $request) {

    if ($request->user()->tokenCan('server:update')) {
        return "Server updated successfully";
    }

    return response()->json(['message' => 'Not allowed'], 403);

})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/logout-all', [AuthController::class, 'revokeAllTokens'])->middleware('auth:sanctum');
Route::delete('/revoke/{tokenId}', [AuthController::class, 'revokeToken'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/tokens', [ApiTokenController::class, 'revokeAll']);
    Route::delete('/tokens/{id}', [ApiTokenController::class, 'revoke']);
});

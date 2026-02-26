<?php

use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Authenticated user with Sanctum token
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Authenticated user with HTTP Basic once
Route::get('/user', function () {
    return auth()->user();
})->middleware('auth.once.basic');

// Login route
Route::post('/login', [\App\Http\Controllers\Auth\AuthController::class, 'login']);
Route::post('/logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])->middleware('auth:sanctum');

//Protecting Routes
Route::middleware(['auth:sanctum'])->group (function(){
    Route::apiResource('projects', \App\Http\Controllers\ProjectController::class);
    Route::apiResource('tasks', \App\Http\Controllers\TaskController::class);
}
);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/tasks', [TaskController::class, 'store'])
        ->middleware('ability:server:update');

    Route::put('/tasks/{task}', [TaskController::class, 'update'])
        ->middleware('ability:server:update');

    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])
        ->middleware('ability:admin:update');

});



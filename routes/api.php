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
}
);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    //->middleware('ability:task:update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])
    ->middleware('auth:sanctum');
});

Route::middleware(['auth:sanctum'])->group(function (){
    Route::post('/projects', [\App\Http\Controllers\ProjectController::class, 'store']);
    Route::get('/projects/{project}', [\App\Http\Controllers\ProjectController::class, 'show']);
    Route::put('/projects/{project}', [\App\Http\Controllers\ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [\App\Http\Controllers\ProjectController::class, 'destroy']);
});

/*Route::get('/test', function () {
    return \App\Models\Task::with('assignee', 'reviewer', 'attachments')->get();});
*/

Route::get('/tasks', [TaskController::class, 'index'])
->middleware('auth:sanctum');


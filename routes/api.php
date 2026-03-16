<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\JsonPlaceholderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

// Protecting Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('projects', \App\Http\Controllers\ProjectController::class);
}
);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::get('/tasks', [TaskController::class, 'index']);

    // batch routes
    Route::post('/posts/batch/fetch', [TaskController::class, 'getManyPosts']);
    Route::post('/posts/batch/create', [TaskController::class, 'createManyPosts']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/projects', [\App\Http\Controllers\ProjectController::class, 'store']);
    Route::get('/projects/{project}', [\App\Http\Controllers\ProjectController::class, 'show']);
    Route::put('/projects/{project}', [\App\Http\Controllers\ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [\App\Http\Controllers\ProjectController::class, 'destroy']);
});

Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/tasks/{task}/comments', [CommentController::class, 'store']);
    Route::post('/comments/{comment}/replies', [CommentController::class, 'reply']);
    Route::get('/tasks/{task}/comments', [CommentController::class, 'index']);
    Route::get('/comments/{comment}/replies', [CommentController::class, 'indexReplies']);
});

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/all', [NotificationController::class, 'all']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});

Route::prefix('placeholder')->group(function () {
    // For Http Client
    Route::get('/posts/{id}', [JsonPlaceholderController::class, 'show']);
    Route::delete('/posts/{id}', [JsonPlaceholderController::class, 'destroy']);
    Route::get('/users/{id}/retry', [JsonPlaceholderController::class, 'userWithRetry']);
    Route::get('/dashboard', [JsonPlaceholderController::class, 'dashboardData']);
    Route::get('/albums/{id}', [JsonPlaceholderController::class, 'showAlbum']);

    // For Process
    Route::get('/process/sync', [JsonPlaceholderController::class, 'processSync']);
    Route::get('/process/list-dir/{dir?}', [JsonPlaceholderController::class, 'listDirectory'])
        ->where('dir', '.*');
    Route::get('/process/stream', [JsonPlaceholderController::class, 'processStream']);
    Route::get('/process/async', [JsonPlaceholderController::class, 'processAsync']);
    Route::get('/process/concurrent', [JsonPlaceholderController::class, 'processConcurrent']);

    // Combined
    Route::get('/users/{userId}/fetch-and-process', [JsonPlaceholderController::class, 'fetchAndProcess']);
});

// For testing localstack
Route::get('/test-s3', function () {
    Storage::disk('s3')->put('hello.txt', 'LocalStack is working!');
    $exists = Storage::disk('s3')->exists('hello.txt');

    return response()->json(['s3_working' => $exists]);
});

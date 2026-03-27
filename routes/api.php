<?php

// use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\JsonPlaceholderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Services\SnsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Authenticated user with Sanctum token
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Login route
Route::post('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);
Route::post('/login', [\App\Http\Controllers\Auth\AuthController::class, 'login']);
Route::post('/logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::get('/tasks', [TaskController::class, 'index']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/projects', [\App\Http\Controllers\ProjectController::class, 'index']);
    Route::post('/projects', [\App\Http\Controllers\ProjectController::class, 'store']);
    Route::get('/projects/{project}', [\App\Http\Controllers\ProjectController::class, 'show']);
    Route::put('/projects/{project}', [\App\Http\Controllers\ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [\App\Http\Controllers\ProjectController::class, 'destroy']);
});

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
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
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

// For testing SNS
Route::get('/test-sns', function () {
    $sns = new SnsService;
    $result = $sns->publish('Hello from Laravel SNS!', 'Test Notification');

    return response()->json(['message_id' => $result['MessageId']]);
});

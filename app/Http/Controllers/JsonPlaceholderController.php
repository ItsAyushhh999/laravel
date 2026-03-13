<?php

namespace App\Http\Controllers;

use App\Services\JsonPlaceholderService;
use Illuminate\Http\JsonResponse;

class JsonPlaceholderController extends Controller
{
    public function __construct(private JsonPlaceholderService $service)
    {
        //
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->getPost($id));
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(
            ['deleted' => $this->service->deletePost($id)]
        );
    }

    public function userWithRetry(int $id): JsonResponse
    {
        return response()->json($this->service->getUserWithRetry($id));
    }

    public function dashboardData(): JsonResponse
    {
        return response()->json($this->service->fetchDashboardData());
    }

    public function showAlbum(int $id): JsonResponse
    {
        return response()->json($this->service->getAlbum($id));
    }

    public function processSync(): JsonResponse
    {
        return response()->json($this->service->runSyncProcess());
    }

    public function listDirectory(string $dir = '/tmp'): JsonResponse
    {
        return response()->json($this->service->listDirectory($dir));
    }

    public function processStream(): JsonResponse
    {
        return response()->json($this->service->streamProcessOutput());
    }

    public function processAsync(): JsonResponse
    {
        return response()->json($this->service->runAsyncProcess());
    }

    public function processConcurrent(): JsonResponse
    {
        return response()->json($this->service->runConcurrentProcesses());
    }

    public function fetchAndProcess(int $userId): JsonResponse
    {
        return response()->json($this->service->fetchAndProcessWithShell($userId));
    }
}

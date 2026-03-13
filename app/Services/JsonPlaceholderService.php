<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class JsonPlaceholderService
{
    protected string $baseUrl = 'https://jsonplaceholder.typicode.com';

    public function getPost(int $id): array
    {
        $response = Http::baseUrl($this->baseUrl)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(10)
            ->get("/posts/{$id}");

        $response->throw();

        return [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'data' => $response->json(),
        ];
    }

    public function deletePost(int $id): bool
    {
        $response = Http::baseUrl($this->baseUrl)
            ->timeout(10)
            ->delete("/posts/{$id}");

        $response->throw();

        return $response->ok();
    }

    public function getUserWithRetry(int $id): array
    {
        $response = Http::baseUrl($this->baseUrl)
            ->timeout(10)
            ->retry(
                times: 3,
                sleepMilliseconds: 200,
                when: null,
                throw: true
            )
            ->get("/users/{$id}");

        return $response->json();
    }

    /**
     * Fetch posts, comments, and todos simultaneously.
     * Demonstrates: Http::pool() for concurrent requests.
     */
    public function fetchDashboardData(): array
    {
        $responses = Http::pool(fn (Pool $pool) => [
            $pool->as('posts')->get("{$this->baseUrl}/posts", ['_limit' => 5]),
            $pool->as('comments')->get("{$this->baseUrl}/comments", ['_limit' => 5]),
            $pool->as('todos')->get("{$this->baseUrl}/todos", ['userId' => 1]),
            $pool->as('users')->get("{$this->baseUrl}/users"),
        ]);

        return [
            'posts' => $responses['posts']->json(),
            'comments' => $responses['comments']->json(),
            'todos' => $responses['todos']->json(),
            'users' => $responses['users']->json(),
        ];
    }

    /**
     * Using a pre-configured Guzzle option (verify SSL, connect timeout).
     * Demonstrates: withOptions() for raw Guzzle options.
     */
    public function getAlbum(int $id): array
    {
        $response = Http::baseUrl($this->baseUrl)
            ->withOptions([
                'connect_timeout' => 5,
                'verify' => true,
            ])
            ->timeout(15)
            ->get("/albums/{$id}");

        $response->throw();

        return $response->json();
    }

    public function runSyncProcess(): array
    {
        $result = Process::run('echo "Laravel Processes work!"');

        return [
            'command' => $result->command(),
            'output' => trim($result->output()),
            'errorOutput' => $result->errorOutput(),
            'successful' => $result->successful(),
            'exitCode' => $result->exitCode(),
        ];
    }

    public function listDirectory(string $dir = '/tmp'): string
    {
        $result = Process::path($dir)
            ->timeout(30)
            ->run('ls -la');

        // Throw only if the process failed
        $result->throwIf($result->failed());

        return $result->output();
    }

    public function runWithEnv(): array
    {
        $result = Process::env([
            'API_BASE' => $this->baseUrl,
            'APP_ENV' => app()->environment(),
        ])
            ->timeout(30)
            ->run('printenv API_BASE');

        return [
            'successful' => $result->successful(),
            'output' => trim($result->output()),
        ];
    }

    public function streamProcessOutput(): array
    {
        $result = Process::run('for i in 1 2 3; do echo "Line $i"; done');

        return array_values(
            array_filter(
                array_map('trim', explode("\n", $result->output()))
            )
        );
    }

    public function runAsyncProcess(): array
    {
        // Start the process without blocking
        $process = Process::timeout(30)->start('sleep 1 && echo "Async done"');

        // Do other work concurrently (e.g. fetch data while process runs)
        $apiData = Http::get("{$this->baseUrl}/todos/1")->json();

        // Now wait for the process to finish
        $result = $process->wait();

        return [
            'process_output' => trim($result->output()),
            'process_ok' => $result->successful(),
            'api_data' => $apiData,
        ];
    }

    public function runConcurrentProcesses(): array
    {
        [$posts, $users, $todos] = Process::concurrently(function (\Illuminate\Process\Pool $pool) {
            $pool->command('curl -s https://jsonplaceholder.typicode.com/posts');
            $pool->command('curl -s https://jsonplaceholder.typicode.com/users');
            $pool->command('curl -s https://jsonplaceholder.typicode.com/todos');
        });

        return [
            'posts' => json_decode($posts->output(), true),
            'users' => json_decode($users->output(), true),
            'todos' => json_decode($todos->output(), true),
        ];
    }

    public function fetchAndProcessWithShell(int $userId): array
    {
        // 1. HTTP: fetch posts for user
        $posts = Http::baseUrl($this->baseUrl)
            ->timeout(10)
            ->get('/posts', ['userId' => $userId])
            ->throw()
            ->body();           // raw JSON string

        // 2. Process: pipe the JSON through `wc -c` to count bytes
        $result = Process::input($posts)
            ->timeout(10)
            ->run('wc -c');

        $byteCount = (int) trim($result->output());

        return [
            'userId' => $userId,
            'postCount' => count(json_decode($posts, true)),
            'jsonBytes' => $byteCount,
            'processOk' => $result->successful(),
        ];
    }
}

<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

test('it fetches a post', function () {
    Http::fake([
        'jsonplaceholder.typicode.com/posts/1' => Http::response([
            'id' => 1,
            'userId' => 1,
            'title' => 'Fake title',
            'body' => 'Fake body',
        ], 200),
    ]);

    $response = $this->getJson('/api/placeholder/posts/1');

    $response->assertOk()
        ->assertJsonPath('data.id', 1)
        ->assertJsonPath('data.title', 'Fake title');

    Http::assertSent(fn ($request) => $request->url() === 'https://jsonplaceholder.typicode.com/posts/1'
    );
});

test('it deletes a post', function () {
    Http::fake([
        'jsonplaceholder.typicode.com/posts/1' => Http::response([], 200),
    ]);

    $response = $this->deleteJson('/api/placeholder/posts/1');

    $response->assertOk()
        ->assertJsonPath('deleted', true);
});

test('it fetches a user with retry', function () {
    Http::fake([
        'jsonplaceholder.typicode.com/users/1' => Http::response([
            'id' => 1,
            'name' => 'Leanne Graham',
        ], 200),
    ]);

    $response = $this->getJson('/api/placeholder/users/1/retry');

    $response->assertOk()
        ->assertJsonPath('id', 1)
        ->assertJsonPath('name', 'Leanne Graham');
});

test('it fetches dashboard data concurrently', function () {
    Http::fake([
        'jsonplaceholder.typicode.com/posts*' => Http::response([['id' => 1, 'title' => 'Post']], 200),
        'jsonplaceholder.typicode.com/comments*' => Http::response([['id' => 1, 'body' => 'Comment']], 200),
        'jsonplaceholder.typicode.com/todos*' => Http::response([['id' => 1, 'completed' => false]], 200),
        'jsonplaceholder.typicode.com/users*' => Http::response([['id' => 1, 'name' => 'User']], 200),
    ]);

    $response = $this->getJson('/api/placeholder/dashboard');

    $response->assertOk()
        ->assertJsonStructure(['posts', 'comments', 'todos', 'users']);
});

test('it fetches an album', function () {
    Http::fake([
        'jsonplaceholder.typicode.com/albums/1' => Http::response([
            'id' => 1,
            'userId' => 1,
            'title' => 'Fake album',
        ], 200),
    ]);

    $response = $this->getJson('/api/placeholder/albums/1');

    $response->assertOk()
        ->assertJsonPath('id', 1)
        ->assertJsonPath('title', 'Fake album');
});

test('it runs a sync process', function () {
    Process::fake([
        'echo "Laravel Processes work!"' => Process::result('Laravel Processes work!', '', 0),
    ]);

    $response = $this->getJson('/api/placeholder/process/sync');

    $response->assertOk()
        ->assertJsonPath('successful', true)
        ->assertJsonPath('output', 'Laravel Processes work!');
});

test('it runs async process while fetching api', function () {
    Process::fake([
        'sleep *' => Process::result(output: 'Async done', exitCode: 0),
    ]);

    Http::fake([
        'jsonplaceholder.typicode.com/todos/1' => Http::response([
            'id' => 1,
            'completed' => false,
        ], 200),
    ]);

    $response = $this->getJson('/api/placeholder/process/async');

    $response->assertOk()
        ->assertJsonPath('process_output', 'Async done')
        ->assertJsonPath('process_ok', true)
        ->assertJsonPath('api_data.id', 1);
});

test('it runs concurrent processes', function () {
    Process::fake([
        'curl -s https://jsonplaceholder.typicode.com/posts' => Process::result('[{"id":1,"title":"Post"}]', '', 0),
        'curl -s https://jsonplaceholder.typicode.com/users' => Process::result('[{"id":1,"name":"User"}]', '', 0),
        'curl -s https://jsonplaceholder.typicode.com/todos' => Process::result('[{"id":1,"completed":false}]', '', 0),
    ]);

    $response = $this->getJson('/api/placeholder/process/concurrent');

    $response->assertOk()
        ->assertJsonStructure(['posts', 'users', 'todos'])
        ->assertJsonPath('posts.0.id', 1)
        ->assertJsonPath('users.0.id', 1)
        ->assertJsonPath('todos.0.id', 1);
});

test('it fetches posts and counts bytes with shell', function () {
    $fakeJson = json_encode([
        ['id' => 1, 'userId' => 1, 'title' => 'Post 1'],
        ['id' => 2, 'userId' => 1, 'title' => 'Post 2'],
    ]);

    Http::fake([
        'jsonplaceholder.typicode.com/posts*' => Http::response(
            json_decode($fakeJson, true), 200
        ),
    ]);

    Process::fake([
        'wc -c' => Process::result(output: (string) strlen($fakeJson), exitCode: 0),
    ]);

    $response = $this->getJson('/api/placeholder/users/1/fetch-and-process');

    $response->assertOk()
        ->assertJsonPath('userId', 1)
        ->assertJsonPath('postCount', 2)
        ->assertJsonPath('processOk', true);
});

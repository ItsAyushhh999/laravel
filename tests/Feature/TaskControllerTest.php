<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {

    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();

});

test('tasks can be created', function () {

    Storage::fake('public');

    $file = UploadedFile::fake()->create('attachment.pdf', 100);

    $response = $this->actingAs($this->user, 'sanctum')->post('/api/tasks', [
        'project_id' => $this->project->id,
        'title' => 'Test Task',
        'description' => 'This is a test task.',
        'priority' => 'high',
        'assignee_id' => $this->user->id,
        'reviewer_id' => $this->user->id,
        'attachments' => [$file],
    ]);

    $response->assertStatus(201);

    expect($response->json('task.title'))->toBe('Test Task');
    expect($response->json('task.description'))->toBe('This is a test task.');
});

test('tasks can be retrieved', function () {

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $task = Task::factory()->create();

    $response = $this->get("/api/tasks/{$task->id}");

    $response->assertStatus(200);
    expect($response->json('task.id'))->toBe($task->id);
    expect($response->json('task.title'))->toBe($task->title);
    expect($response->json('task.description'))->toBe($task->description);
});

test('tasks can be updated', function () {
    // Authenticate a user
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Create a task
    $task = Task::factory()->create();

    // Prepare updated data
    $updatedData = [
        'title' => 'Updated Task Title',
        'description' => 'This description has been updated.',
        'priority' => 'high',
        'assignee_id' => $task->assignee_id,
        'reviewer_id' => $task->reviewer_id,
    ];

    // Send PUT request to update the task
    $response = $this->put("/api/tasks/{$task->id}", $updatedData);

    // Assert the response is OK
    $response->assertStatus(200);

    // Assert that the response contains updated data
    expect($response->json('task.id'))->toBe($task->id);
    expect($response->json('task.title'))->toBe($updatedData['title']);
    expect($response->json('task.description'))->toBe($updatedData['description']);
    expect($response->json('task.priority'))->toBe($updatedData['priority']);
});

test('tasks can be deleted', function () {

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $task = Task::factory()->create();

    $response = $this->delete("/api/tasks/{$task->id}");

    $response->assertStatus(200);

    // Assert task is deleted from DB (soft deletes)
    expect(Task::find($task->id))->toBeNull();
});

test('tasks can be listed', function () {

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Task::factory()->count(3)->create();

    $response = $this->get('/api/tasks');

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(3);
});

test('tasks are paginated correctly', function () {

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // create 15 tasks, but per page is 10
    Task::factory()->count(15)->create();

    // page 1 should have 10 tasks
    $response = $this->get('/api/tasks?page=1');
    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(10);
    expect($response->json('current_page'))->toBe(1);
    expect($response->json('total'))->toBe(15);
    expect($response->json('last_page'))->toBe(2);

    // page 2 should have remaining 5 tasks
    $response = $this->get('/api/tasks?page=2');
    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(5);
    expect($response->json('current_page'))->toBe(2);
});

test('unauthenticated user cannot access tasks', function () {
    $response = $this->getJson('/api/tasks');
    $response->assertStatus(401);
});

test('task not found returns 404', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tasks/99999');
    $response->assertStatus(404);
});

test('task cannot be created with missing fields', function () {
    $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/tasks', [
        'title' => 'Missing fields task',
        // missing project_id, description, priority etc.
    ]);

    $response->assertStatus(422); // validation error
});

test('task cannot be created with invalid priority', function () {
    $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/tasks', [
        'project_id' => $this->project->id,
        'title' => 'Test Task',
        'description' => 'Test',
        'priority' => 'invalid_priority', // ❌ not in normal,high,urgent
        'assignee_id' => $this->user->id,
        'reviewer_id' => $this->user->id,
    ]);

    $response->assertStatus(422);
});

test('tasks can be filtered by priority', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Task::factory()->count(3)->create(['priority' => 'high']);
    Task::factory()->count(2)->create(['priority' => 'normal']);

    $response = $this->getJson('/api/tasks?priority=high');
    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(3);
});

test('deleted task cannot be retrieved', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $task = Task::factory()->create();
    $task->delete();

    $response = $this->getJson("/api/tasks/{$task->id}");
    $response->assertStatus(404);
});

test('task cannot be updated with missing fields', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $task = Task::factory()->create();

    $response = $this->putJson("/api/tasks/{$task->id}", [
        'title' => 'Only title', // missing other required fields
    ]);

    $response->assertStatus(422);
});

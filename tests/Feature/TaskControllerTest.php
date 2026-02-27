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

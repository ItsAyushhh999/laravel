<?php

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->task = Task::factory()->create();
});

test('comments can be created for a task', function () {
    $task = Task::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/tasks/{$this->task->id}/comments", [
        'body' => 'This is a comment.',
        'user_id' => $user->id,
    ]);

    $response->assertStatus(201);
    expect($response->json('body'))->toBe('This is a comment.');
});

test('replies can be created for a comment', function () {
    $comment = Comment::factory()->create(['task_id' => $this->task->id]);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->actingAs($user)->postJson("/api/comments/{$comment->id}/replies", [
        'body' => 'This is a reply.',
        'user_id' => $user->id,
    ]);

    $response->assertStatus(201);
    expect($response->json('body'))->toBe('This is a reply.');
});

test('comments for a task can be listed', function () {
    Sanctum::actingAs($this->user);

    Comment::factory()->count(3)->create([
        'task_id' => $this->task->id,
        'parent_id' => null,
    ]);

    $response = $this->getJson("/api/tasks/{$this->task->id}/comments");

    $response->assertStatus(200);
    expect(count($response->json()))->toBe(3);
});

test('replies for a comment can be listed', function () {
    Sanctum::actingAs($this->user);

    $comment = Comment::factory()->create(['task_id' => $this->task->id]);

    Comment::factory()->count(2)->create([
        'task_id' => $this->task->id,
        'parent_id' => $comment->id,
    ]);

    $response = $this->getJson("/api/comments/{$comment->id}/replies");

    $response->assertStatus(200);
    expect(count($response->json()))->toBe(2);
});

test('unauthenticated user cannot create comment', function () {
    $response = $this->postJson("/api/tasks/{$this->task->id}/comments", [
        'body' => 'This is a comment.',
    ]);
    $response->assertStatus(401);
});

test('comment cannot be created with empty body', function () {
    $response = $this->actingAs($this->user, 'sanctum')->postJson("/api/tasks/{$this->task->id}/comments", [
        'body' => '', // empty body
    ]);
    $response->assertStatus(422);
});

test('empty comments list returned for task with no comments', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson("/api/tasks/{$this->task->id}/comments");
    $response->assertStatus(200);
    expect($response->json())->toBe([]);
});

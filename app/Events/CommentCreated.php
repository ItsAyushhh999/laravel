<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Comment $comment) {}

    public function broadcastOn(): array
    {
        $task = $this->comment->task;

        $channels = [
            new PrivateChannel('users.'.$task->assignee_id),
            new PrivateChannel('users.'.$task->reviewer_id),
        ];

        // Notify creator too if different from assignee/reviewer
        if (
            $task->creator_id !== $task->assignee_id &&
            $task->creator_id !== $task->reviewer_id
        ) {
            $channels[] = new PrivateChannel('users.'.$task->creator_id);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->comment->id,
            'body' => $this->comment->body,
            'task_id' => $this->comment->task_id,
            'user_id' => $this->comment->user_id,
            'message' => 'A new comment was added to your task.',
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.created';
    }
}

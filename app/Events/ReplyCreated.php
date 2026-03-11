<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReplyCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Comment $reply) {}

    public function broadcastOn(): array
    {
        // Notify the original commenter
        $parentComment = $this->reply->parent;

        return [
            new PrivateChannel('users.'.$parentComment->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->reply->id,
            'body' => $this->reply->body,
            'task_id' => $this->reply->task_id,
            'user_id' => $this->reply->user_id,
            'parent_id' => $this->reply->parent_id,
            'message' => 'Someone replied to your comment.',
        ];
    }

    public function broadcastAs(): string
    {
        return 'reply.created';
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $task,
        public int $assignee_id,
        public int $reviewer_id,
        public string $taskTitle)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->assignee_id),
            new PrivateChannel('users.'.$this->reviewer_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->task,
            'title' => $this->taskTitle,
            'message' => 'A task has been deleted.',
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.deleted';
    }
}

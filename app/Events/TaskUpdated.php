<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Task $task, public array $previous)
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
        $channels = [
            new PrivateChannel('users.'.$this->task->assignee_id),
            new PrivateChannel('users.'.$this->task->reviewer_id),
        ];

        if ($this->task->creator_id !== $this->task->assignee_id && $this->task->creator_id !== $this->task->reviewer_id) {
            $channels[] = new PrivateChannel('users.'.$this->task->creator_id);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->task->id,
            'title' => $this->task->title,
            'priority' => $this->task->priority,
            'assignee_id' => $this->task->assignee_id,
            'reviewer_id' => $this->task->reviewer_id,
            'previous' => $this->previous,
            'message' => 'A task has been updated.',
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.updated';
    }
}

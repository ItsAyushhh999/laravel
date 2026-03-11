<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Task $task)
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
            new PrivateChannel('users.'.$this->task->assignee_id),
            new PrivateChannel('users.'.$this->task->reviewer_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->task->id,
            'project_id' => $this->task->project_id,
            'title' => $this->task->title,
            'description' => $this->task->description,
            'priority' => $this->task->priority,
            'assignee_id' => $this->task->assignee_id,
            'reviewer_id' => $this->task->reviewer_id,
            'message' => 'A new task has been created and assigned to you.',
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.created';
    }
}

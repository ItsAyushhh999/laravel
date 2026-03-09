<?php

namespace App\Observers;

use App\Jobs\SendTaskNotification;
use App\Jobs\SendTaskUpdatedNotification;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TaskObserver
{
    private static array $previousData = [];

    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        if ($task->priority === 'urgent') {
            SendTaskNotification::dispatch($task, $task->assignee)
                ->onQueue('high');
        } else {
            SendTaskNotification::dispatch($task, $task->assignee)
                ->onQueue('default');
        }

        $this->clearTaskListCache();
    }

    // Fires BEFORE update — capture original here
    public function updating(Task $task): void
    {
        self::$previousData[$task->id] = [
            'title' => $task->getOriginal('title'),
            'description' => $task->getOriginal('description'),
            'priority' => $task->getOriginal('priority'),
            'assignee_id' => $task->getOriginal('assignee_id'),
            'reviewer_id' => $task->getOriginal('reviewer_id'),
        ];
    }

    // Fires AFTER update — use captured original here
    public function updated(Task $task): void
    {
        if (! isset(self::$previousData[$task->id])) {
            return;
        }

        $previous = self::$previousData[$task->id];

        Log::info('Task updated', [
            'previous' => $previous,
            'current' => $task->only(['title', 'description', 'priority']),
        ]);

        SendTaskUpdatedNotification::dispatch($task, $task->assignee, $previous)
            ->onQueue('default');

        // Clean up static data
        unset(self::$previousData[$task->id]);

        Cache::forget("tasks:{$task->id}");
        $this->clearTaskListCache();
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        Cache::forget("tasks:{$task->id}");
        $this->clearTaskListCache();
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        $this->clearTaskListCache();
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }

    private function clearTaskListCache(): void
    {
        for ($page = 1; $page <= 50; $page++) {
            Cache::forget("tasks:all:{$page}");
        }
    }
}

<?php

namespace App\Observers;

use App\Jobs\SendTaskNotification;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        SendTaskNotification::dispatch($task, $task->assignee);

        $this->clearTaskListCache();
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
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

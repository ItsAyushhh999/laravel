<?php

use App\Jobs\SendTaskNotification;
use App\Models\Task;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    for ($page = 1; $page <= 50; $page++) {
        Cache::forget("tasks:all:{$page}");
    }
    Log::info('task cache cleared by scheduler');
})->hourly()->name('clear:task-cache');

Schedule::call(function () {
    $urgentTasks = \App\Models\Task::where('priority', 'urgent')->with('assignee')->get();

    foreach ($urgentTasks as $task) {
        SendTaskNotification::dispatch($task, $task->assignee)
            ->onQueue('default');
    }
    Log::info('Daily urgent tasks sent for '.$urgentTasks->count().' tasks');
})->dailyAt('08:00')->name('send:urgent-tasks');

Schedule::call(
    function () {
        $deleted = Task::onlyTrashed()
            ->where('deleted_at', '<=', now()
                ->subDays(30))->forceDelete();

        Log::info('Pruned old soft-deleted tasks.');
    }
)->weekly()->name('prune:old-tasks');

Schedule::command('queue:prune-failed --hours=24')->daily()->name('prune:failed-jobs');

/*Schedule::call(function () {
    Log::info('Scheduler is working!');
})->everyMinute()->name('test-scheduler');
*/

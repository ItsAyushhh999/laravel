<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTaskUpdatedNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 5;

    public function __construct(
        public Task $task,
        public User $user,
        public array $previous
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)
            ->send(new \App\Mail\TaskUpdated($this->task, $this->previous));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendTaskUpdatedNotification failed for task {$this->task->id}: {$exception->getMessage()}");
    }
}

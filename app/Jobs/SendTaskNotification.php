<?php

namespace App\Jobs;

use App\Mail\TaskAssigned;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTaskNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue ,Queueable, SerializesModels;

    public function __construct(public Task $task, public User $user) {}

    public function handle(): void
    {
        Mail::to($this->user->email)->send(new TaskAssigned($this->task));
    }
}

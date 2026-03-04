<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Task $task) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Task Assigned: '.$this->task->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <h1>New Task Assigned</h1>
                <p><strong>Title:</strong> {$this->task->title}</p>
                <p><strong>Description:</strong> {$this->task->description}</p>
                <p><strong>Priority:</strong> {$this->task->priority}</p>
            "
        );
    }
}

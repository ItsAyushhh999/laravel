<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Task $task, public array $previous) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Task Updated: '.$this->task->title,
        );
    }

    public function content(): Content
    {
        $rows = '';

        if ($this->previous['title'] !== $this->task->title) {
            $rows .= "
            <tr>
                <td>Title</td>
                <td style='color:red'>{$this->previous['title']}</td>
                <td style='color:green'>{$this->task->title}</td>
            </tr>";
        }

        if ($this->previous['description'] !== $this->task->description) {
            $rows .= "
            <tr>
                <td>Description</td>
                <td style='color:red'>{$this->previous['description']}</td>
                <td style='color:green'>{$this->task->description}</td>
            </tr>";
        }

        if ($this->previous['priority'] !== $this->task->priority) {
            $rows .= "
            <tr>
                <td>Priority</td>
                <td style='color:red'>{$this->previous['priority']}</td>
                <td style='color:green'>{$this->task->priority}</td>
            </tr>";
        }

        $html = "
            <h1>Task Has Been Updated</h1>
            <table border='1' cellpadding='8' style='border-collapse:collapse;'>
                <tr>
                    <th>Field</th>
                    <th>Previous</th>
                    <th>Updated</th>
                </tr>
                {$rows}
            </table>
            <p>Only changed fields are shown above.</p>
        ";

        return new Content(htmlString: $html);
    }
}

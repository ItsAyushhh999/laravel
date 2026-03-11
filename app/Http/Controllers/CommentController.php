<?php

namespace App\Http\Controllers;

use App\Events\CommentCreated;
use App\Events\ReplyCreated;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CommentCreatedNotification;
use App\Notifications\ReplyCreatedNotification;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        /** @var \App\Models\Comment $comment */
        $comment = $task->comments()->create([
            'user_id' => $request->input('user_id', $request->user()->id),
            'body' => $request->input('body'),
        ]);

        // Load task relationship for broadcastOn()
        $comment->load('task');

        broadcast(new CommentCreated($comment));

        $task = $comment->task;
        $users = User::whereIn('id', [$task->assignee_id, $task->reviewer_id, $task->creator_id])->where('id', '!=', auth()->id())->get();
        \Illuminate\Support\Facades\Notification::send($users, new CommentCreatedNotification($comment));

        return response()->json($comment, 201);
    }

    public function reply(Request $request, Comment $comment)
    {
        $request->validate([
            'body' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        /** @var \App\Models\Comment $reply */
        $reply = $comment->replies()->create([
            'task_id' => $comment->task_id,
            'user_id' => $request->input('user_id', $request->user()->id),
            'parent_id' => $comment->id,
            'body' => $request->input('body'),
        ]);

        // Load parent relationship for broadcastOn()
        $reply->load('parent');

        broadcast(new ReplyCreated($reply));

        $originalCommenter = User::find($reply->parent->user_id);
        if ($originalCommenter->id !== auth()->id()) {
            $originalCommenter->notify(new ReplyCreatedNotification($reply));
        }

        return response()->json($reply, 201);
    }

    public function index(Task $task)
    {
        $comments = Comment::with([
            'user:id,name,email',
            'replies.user:id,name,email',
            'task.assignee:id,email',
            'task.reviewer:id,email',
            'task.creator:id,email',
        ])
            ->where('task_id', $task->id)
            ->whereNull('parent_id')
            ->get();

        return response()->json($comments);
    }

    public function indexReplies(Comment $comment)
    {
        $replies = Comment::with('user:id,name,email')
            ->where('parent_id', $comment->id)
            ->get();

        return response()->json($replies);
    }
}

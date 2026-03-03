<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        $comment = $task->comments()->create([
            'user_id' => $request->input('user_id', $request->user()->id),
            'body' => $request->input('body'),
        ]);

        return response()->json($comment, 201);
    }

    public function reply(Request $request, Comment $comment)
    {
        $request->validate([
            'body' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $reply = $comment->replies()->create([
            'task_id' => $comment->task_id,
            'user_id' => $request->input('user_id', $request->user()->id),
            'parent_id' => $comment->id,
            'body' => $request->input('body'),
        ]);

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

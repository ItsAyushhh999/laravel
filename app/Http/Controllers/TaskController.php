<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return Task::with(['project', 'assignee', 'reviewer', 'attachments', 'creator'])
            ->select('id', 'project_id', 'title', 'priority', 'assignee_id', 'reviewer_id', 'creator_id')->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'project_id' => 'required|exists:projects,id',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'priority' => 'required|in:normal,high,urgent',
                'assignee_id' => 'required|exists:users,id',
                'reviewer_id' => 'required|exists:users,id',
                'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
            ]
        );

        $task = Task::create(array_merge($validated, [
            'creator_id' => auth()->id(),
        ]));

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time().'_'.$file->getClientOriginalName();
                $path = $file->storeAs('tasks', $filename, 'public');

                $task->attachments()->create([
                    'file_name' => $filename,
                    'file_path' => $path,
                ]);
            }
        }

        $task->load(['assignee', 'reviewer', 'attachments']);

        $task->attachments->transform(function ($attachment) {
            $attachment->url = asset('storage/'.$attachment->path);

            return $attachment;
        });

        return response()->json([
            'message' => 'Task created successfully',
            'task' => $task,
        ], 201);
    }

    public function show(Task $task)
    {
        $task->load(['project', 'assignee', 'reviewer', 'creator', 'comments.user', 'comments.replies.user', 'attachments']);

        /*$task->attachments->transform(function ($attachment) {
            $attachment->url = asset('storage/' . $attachment->path);
            return $attachment;
        });
        */

        return response()->json([
            'task' => $task,
        ]);
    }

    /*
     * Update the specified resource in storage.

    public function update(Request $request, Task $task)
    {
        $task->update($request->all());
        return response()->json($task);
    }
    */

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:normal,high,urgent',
            'assignee_id' => 'required|exists:users,id',
            'reviewer_id' => 'required|exists:users,id',
        ]);

        $task->update($validated);

        return response()->json([
            'task' => $task,
        ], 200);
    }

    public function destroy(Task $task)
    {
        $task = Task::find($task->id);

        if (! $task) {
            return response()->json([
                'message' => 'Task not found',
            ], 404);
        }
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }
}

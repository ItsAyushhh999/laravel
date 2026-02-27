<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Task::with(['project', 'assignee', 'reviewer', 'attachments'])
            ->select('id', 'project_id', 'title', 'priority', 'assignee_id', 'reviewer_id')->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     */
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

        $task = Task::create($validated);

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

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        $task->load(['project', 'assignee', 'reviewer', 'attachments']);

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
        // Try to find the task
        $task = Task::find($task->id);

        // If not found, return 404 JSON response
        if (! $task) {
            return response()->json([
                'message' => 'Task not found',
            ], 404);
        }

        // Delete the task
        $task->delete();

        // Return success message
        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }
}

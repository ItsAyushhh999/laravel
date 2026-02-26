<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        return Task::with(['project','assignee','reviewer','attachments'])->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'project_id' => 'required|exists:projects,id',
                'title'=> 'required|string|max:255',
                'description'=> 'required|string',
                'priority'=> 'required|in:normal,high,urgent',
                'assignee_id'=> 'required|exists:users,id',
                'reviewer_id'=> 'required|exists:users,id',
            ]
        );

        $task = Task::create($validated);
        return response()->json($task, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        return $task->load(['project','assignee','reviewer','attachments']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $task->update($request->all());
        return response()->json($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(['message'=>'Task deleted']);
    }

}

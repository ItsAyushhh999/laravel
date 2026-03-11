<?php

namespace App\Http\Controllers;

use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $priority = $request->get('priority');
        $assignee_id = $request->get('assignee');
        $project_id = $request->get('project');

        $cacheKey = "tasks:all:{$page}:priority:{$priority}:assignee:{$assignee_id}:project:{$project_id}";

        $tasks = Cache::remember($cacheKey, 60, function () use ($priority, $assignee_id, $project_id) {
            $query = Task::with(['project', 'assignee', 'reviewer', 'attachments', 'creator', 'comments.user', 'comments.replies.user'])
                ->select('id', 'project_id', 'title', 'description', 'priority', 'assignee_id', 'reviewer_id', 'creator_id');

            if ($priority === 'high') {
                $query->highPriority();
            }
            if ($priority === 'urgent') {
                $query->urgent();
            }
            if ($priority === 'normal') {
                $query->normal();
            }
            if ($assignee_id) {
                $query->assignedTo($assignee_id);
            }
            if ($project_id) {
                $query->forProject($project_id);
            }

            return $query->paginate(10);
        });

        return response()->json([
            'data' => TaskResource::collection($tasks->items()),
            'total' => $tasks->total(),
            'current_page' => $tasks->currentPage(),
            'last_page' => $tasks->lastPage(),
        ]);
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
        broadcast(new TaskCreated($task));

        $task->attachments->transform(function ($attachment) {
            $attachment->url = asset('storage/'.$attachment->path);

            return $attachment;
        });

        return response()->json([
            'message' => 'Task created successfully',
            'task' => new TaskResource($task),
        ], 201);
    }

    public function show(Task $task)
    {
        $taskData = Cache::remember("tasks:{$task->id}", 60, function () use ($task) {
            return Task::with('assignee', 'reviewer', 'creator', 'comments.user', 'comments.replies.user', 'attachments')
                ->find($task->id);
        });

        return response()->json([
            'task' => new TaskResource($taskData),
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:normal,high,urgent',
            'assignee_id' => 'required|exists:users,id',
            'reviewer_id' => 'required|exists:users,id',
        ]);

        $previous = [
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority,
            'assignee_id' => $task->assignee_id,
            'reviewer_id' => $task->reviewer_id,
        ];

        $task->update($validated);
        $task->load(['assignee', 'reviewer', 'project', 'creator', 'attachments']);
        broadcast(new TaskUpdated($task, $previous));

        return response()->json([
            'previous' => $previous,
            'updated' => new TaskResource($task),
        ], 200);
    }

    public function destroy(Task $task)
    {
        Task::findOrFail($task->id); // Ensure task exists before deletion
        $assignee_id = (int) $task->assignee_id;
        $reviewer_id = (int) $task->reviewer_id;
        $taskTitle = $task->title;
        $TaskId = (int) $task->id;
        $task->delete();
        broadcast(new \App\Events\TaskDeleted($TaskId, $assignee_id, $reviewer_id, $taskTitle));

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Jobs\SendTaskNotification;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->get('page', 1);

        $tasks = Cache::remember("tasks:all:{$page}", 60, function () {
            return Task::with(['project', 'assignee', 'reviewer', 'attachments', 'creator', 'comments.user', 'comments.replies.user'])
                ->select('id', 'project_id', 'title', 'priority', 'assignee_id', 'reviewer_id', 'creator_id')
                ->paginate(10);
        });

        return response()->json([
            'data' => $tasks->items(),
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

        SendTaskNotification::dispatch($task, $task->assignee);

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

        $this->clearTaskListCache();

        return response()->json([
            'message' => 'Task created successfully',
            'task' => $task,
        ], 201);
    }

    public function show(Task $task)
    {
        $taskData = Cache::remember("tasks:{$task->id}", 60, function () use ($task) {
            return Task::with('assignee', 'reviewer', 'creator', 'comments.user', 'comments.replies.user', 'attachments')
                ->find($task->id);
        });

        return response()->json([
            'task' => $taskData,
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

        $task->update($validated);

        $this->clearTaskCache($task->id);

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
        $this->clearTaskCache($task->id);

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }

    // Clear all cache keys for a specific task
    private function clearTaskCache(int $taskId)
    {
        Cache::forget("tasks:{$taskId}");
        $this->clearTaskListCache();
    }

    // Cler paginated task list cache(clears first 50 pages)
    private function clearTaskListCache()
    {
        for ($i = 1; $i <= 50; $i++) {
            Cache::forget("tasks:all:page:{$i}");
        }
    }
}

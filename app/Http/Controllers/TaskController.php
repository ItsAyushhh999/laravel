<?php

namespace App\Http\Controllers;

use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskDeletedNotification;
use App\Notifications\TaskUpdatedNotification;
use App\Services\JsonPlaceholderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{
    public function __construct(private JsonPlaceholderService $placeholder)
    {
        //
    }

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

        $externalPosts = $this->placeholder->getPosts();

        return response()->json([
            'data' => TaskResource::collection($tasks->items()),
            'total' => $tasks->total(),
            'current_page' => $tasks->currentPage(),
            'last_page' => $tasks->lastPage(),
            'external_posts' => $externalPosts,
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

        $assignee = User::find($task->assignee_id);
        $reviewer = User::find($task->reviewer_id);
        $assignee->notify(new TaskAssignedNotification($task));
        $reviewer->notify(new TaskAssignedNotification($task));

        $task->attachments->transform(function ($attachment) {
            $attachment->url = asset('storage/'.$attachment->path);

            return $attachment;
        });

        $externalPosts = $this->placeholder->createPost([
            'title' => $task->title,
            'body' => $task->description,
            'userId' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Task created successfully',
            'task' => new TaskResource($task),
            'external_post' => $externalPosts,
        ], 201);
    }

    public function show(Task $task)
    {
        $taskData = Cache::remember("tasks:{$task->id}", 60, function () use ($task) {
            return Task::with('assignee', 'reviewer', 'creator', 'comments.user', 'comments.replies.user', 'attachments')
                ->find($task->id);
        });

        $externalPosts = $this->placeholder->getPosts();

        return response()->json([
            'task' => new TaskResource($taskData),
            'external_posts' => $externalPosts,
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

        $users = User::whereIn('id', [$task->assignee_id, $task->reviewer_id, $task->creator_id])->get();
        \Illuminate\Support\Facades\Notification::send($users, new TaskUpdatedNotification($task));

        return response()->json([
            'previous' => $previous,
            'updated' => new TaskResource($task),
        ], 200);
    }

    public function destroy(Task $task)
    {
        $assignee_id = (int) $task->assignee_id;
        $reviewer_id = (int) $task->reviewer_id;
        $taskTitle = $task->title;
        $TaskId = (int) $task->id;
        $task->delete();
        broadcast(new \App\Events\TaskDeleted($TaskId, $assignee_id, $reviewer_id, $taskTitle));

        $assignee = User::find($assignee_id);
        $reviewer = User::find($reviewer_id);
        $assignee->notify(new TaskDeletedNotification($TaskId, $taskTitle));
        $reviewer->notify(new TaskDeletedNotification($TaskId, $taskTitle));

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }

    public function getManyPosts(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        $posts = $this->placeholder->getManyPosts($request->ids);

        return response()->json([
            'count' => count($posts),
            'posts' => $posts,
        ]);
    }

    public function createManyPosts(Request $request)
    {
        $request->validate([
            'posts' => 'required|array',
            'posts.*.title' => 'required|string',
            'posts.*.body' => 'required|string',
        ]);

        $externalPosts = $this->placeholder->createManyPosts(
            collect($request->posts)->map(function ($post) {
                return [
                    'title' => $post['title'],
                    'body' => $post['body'],
                    'userId' => auth()->id(),
                ];
            })->toArray()
        );

        return response()->json([
            'message' => 'Posts created successfully',
            'external_posts' => $externalPosts,
        ], 201);
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Task */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User|null $creator */
        $creator = $this->creator instanceof \App\Models\User ? $this->creator : null;

        return [
            // task details
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'project' => [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ],
            // assigne details
            'assignee' => [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'email' => $this->assignee->email,
            ],
            // reviewer details
            'reviewer' => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
                'email' => $this->reviewer->email,
            ],
            // creator details
            'creator' => [
                /** @var \App\Models\User $creator */
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ],
            // attachment details
            'attachments' => $this->attachments->map(fn ($a) => [
                'id' => $a->id,
                'file_name' => $a->file_name,
                'url' => asset('storage/'.$a->file_path),
            ]),
            // comment details
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

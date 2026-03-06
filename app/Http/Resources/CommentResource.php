<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Comment */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User|null $user */
        $user = $this->user instanceof \App\Models\User ? $this->user : null;

        return [
            'id' => $this->id,
            'body' => $this->body,
            'created_at' => $this->created_at?->toDateTimeString(),
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ],
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
        ];
    }
}

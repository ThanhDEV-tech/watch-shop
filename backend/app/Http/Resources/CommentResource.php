<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $isDeleted = $this->trashed();

        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'parent_id' => $this->parent_id,
            'content' => $isDeleted ? 'Bình luận đã bị xóa' : $this->content,
            'is_deleted' => $isDeleted,
            'user' => $isDeleted ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->resolveAvatarUrl($request->getSchemeAndHttpHost()),
            ],
            'replies' => CommentResource::collection($this->whenLoaded('repliesRecursive')),
            'created_at' => $this->created_at,
        ];
    }
}

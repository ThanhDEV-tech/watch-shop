<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminCourseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'content' => $this->content,
            'thumbnail_url' => $this->thumbnail
                ? url(Storage::disk('public')->url($this->thumbnail))
                : null,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'final_price' => $this->final_price,
            'level' => $this->level,
            'status' => $this->status,
            'reject_reason' => $this->reject_reason,
            'total_students' => $this->total_students,
            'rating_avg' => $this->rating_avg,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'instructor' => new UserResource($this->whenLoaded('instructor')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),
        ];
    }
}

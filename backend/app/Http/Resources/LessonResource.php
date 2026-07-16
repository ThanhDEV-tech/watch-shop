<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chapter_id' => $this->chapter_id,
            'title' => $this->title,
            'content' => $this->content,
            'youtube_url' => $this->youtube_url,
            'duration_seconds' => $this->duration_seconds,
            'position' => $this->position,
            'is_free_preview' => $this->is_free_preview,
        ];
    }
}

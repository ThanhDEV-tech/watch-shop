<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'progress_percent' => $this->progress_percent,
            'enrolled_at' => $this->enrolled_at,
            'completed_at' => $this->completed_at,
            'course' => new CourseResource($this->whenLoaded('course')),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentStudentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'enrollment_id' => $this->id,
            'student' => new UserResource($this->whenLoaded('user')),
            'enrolled_at' => $this->enrolled_at,
            'progress_percent' => $this->progress_percent,
            'completed_at' => $this->completed_at,
            'completed_lessons' => (int) ($this->completed_lessons_count ?? 0),
        ];
    }
}

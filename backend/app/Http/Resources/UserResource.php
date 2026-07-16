<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatarUrl = $this->resolveAvatarUrl($request->getSchemeAndHttpHost());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $avatarUrl,
            'avatar_url' => $avatarUrl,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'is_active' => $this->is_active,
            'role' => [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'display_name' => $this->role->display_name,
            ],
            'instructor_stats' => $this->role->name === 'instructor'
                ? [
                    'total_courses' => $this->instructorTotalCourses(),
                    'total_students' => $this->instructorTotalStudents(),
                    'rating_avg' => $this->instructorRatingAvg(),
                ]
                : null,
        ];
    }
}

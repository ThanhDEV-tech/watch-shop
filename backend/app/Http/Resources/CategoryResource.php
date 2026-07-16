<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'accent_color' => $this->accent_color ?: '#FF6B4A',
            'is_active' => $this->is_active,
            'courses_count' => $this->whenCounted('courses'),
            'created_at' => $this->created_at,
        ];
    }
}

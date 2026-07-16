<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CertificationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider' => $this->provider,
            'description' => $this->description,
            'icon' => $this->icon,
            'accent_color' => $this->accent_color ?: '#FF6B4A',
            'badge_image_url' => $this->badge_image
                ? url(Storage::disk('public')->url($this->badge_image))
                : null,
            'exam_info' => $this->exam_info,
            'external_link' => $this->external_link,
            'courses_count' => (int) ($this->courses_count ?? 0),
        ];
    }
}

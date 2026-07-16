<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider',
        'description',
        'icon',
        'accent_color',
        'badge_image',
        'exam_info',
        'external_link',
    ];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class)->withTimestamps();
    }
}

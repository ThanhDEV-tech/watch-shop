<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = ['chapter_id', 'title', 'content', 'youtube_url', 'duration_seconds', 'position', 'is_free_preview'];

    protected function casts(): array
    {
        return ['duration_seconds' => 'integer', 'position' => 'integer', 'is_free_preview' => 'boolean'];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function aiChatSessions(): HasMany
    {
        return $this->hasMany(AiChatSession::class);
    }
}

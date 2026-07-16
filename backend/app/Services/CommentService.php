<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Lesson;
use App\Models\User;

class CommentService
{
    /** @param array{content: string, parent_id?: int|null} $data */
    public function create(User $user, Lesson $lesson, array $data): Comment
    {
        return $lesson->comments()->create([
            'user_id' => $user->id,
            ...$data,
        ])->load(['user', 'repliesRecursive']);
    }

    public function delete(Comment $comment): bool
    {
        $keptAsDeletedPlaceholder = $comment->replies()->exists();

        if ($keptAsDeletedPlaceholder) {
            $comment->delete();
        } else {
            $comment->forceDelete();
        }

        return $keptAsDeletedPlaceholder;
    }
}

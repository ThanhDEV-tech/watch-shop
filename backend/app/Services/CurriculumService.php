<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CurriculumService
{
    /** @param array<string, mixed> $data */
    public function createChapter(Course $course, array $data): Chapter
    {
        $data['position'] ??= ((int) $course->chapters()->max('position')) + 1;

        return $course->chapters()->create($data);
    }

    /**
     * @param  array<int, array{id: int, position: int}>  $items
     * @return Collection<int, Chapter>
     */
    public function reorderChapters(Course $course, array $items): Collection
    {
        DB::transaction(function () use ($course, $items): void {
            foreach ($items as $item) {
                $course->chapters()->whereKey($item['id'])->update(['position' => $item['position']]);
            }
        });

        return $course->chapters()
            ->with(['lessons' => fn ($query) => $query->orderBy('position')->orderBy('id')])
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /** @param array<string, mixed> $data */
    public function updateChapter(Chapter $chapter, array $data): Chapter
    {
        $chapter->update($data);

        return $chapter->refresh();
    }

    public function deleteChapter(Chapter $chapter): void
    {
        $chapter->delete();
    }

    /** @param array<string, mixed> $data */
    public function createLesson(Chapter $chapter, array $data): Lesson
    {
        $data['position'] ??= ((int) $chapter->lessons()->max('position')) + 1;

        return $chapter->lessons()->create($data);
    }

    /**
     * @param  array<int, array{id: int, position: int}>  $items
     * @return Collection<int, Lesson>
     */
    public function reorderLessons(Chapter $chapter, array $items): Collection
    {
        DB::transaction(function () use ($chapter, $items): void {
            foreach ($items as $item) {
                $chapter->lessons()->whereKey($item['id'])->update(['position' => $item['position']]);
            }
        });

        return $chapter->lessons()->orderBy('position')->orderBy('id')->get();
    }

    /** @param array<string, mixed> $data */
    public function updateLesson(Lesson $lesson, array $data): Lesson
    {
        $lesson->update($data);

        return $lesson->refresh();
    }

    public function deleteLesson(Lesson $lesson): void
    {
        $lesson->delete();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chapter\ReorderChaptersRequest;
use App\Http\Requests\Chapter\StoreChapterRequest;
use App\Http\Requests\Chapter\UpdateChapterRequest;
use App\Http\Requests\Lesson\ReorderLessonsRequest;
use App\Http\Requests\Lesson\StoreLessonRequest;
use App\Http\Requests\Lesson\UpdateLessonRequest;
use App\Http\Resources\ChapterResource;
use App\Http\Resources\LessonResource;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\CurriculumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurriculumController extends Controller
{
    public function __construct(private readonly CurriculumService $curriculumService) {}

    public function index(Request $request, Course $course): JsonResponse
    {
        $user = $request->user('sanctum');
        $canPreviewUnpublished = $user && (
            $course->instructor_id === $user->id
            || $user->role?->name === 'admin'
        );

        abort_unless($course->status === 'approved' || $canPreviewUnpublished, 404);

        $enrollment = $user?->enrollments()
            ->where('course_id', $course->id)
            ->first();

        $chapters = $course->chapters()
            ->with(['lessons' => function ($query) use ($enrollment): void {
                $query->orderBy('position')->orderBy('id');

                if ($enrollment) {
                    $query->with(['lessonProgress' => fn ($progressQuery) => $progressQuery
                        ->where('enrollment_id', $enrollment->id)]);
                }
            }])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $canAccessFullContent = $canPreviewUnpublished || $enrollment !== null;
        $curriculum = $chapters->map(fn (Chapter $chapter) => $this->buildChapterPayload($chapter, $canAccessFullContent))->values();

        return response()->json([
            'success' => true,
            'data' => $curriculum,
            'message' => 'Lấy nội dung khóa học thành công.',
        ]);
    }

    private function buildChapterPayload(Chapter $chapter, bool $canAccessFullContent): array
    {
        return [
            'id' => $chapter->id,
            'course_id' => $chapter->course_id,
            'title' => $chapter->title,
            'description' => $chapter->description,
            'position' => $chapter->position,
            'lessons' => $chapter->lessons->map(function (Lesson $lesson) use ($canAccessFullContent): array {
                $canAccessLessonContent = $canAccessFullContent || $lesson->is_free_preview;

                return [
                    'id' => $lesson->id,
                    'chapter_id' => $lesson->chapter_id,
                    'title' => $lesson->title,
                    'content' => $canAccessLessonContent ? $lesson->content : null,
                    'youtube_url' => $canAccessLessonContent ? $lesson->youtube_url : null,
                    'duration_seconds' => $lesson->duration_seconds,
                    'position' => $lesson->position,
                    'is_free_preview' => $lesson->is_free_preview,
                    'is_completed' => $lesson->relationLoaded('lessonProgress')
                        && $lesson->lessonProgress->contains('is_completed', true),
                ];
            })->values()->all(),
        ];
    }

    public function storeChapter(StoreChapterRequest $request, Course $course): JsonResponse
    {
        $chapter = $this->curriculumService->createChapter($course, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new ChapterResource($chapter->load('lessons')),
            'message' => 'Tạo chương thành công.',
        ], 201);
    }

    public function updateChapter(UpdateChapterRequest $request, Chapter $chapter): JsonResponse
    {
        $chapter = $this->curriculumService->updateChapter($chapter, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new ChapterResource($chapter->load('lessons')),
            'message' => 'Cập nhật chương thành công.',
        ]);
    }

    public function reorderChapters(ReorderChaptersRequest $request, Course $course): JsonResponse
    {
        $chapters = $this->curriculumService->reorderChapters($course, $request->validated('items'));

        return response()->json([
            'success' => true,
            'data' => ChapterResource::collection($chapters),
            'message' => 'Cập nhật thứ tự chương thành công.',
        ]);
    }

    public function destroyChapter(UpdateChapterRequest $request, Chapter $chapter): JsonResponse
    {
        $this->curriculumService->deleteChapter($chapter);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Xóa chương thành công.',
        ]);
    }

    public function storeLesson(StoreLessonRequest $request, Chapter $chapter): JsonResponse
    {
        $lesson = $this->curriculumService->createLesson($chapter, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new LessonResource($lesson),
            'message' => 'Tạo bài học thành công.',
        ], 201);
    }

    public function updateLesson(UpdateLessonRequest $request, Lesson $lesson): JsonResponse
    {
        $lesson = $this->curriculumService->updateLesson($lesson, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new LessonResource($lesson),
            'message' => 'Cập nhật bài học thành công.',
        ]);
    }

    public function reorderLessons(ReorderLessonsRequest $request, Chapter $chapter): JsonResponse
    {
        $lessons = $this->curriculumService->reorderLessons($chapter, $request->validated('items'));

        return response()->json([
            'success' => true,
            'data' => LessonResource::collection($lessons),
            'message' => 'Cập nhật thứ tự bài học thành công.',
        ]);
    }

    public function destroyLesson(UpdateLessonRequest $request, Lesson $lesson): JsonResponse
    {
        $this->curriculumService->deleteLesson($lesson);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Xóa bài học thành công.',
        ]);
    }
}

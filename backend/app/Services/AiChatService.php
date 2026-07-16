<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

class AiChatService
{
    private const SYSTEM_PROMPT = 'Bạn là trợ lý học tập của EduMarket, giúp học viên hiểu bài học. Trả lời ngắn gọn, súc tích, bằng tiếng Việt trừ khi được hỏi bằng ngôn ngữ khác.';

    public function __construct(private readonly OpenAiService $openAiService) {}

    /**
     * @return array{session: AiChatSession, message: string, course_suggestions: array<int, array{id: int, title: string, thumbnail: string|null, price: string, slug: string}>}
     *
     * @throws AuthorizationException
     */
    public function chat(User $user, string $message, ?int $lessonId, ?int $sessionId): array
    {
        if ($sessionId) {
            $session = $this->ownedSession($user, $sessionId);
            $lesson = $session->lesson;
        } else {
            $lesson = $lessonId
                ? Lesson::query()->with('chapter')->findOrFail($lessonId)
                : null;

            if ($lesson) {
                $this->authorizeLessonContext($user, $lesson);
            }

            $session = $this->createSession($user, $message, $lesson?->id);
        }

        if ($sessionId && $lesson) {
            $this->authorizeLessonContext($user, $lesson);
        }

        $session->messages()->create([
            'role' => 'user',
            'content' => $message,
        ]);

        $history = $session->messages()
            ->latest('id')
            ->limit(20)
            ->get(['role', 'content'])
            ->reverse()
            ->values()
            ->map(fn ($chatMessage): array => [
                'role' => $chatMessage->role,
                'content' => $chatMessage->content,
            ])
            ->all();
        $assistantMessage = $this->openAiService->chat(
            $this->systemPrompt($lesson),
            $history,
        );
        $parsedMessage = $this->parseCourseSuggestions($assistantMessage);

        $session->messages()->create([
            'role' => 'assistant',
            'content' => $parsedMessage['message'],
        ]);

        return [
            'session' => $session,
            'message' => $parsedMessage['message'],
            'course_suggestions' => $parsedMessage['course_suggestions'],
        ];
    }

    /** @throws AuthorizationException */
    public function ownedSession(User $user, int $sessionId): AiChatSession
    {
        $session = AiChatSession::query()->with('lesson.chapter')->findOrFail($sessionId);

        if ($session->user_id !== $user->id) {
            throw new AuthorizationException('Bạn không có quyền truy cập phiên chat này.');
        }

        return $session;
    }

    private function createSession(User $user, string $message, ?int $lessonId): AiChatSession
    {
        return $user->aiChatSessions()->create([
            'lesson_id' => $lessonId,
            'title' => Str::limit($message, 100),
        ])->load('lesson.chapter');
    }

    /** @throws AuthorizationException */
    private function authorizeLessonContext(User $user, Lesson $lesson): void
    {
        if ($lesson->is_free_preview) {
            return;
        }

        $courseId = $lesson->chapter->course_id;
        $isEnrolled = $user->enrollments()->where('course_id', $courseId)->exists();

        if (! $isEnrolled) {
            throw new AuthorizationException('Bạn chưa đăng ký khóa học này.');
        }
    }

    private function systemPrompt(?Lesson $lesson): string
    {
        $prompt = self::SYSTEM_PROMPT;

        $prompt .= "\n\nDanh sách khóa học đang approved (chỉ dùng làm context gợi ý, giới hạn 20 khóa học):\n";
        $prompt .= $this->approvedCourseContext();
        $prompt .= "\n\nHướng dẫn: Nếu người dùng hỏi về việc nên học gì, muốn tìm khóa học liên quan chủ đề nào, hãy gợi ý ĐÚNG tên khóa học có trong danh sách trên, format gợi ý dạng: [COURSE_SUGGESTION:course_id] Tên khóa học. Nếu không có khóa học phù hợp, đừng bịa tên khóa học.";

        if ($lesson && ! blank($lesson->content)) {
            $prompt .= "\n\nNgữ cảnh bài học:\n".Str::limit(strip_tags((string) $lesson->content), 2000, '');
        }

        return $prompt;
    }

    private function approvedCourseContext(): string
    {
        $courses = Course::query()
            ->where('status', 'approved')
            ->with('category')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'title', 'category_id']);

        if ($courses->isEmpty()) {
            return '- Không có khóa học approved nào.';
        }

        return $courses->map(fn (Course $course): string => sprintf(
            '- [%d] %s (%s)',
            $course->id,
            $course->title,
            $course->category?->name ?? 'Không rõ',
        ))->implode("\n");
    }

    /**
     * @return array{message: string, course_suggestions: array<int, array{id: int, title: string, thumbnail: string|null, price: string, slug: string}>}
     */
    private function parseCourseSuggestions(string $message): array
    {
        $courseSuggestions = [];
        $cleanedMessage = preg_replace_callback(
            '/\[COURSE_SUGGESTION:(\d+)\]/i',
            function (array $matches) use (&$courseSuggestions): string {
                $course = Course::query()
                    ->select(['id', 'title', 'thumbnail', 'price', 'slug'])
                    ->find((int) $matches[1]);

                if (! $course) {
                    return '';
                }

                $courseSuggestions[] = [
                    'id' => $course->id,
                    'title' => $course->title,
                    'thumbnail' => $course->thumbnail,
                    'price' => number_format((float) $course->price, 2, '.', ''),
                    'slug' => $course->slug,
                ];

                return $course->title;
            },
            $message,
        );

        $cleanedMessage = trim((string) $cleanedMessage);
        foreach ($courseSuggestions as $courseSuggestion) {
            $title = (string) ($courseSuggestion['title'] ?? '');
            if ($title !== '') {
                $escapedTitle = preg_quote($title, '/');
                $cleanedMessage = preg_replace('/'. $escapedTitle .'\s+'. $escapedTitle .'/u', $title, $cleanedMessage) ?? $cleanedMessage;
            }
        }
        $cleanedMessage = preg_replace('/\s{2,}/', ' ', $cleanedMessage) ?? $cleanedMessage;
        $cleanedMessage = preg_replace('/\n{2,}/', "\n", $cleanedMessage) ?? $cleanedMessage;
        $cleanedMessage = preg_replace('/\s+([,.!?;:])/u', '$1', $cleanedMessage) ?? $cleanedMessage;

        return [
            'message' => $cleanedMessage,
            'course_suggestions' => array_values($courseSuggestions),
        ];
    }
}

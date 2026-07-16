<?php

namespace Tests\Feature;

use App\Models\AiChatSession;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiChatTest extends TestCase
{
    use RefreshDatabase;

    private bool $useCustomAiResponse = false;

    private string $customAiResponse = 'Đây là câu trả lời AI đã được mock.';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.openai', [
            'api_key' => 'test-openai-key',
            'model' => 'gpt-4o-mini',
            'base_url' => 'https://api.openai.com/v1',
        ]);

        Http::fake(function (Request $request) {
            if (($request['messages'][1]['content'] ?? '') === 'TRIGGER_PROVIDER_FAILURE') {
                return Http::response([
                    'error' => ['message' => 'Sensitive provider error'],
                ], 429);
            }

            if ($this->useCustomAiResponse) {
                return Http::response([
                    'choices' => [
                        ['message' => ['content' => $this->customAiResponse]],
                    ],
                ]);
            }

            return Http::response([
                'choices' => [
                    ['message' => ['content' => 'Đây là câu trả lời AI đã được mock.']],
                ],
            ]);
        });
    }

    public function test_sending_message_creates_session_and_stores_user_and_assistant_messages(): void
    {
        $student = $this->createStudent();
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/ai/chat', [
            'message' => 'Giải thích dependency injection là gì?',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message', 'Đây là câu trả lời AI đã được mock.');

        $sessionId = $response->json('data.session_id');

        $this->assertDatabaseHas('ai_chat_sessions', [
            'id' => $sessionId,
            'user_id' => $student->id,
        ]);
        $this->assertDatabaseHas('ai_chat_messages', [
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => 'Giải thích dependency injection là gì?',
        ]);
        $this->assertDatabaseHas('ai_chat_messages', [
            'session_id' => $sessionId,
            'role' => 'assistant',
            'content' => 'Đây là câu trả lời AI đã được mock.',
        ]);
        $this->assertDatabaseCount('ai_chat_messages', 2);

        $this->getJson("/api/ai/sessions/{$sessionId}/messages")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.messages')
            ->assertJsonPath('data.messages.0.role', 'user')
            ->assertJsonPath('data.messages.1.role', 'assistant');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request['model'] === 'gpt-4o-mini'
                && $request['messages'][0]['role'] === 'system'
                && str_contains($request['messages'][0]['content'], 'trợ lý học tập của EduMarket');
        });
    }

    public function test_sending_next_message_reuses_existing_session(): void
    {
        $student = $this->createStudent();
        Sanctum::actingAs($student);

        $firstResponse = $this->postJson('/api/ai/chat', [
            'message' => 'Câu hỏi đầu tiên',
        ])->assertOk();
        $sessionId = $firstResponse->json('data.session_id');

        $this->postJson('/api/ai/chat', [
            'message' => 'Câu hỏi tiếp theo',
            'session_id' => $sessionId,
        ])
            ->assertOk()
            ->assertJsonPath('data.session_id', $sessionId);

        $this->assertDatabaseCount('ai_chat_sessions', 1);
        $this->assertDatabaseCount('ai_chat_messages', 4);
        $this->assertDatabaseHas('ai_chat_messages', [
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => 'Câu hỏi tiếp theo',
        ]);
    }

    public function test_user_cannot_view_another_users_chat_history(): void
    {
        $owner = $this->createStudent();
        $otherUser = User::factory()->create(['role_id' => $owner->role_id]);
        $session = AiChatSession::query()->create([
            'user_id' => $owner->id,
            'title' => 'Private session',
        ]);
        $session->messages()->create([
            'role' => 'user',
            'content' => 'Nội dung riêng tư',
        ]);
        Sanctum::actingAs($otherUser);

        $this->getJson("/api/ai/sessions/{$session->id}/messages")
            ->assertForbidden()
            ->assertExactJson([
                'success' => false,
                'data' => null,
                'message' => 'Bạn không có quyền truy cập phiên chat này.',
            ]);
    }

    public function test_lesson_context_is_included_and_truncated_before_sending_to_openai(): void
    {
        $student = $this->createStudent();
        $lesson = $this->createFreePreviewLesson(str_repeat('A', 2100).'SHOULD_NOT_BE_SENT');
        Sanctum::actingAs($student);

        $this->postJson('/api/ai/chat', [
            'message' => 'Tóm tắt bài học',
            'lesson_id' => $lesson->id,
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('ai_chat_sessions', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
        ]);
        Http::assertSent(function (Request $request): bool {
            $systemPrompt = $request['messages'][0]['content'];

            return str_contains($systemPrompt, 'Ngữ cảnh bài học:')
                && str_contains($systemPrompt, str_repeat('A', 2000))
                && ! str_contains($systemPrompt, 'SHOULD_NOT_BE_SENT');
        });
    }

    public function test_openai_course_suggestions_are_parsed_into_course_cards(): void
    {
        $student = $this->createStudent();
        $category = Category::query()->create(['name' => 'DevOps', 'slug' => 'devops', 'is_active' => true]);
        $course = Course::query()->create([
            'instructor_id' => User::factory()->create(['role_id' => Role::query()->firstOrCreate(['name' => 'instructor'], ['display_name' => 'Instructor'])->id])->id,
            'category_id' => $category->id,
            'title' => 'Docker cho người mới bắt đầu',
            'slug' => 'docker-cho-nguoi-moi-bat-dau',
            'thumbnail' => 'https://example.com/docker.jpg',
            'price' => 299000,
            'status' => 'approved',
        ]);
        Sanctum::actingAs($student);

        $this->useCustomAiResponse = true;
        $this->customAiResponse = "Bạn nên xem [COURSE_SUGGESTION:{$course->id}] Docker cho người mới bắt đầu";

        $this->postJson('/api/ai/chat', ['message' => 'Tôi muốn học về Docker'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message', 'Bạn nên xem Docker cho người mới bắt đầu')
            ->assertJsonPath('data.course_suggestions.0.id', $course->id)
            ->assertJsonPath('data.course_suggestions.0.title', 'Docker cho người mới bắt đầu')
            ->assertJsonPath('data.course_suggestions.0.slug', 'docker-cho-nguoi-moi-bat-dau')
            ->assertJsonPath('data.course_suggestions.0.price', '299000.00');
    }

    public function test_openai_failure_returns_friendly_service_unavailable_response(): void
    {
        Sanctum::actingAs($this->createStudent());

        $this->postJson('/api/ai/chat', ['message' => 'TRIGGER_PROVIDER_FAILURE'])
            ->assertStatus(503)
            ->assertExactJson([
                'success' => false,
                'data' => null,
                'message' => 'Trợ lý AI đang bận hoặc tạm thời không thể kết nối. Vui lòng thử lại sau.',
            ])
            ->assertDontSee('Sensitive provider error')
            ->assertDontSee('test-openai-key');
    }

    private function createStudent(): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => 'student'],
            ['display_name' => 'Student'],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createFreePreviewLesson(string $content): Lesson
    {
        $instructorRole = Role::query()->firstOrCreate(
            ['name' => 'instructor'],
            ['display_name' => 'Instructor'],
        );
        $instructor = User::factory()->create(['role_id' => $instructorRole->id]);
        $category = Category::query()->create([
            'name' => 'AI Context',
            'slug' => 'ai-context',
        ]);
        $course = Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => 'AI Context Course',
            'slug' => 'ai-context-course',
            'price' => 299000,
            'status' => 'approved',
        ]);
        $chapter = Chapter::query()->create([
            'course_id' => $course->id,
            'title' => 'Context Chapter',
            'position' => 1,
        ]);

        return Lesson::query()->create([
            'chapter_id' => $chapter->id,
            'title' => 'Context Lesson',
            'content' => $content,
            'position' => 1,
            'is_free_preview' => true,
        ]);
    }
}

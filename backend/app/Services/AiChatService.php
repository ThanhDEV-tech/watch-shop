<?php

namespace App\Services;

use App\Models\AiChatSession;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class AiChatService
{
    public function __construct(private readonly OpenAiService $openAiService) {}

    /**
     * @return array{session: AiChatSession, message: string, course_suggestions: array<int, array<string, mixed>>}
     *
     * @throws AuthorizationException
     */
    public function chat(User $user, string $message, ?int $lessonId, ?int $sessionId): array
    {
        // TODO: Redesign this service for the Watchora AI Shopping Assistant.
        throw new AuthorizationException('Tính năng AI Chat đang được nâng cấp. Vui lòng thử lại sau.');
    }

    /** @throws AuthorizationException */
    public function ownedSession(User $user, int $sessionId): AiChatSession
    {
        $session = AiChatSession::query()->findOrFail($sessionId);

        if ($session->user_id !== $user->id) {
            throw new AuthorizationException('Bạn không có quyền truy cập phiên chat này.');
        }

        return $session;
    }
}

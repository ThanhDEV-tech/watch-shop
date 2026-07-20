<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OpenAiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\ChatRequest;
use App\Http\Resources\AiChatMessageResource;
use App\Models\AiChatSession;
use App\Models\User;
use App\Services\AiChatService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function __construct(private readonly AiChatService $aiChatService) {}

    public function chat(ChatRequest $request): JsonResponse
    {
        try {
            $result = $this->aiChatService->chat(
                $this->optionalUser(),
                $request->string('message')->toString(),
                $request->integer('session_id') ?: null,
                $request->string('session_token')->trim()->toString() ?: null,
            );
        } catch (AuthorizationException $exception) {
            return $this->forbiddenResponse($exception->getMessage());
        } catch (OpenAiException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Hệ thống đang bận, vui lòng thử lại sau.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $result['session']->id,
                'session_token' => $result['session']->guest_token,
                'message' => $result['message'],
                'product_suggestions' => $result['product_suggestions'],
            ],
            'message' => 'Nhận phản hồi từ trợ lý AI thành công.',
        ]);
    }

    public function messages(Request $request, AiChatSession $session): JsonResponse
    {
        $user = $this->optionalUser();
        $sessionToken = $request->string('session_token')->trim()->toString();

        if (
            ($session->user_id && $session->user_id !== $user?->id)
            || (! $session->user_id && (! $session->guest_token || $session->guest_token !== $sessionToken))
        ) {
            return $this->forbiddenResponse('Bạn không có quyền truy cập phiên chat này.');
        }

        $messages = $session->messages()->oldest('id')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'session_token' => $session->guest_token,
                'messages' => AiChatMessageResource::collection($messages),
            ],
            'message' => 'Lấy lịch sử chat thành công.',
        ]);
    }

    private function optionalUser(): ?User
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();

        return $user;
    }

    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
        ], 403);
    }
}

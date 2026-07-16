<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OpenAiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\ChatRequest;
use App\Http\Resources\AiChatMessageResource;
use App\Models\AiChatSession;
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
                $request->user(),
                $request->string('message')->toString(),
                $request->integer('lesson_id') ?: null,
                $request->integer('session_id') ?: null,
            );
        } catch (AuthorizationException $exception) {
            return $this->forbiddenResponse($exception->getMessage());
        } catch (OpenAiException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Trợ lý AI đang bận hoặc tạm thời không thể kết nối. Vui lòng thử lại sau.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $result['session']->id,
                'message' => $result['message'],
                'course_suggestions' => $result['course_suggestions'],
            ],
            'message' => 'Nhận phản hồi từ trợ lý AI thành công.',
        ]);
    }

    public function messages(Request $request, AiChatSession $session): JsonResponse
    {
        if ($session->user_id !== $request->user()->id) {
            return $this->forbiddenResponse('Bạn không có quyền truy cập phiên chat này.');
        }

        $messages = $session->messages()->oldest('id')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'messages' => AiChatMessageResource::collection($messages),
            ],
            'message' => 'Lấy lịch sử chat thành công.',
        ]);
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

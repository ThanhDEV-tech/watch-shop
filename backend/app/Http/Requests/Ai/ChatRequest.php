<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'session_id' => ['nullable', 'integer', 'exists:ai_chat_sessions,id'],
        ];
    }
}

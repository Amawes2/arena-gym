<?php

namespace App\Http\Requests;

use App\Services\TurnstileService;
use Illuminate\Foundation\Http\FormRequest;

class MagicLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(TurnstileService $turnstile): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:254'],
            'website' => ['prohibited'],
            'turnstile_token' => $turnstile->validationRules('login'),
        ];
    }
}

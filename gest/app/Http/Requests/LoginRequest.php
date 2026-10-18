<?php

namespace App\Http\Requests;

use App\Services\TurnstileService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
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
            'password' => ['required', 'string'],
            'two_factor_code' => ['nullable', 'string', 'max:64'],
            'website' => ['prohibited'],
            'turnstile_token' => $turnstile->validationRules('login'),
        ];
    }
}

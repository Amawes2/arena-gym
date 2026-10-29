<?php

namespace App\Http\Requests;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Services\TurnstileService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class MemberRegistrationRequest extends FormRequest
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
            'prenom' => ['required', 'string', 'max:50'],
            'nom' => ['required', 'string', 'max:50'],
            'telephone' => ['required', 'string', 'regex:/^[0-9+\s]{8,15}$/'],
            'email' => [
                'required',
                'email',
                'max:254',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $emailExists = User::query()
                        ->whereRaw('LOWER(email) = ?', [Str::lower(trim((string) $value))])
                        ->exists();

                    if ($emailExists) {
                        $fail('Cette adresse e-mail est déjà utilisée.');

                        return;
                    }

                    $registrationIsPending = PendingRegistration::query()
                        ->where('email', Str::lower(trim((string) $value)))
                        ->where('registration_expires_at', '>', now())
                        ->exists();

                    if ($registrationIsPending) {
                        $fail('Une vérification est déjà en cours pour cette adresse. Demandez un nouvel OTP.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'website' => ['prohibited'],
            'turnstile_token' => $turnstile->validationRules('member_registration'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
        ];
    }
}

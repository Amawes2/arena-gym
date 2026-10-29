<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Services\CountryDetectionService;
use App\Services\TurnstileService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class GymRegistrationRequest extends FormRequest
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
        $countryCode = strtoupper(trim((string) (
            $this->input('country_code')
            ?: app(CountryDetectionService::class)->detect($this)
            ?: 'CI'
        )));

        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'country_code' => $countryCode,
            'currency_code' => strtoupper(trim((string) (
                $this->input('currency_code')
                ?: config('billing.countries.'.$countryCode.'.currency', 'XOF')
            ))),
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
            'gym_name' => ['required', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2', Rule::in(array_keys(config('billing.countries', [])))],
            'currency_code' => [
                'required',
                'string',
                'size:3',
                Rule::in([config('billing.countries.'.$this->input('country_code').'.currency')]),
            ],
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
                        ->where('role', '!=', UserRole::Admin->value)
                        ->where('registration_expires_at', '>', now())
                        ->exists();

                    if ($registrationIsPending) {
                        $fail('Une vérification est déjà en cours pour cette adresse. Demandez un nouvel OTP.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'website' => ['prohibited'],
            'turnstile_token' => $turnstile->validationRules('gym_registration'),
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

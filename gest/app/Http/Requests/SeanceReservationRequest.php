<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Services\TurnstileService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeanceReservationRequest extends FormRequest
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
            'prenom' => ['required', 'string', 'max:50'],
            'nom' => ['required', 'string', 'max:50'],
            'telephone' => ['required', 'string', 'regex:/^[0-9+\s]{8,15}$/'],
            'mode_paiement' => ['nullable', Rule::enum(PaymentMethod::class)],
            'website' => ['prohibited'],
            'turnstile_token' => $turnstile->validationRules('quick_registration'),
        ];
    }
}

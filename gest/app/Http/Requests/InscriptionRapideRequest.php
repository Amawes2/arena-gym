<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Services\TurnstileService;
use App\Support\EliteFitGym;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InscriptionRapideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(TurnstileService $turnstile): array
    {
        return [
            'prenom' => ['required', 'string', 'max:50'],
            'nom' => ['required', 'string', 'max:50'],
            'telephone' => ['required', 'string', 'regex:/^[0-9+\s]{8,15}$/'],
            'type' => ['required', Rule::in(['seance_unique', 'abonnement'])],
            'forfait_id' => ['required_if:type,abonnement', 'integer', Rule::exists('plans', 'id')->where('gym_id', EliteFitGym::id())],
            'mode_paiement' => ['nullable', Rule::enum(PaymentMethod::class)],
            'website' => ['prohibited'],
            'turnstile_token' => $turnstile->validationRules('quick_registration'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prenom.required' => 'Le prénom est obligatoire.',
            'nom.required' => 'Le nom est obligatoire.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.regex' => 'Le numéro de téléphone n\'est pas valide.',
            'forfait_id.required_if' => 'Veuillez sélectionner un forfait.',
        ];
    }
}

<?php

namespace App\Rules;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidTurnstileToken implements ValidationRule
{
    public function __construct(public string $action) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! app(TurnstileService::class)->verify($value, request()->ip(), $this->action)) {
            $fail('La vérification anti-robot a échoué. Réessayez.');
        }
    }
}

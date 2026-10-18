<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

final class NotificationService
{
    public function confirmationSeance(Member $member, float $montant): void
    {
        $message = sprintf(
            'EliteFit Abidjan : Bonjour %s, votre séance du %s est confirmée. Montant : %s FCFA. À bientôt !',
            $member->first_name,
            now()->format('d/m/Y'),
            number_format($montant, 0, ',', ' ')
        );

        $this->sendSms($member->phone, $message);
    }

    public function confirmationAbonnement(Member $member, Plan $plan, Subscription $subscription): void
    {
        $message = sprintf(
            'EliteFit : %s, votre abonnement "%s" est actif du %s au %s. Montant : %s FCFA.',
            $member->first_name,
            $plan->name,
            $subscription->start_date->format('d/m/Y'),
            $subscription->end_date->format('d/m/Y'),
            number_format((float) $plan->price, 0, ',', ' ')
        );

        $this->sendSms($member->phone, $message);
    }

    private function sendSms(string $telephone, string $message): void
    {
        Log::channel('single')->info('SMS EliteFit', [
            'to' => $telephone,
            'message' => $message,
        ]);
    }
}

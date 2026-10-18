<?php

namespace App\Notifications;

use App\Models\Gym;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SaasRenewalReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Gym $gym,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->gym->getSaasStatus();
        $subject = match ($status) {
            'expired' => 'Accès SaaS expiré - action requise',
            'expiring' => 'Votre abonnement SaaS arrive à échéance',
            default => 'Mise à jour de votre abonnement SaaS',
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line("La salle {$this->gym->name} est actuellement sur l’offre {$this->gym->plan_saas}.")
            ->line('Statut : '.$this->gym->getSaasStatusLabel())
            ->line('Échéance : '.$this->gym->getExpirySummary())
            ->line('Merci de régulariser l’abonnement pour garder un accès fluide à votre espace de gestion.')
            ->action('Ouvrir mon espace SaaS', url('/admin/tenant-billing'))
            ->line('Si vous avez déjà effectué le paiement, vous pouvez ignorer ce message.');
    }
}

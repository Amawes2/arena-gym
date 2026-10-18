<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Gym;
use App\Support\CurrentGymResolver;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class TenantBilling extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Système';

    protected static ?string $navigationLabel = 'Abonnement SaaS';

    protected static ?string $title = 'Mon Abonnement';

    protected string $view = 'filament.pages.tenant-billing';

    public ?Gym $gym = null;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function mount(): void
    {
        $this->gym = CurrentGymResolver::resolve();
    }

    public function getHeading(): string
    {
        return match ($this->getStatusTone()) {
            'danger' => 'Abonnement à renouveler',
            'warning' => 'Échéance proche',
            'success' => 'Abonnement actif',
            default => 'Suivi de votre abonnement SaaS',
        };
    }

    public function getSubheading(): ?string
    {
        if (! $this->gym) {
            return 'Aucune salle n’a pu être résolue pour ce compte. Les informations d’abonnement restent limitées.';
        }

        return "Vue d’ensemble de la facturation et de l’état d’accès pour {$this->gym->name}.";
    }

    public function hasGym(): bool
    {
        return $this->gym !== null;
    }

    public function isExpired(): bool
    {
        return $this->gym?->expires_at?->lt(today()) ?? false;
    }

    public function isExpiringSoon(): bool
    {
        if (! $this->gym?->expires_at || $this->isExpired()) {
            return false;
        }

        return now()->startOfDay()->diffInDays($this->gym->expires_at->copy()->startOfDay(), false) <= 7;
    }

    public function getStatusTone(): string
    {
        if (! $this->gym) {
            return 'gray';
        }

        if (! $this->gym->is_active) {
            return 'gray';
        }

        if ($this->isExpired()) {
            return 'danger';
        }

        if ($this->isExpiringSoon()) {
            return 'warning';
        }

        return 'success';
    }

    public function getStatusLabel(): string
    {
        return match ($this->getStatusTone()) {
            'danger' => 'Renouvellement requis',
            'warning' => 'Échéance proche',
            'success' => 'Actif et stable',
            default => 'Salle non résolue',
        };
    }

    public function getHeroTitle(): string
    {
        if (! $this->gym) {
            return 'Impossible de déterminer la salle concernée pour le moment.';
        }

        return match ($this->getStatusTone()) {
            'danger' => "L'accès de {$this->gym->name} est actuellement bloqué par l'échéance SaaS.",
            'warning' => "L'abonnement de {$this->gym->name} arrive bientôt à expiration.",
            'success' => "L'abonnement de {$this->gym->name} est en règle.",
            default => "Le statut d'abonnement de {$this->gym->name} nécessite une vérification.",
        };
    }

    public function getHeroDescription(): string
    {
        if (! $this->gym) {
            return 'Rattache une salle à ce compte pour afficher la formule active, la date d’échéance et les prochaines actions.';
        }

        return match ($this->getStatusTone()) {
            'danger' => 'Les données de la salle restent conservées, mais l’accès aux écrans opérationnels peut être limité tant que le renouvellement n’est pas confirmé.',
            'warning' => 'Tout fonctionne encore, mais il est temps d’anticiper le renouvellement pour éviter une interruption de service côté gérant.',
            'success' => 'La salle dispose encore d’un accès complet. Cette page sert de point de contrôle rapide pour la formule, la date limite et les prochaines étapes.',
            default => 'Le compte a besoin d’une vérification manuelle pour confirmer l’état exact de la facturation.',
        };
    }

    public function getPlanLabel(): string
    {
        $plan = Str::lower(trim((string) $this->gym?->plan_saas));

        return match ($plan) {
            'trial' => 'Essai',
            'basic' => 'Basic',
            'pro' => 'Pro',
            'premium' => 'Premium',
            '' => 'Non défini',
            default => Str::headline($plan),
        };
    }

    public function getExpiryDisplay(): string
    {
        if (! $this->gym?->expires_at) {
            return 'Non définie';
        }

        return $this->gym->expires_at->format('d/m/Y');
    }

    public function getRelativeExpiryLabel(): string
    {
        if (! $this->gym?->expires_at) {
            return 'Aucune date d’échéance enregistrée.';
        }

        $days = now()->startOfDay()->diffInDays($this->gym->expires_at->copy()->startOfDay(), false);

        if ($days < 0) {
            $delay = abs($days);

            return 'Expiré il y a '.$delay.' jour'.($delay > 1 ? 's' : '');
        }

        if ($days === 0) {
            return 'Expire aujourd’hui';
        }

        return 'Expire dans '.$days.' jour'.($days > 1 ? 's' : '');
    }

    public function getAccessLabel(): string
    {
        return match ($this->getStatusTone()) {
            'danger' => 'Accès suspendu',
            'warning' => 'Accès complet, mais à surveiller',
            'success' => 'Accès complet',
            default => 'Accès non déterminé',
        };
    }

    public function getPriorityLabel(): string
    {
        return match ($this->getStatusTone()) {
            'danger' => 'Priorité immédiate',
            'warning' => 'À traiter cette semaine',
            'success' => 'RAS',
            default => 'Vérification requise',
        };
    }

    public function getPriorityDescription(): string
    {
        return match ($this->getStatusTone()) {
            'danger' => 'Le renouvellement doit être validé pour rétablir un fonctionnement normal.',
            'warning' => 'Le service reste disponible, mais la fenêtre de renouvellement est déjà ouverte.',
            'success' => 'La salle est couverte. Tu peux simplement surveiller la prochaine échéance.',
            default => 'Le compte courant n’est pas encore suffisamment rattaché pour confirmer le statut.',
        };
    }

    public function getHealthPercent(): int
    {
        return match ($this->getStatusTone()) {
            'danger' => 18,
            'warning' => 52,
            'success' => 91,
            default => 24,
        };
    }

    public function getSupportHint(): string
    {
        if (! $this->gym) {
            return 'Commence par rattacher la salle avant d’activer un vrai flow de paiement.';
        }

        return match ($this->getStatusTone()) {
            'danger' => 'Prépare ici un bouton de règlement ou un lien WhatsApp / support avant la mise en prod.',
            'warning' => 'C’est le bon endroit pour brancher une relance douce avant coupure.',
            'success' => 'Cette zone peut devenir un centre de contrôle pour le prochain cycle de facturation.',
            default => 'Une fois la salle retrouvée, cette zone pourra piloter le renouvellement.',
        };
    }

    public function getFormattedWalkInPrice(): string
    {
        if (! $this->gym) {
            return '—';
        }

        return number_format((float) ($this->gym->walk_in_price ?? 0), 0, ',', ' ').' FCFA';
    }

    /**
     * @return array<int, string>
     */
    public function getImpactItems(): array
    {
        if (! $this->gym) {
            return [
                'Associer une salle à ce compte pour afficher un état SaaS précis.',
                'Vérifier le rattachement owner ou manager avant d’ouvrir le renouvellement.',
                'Revenir ici ensuite pour confirmer la formule et la date d’échéance.',
            ];
        }

        return match ($this->getStatusTone()) {
            'danger' => [
                'Les données de la salle restent présentes, mais la navigation peut être redirigée vers cette page.',
                'Le renouvellement doit être confirmé avant de retrouver un usage normal du back-office.',
                'La formule active, la date limite et le tarif ponctuel restent visibles pour faciliter la reprise.',
            ],
            'warning' => [
                'Le compte reste accessible pour le moment, mais une échéance proche peut interrompre le service sans renouvellement.',
                'Cette vue permet de vérifier en un coup d’œil la formule et la date de fin.',
                'Anticiper ici évite une coupure brutale côté gérant ou accueil.',
            ],
            'success' => [
                'L’espace est accessible et la salle reste opérationnelle.',
                'Cette page sert de contrôle rapide avant la prochaine échéance.',
                'Les informations affichées peuvent être utilisées pour préparer le prochain cycle de facturation.',
            ],
            default => [
                'Le statut de facturation ne peut pas être confirmé automatiquement.',
                'Une vérification manuelle du compte et de la salle est nécessaire.',
                'Une fois la salle retrouvée, cette page redeviendra le point d’entrée du renouvellement.',
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    public function getNextSteps(): array
    {
        if (! $this->gym) {
            return [
                'Vérifier le compte connecté et son rattachement à une salle.',
                'Confirmer la formule SaaS qui doit s’appliquer.',
                'Configurer ensuite le lien ou la passerelle de paiement de renouvellement.',
            ];
        }

        return match ($this->getStatusTone()) {
            'danger' => [
                'Confirmer la formule à renouveler et le cycle correspondant.',
                'Déclencher le paiement ou le renouvellement manuel de la licence.',
                'Valider la nouvelle date d’échéance pour rétablir l’accès complet.',
            ],
            'warning' => [
                'Préparer le renouvellement avant la date limite affichée.',
                'Informer le gérant si une action de paiement est attendue.',
                'Mettre à jour l’échéance dès que le règlement est confirmé.',
            ],
            'success' => [
                'Surveiller la prochaine échéance affichée.',
                'Conserver la formule adaptée à la taille de la salle.',
                'Préparer le futur parcours de renouvellement en ligne si besoin.',
            ],
            default => [
                'Retrouver la salle liée au compte courant.',
                'Vérifier l’état `is_active` et la date `expires_at`.',
                'Revenir ensuite sur cette page pour poursuivre le design du renouvellement.',
            ],
        };
    }
}

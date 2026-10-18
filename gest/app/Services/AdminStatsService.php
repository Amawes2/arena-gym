<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\CheckIn;
use App\Models\Commande;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use App\Support\CurrentGymResolver;

final class AdminStatsService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $gymId = CurrentGymResolver::id();
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();
        $previousMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $monthStart->copy()->subSecond();

        $activeMembers = Subscription::withoutGlobalScopes()
            ->where('gym_id', $gymId)
            ->where('status', SubscriptionStatus::Active->value)
            ->where('end_date', '>=', $today)
            ->distinct('member_id')
            ->count('member_id');

        $presencesToday = CheckIn::withoutGlobalScopes()
            ->where('gym_id', $gymId)
            ->whereDate('checked_in_at', $today)
            ->count();

        $caMois = (float) Payment::withoutGlobalScopes()
            ->where('gym_id', $gymId)
            ->where('status', PaymentStatus::Completed->value)
            ->whereBetween('paid_at', [$monthStart, now()])
            ->sum('amount');

        $caMoisPrecedent = (float) Payment::withoutGlobalScopes()
            ->where('gym_id', $gymId)
            ->where('status', PaymentStatus::Completed->value)
            ->whereBetween('paid_at', [$previousMonthStart, $previousMonthEnd])
            ->sum('amount');

        $caMoisDelta = $caMoisPrecedent > 0
            ? (($caMois - $caMoisPrecedent) / $caMoisPrecedent) * 100
            : ($caMois > 0 ? 100 : 0);

        $expiringSoon = Subscription::withoutGlobalScopes()
            ->where('gym_id', $gymId)
            ->where('status', SubscriptionStatus::Active->value)
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', now()->addDays(7)->toDateString())
            ->count();

        $newActiveMembersThisMonth = Member::withoutGlobalScopes()
            ->where('gym_id', $gymId)
            ->whereDate('created_at', '>=', $monthStart->toDateString())
            ->whereDate('created_at', '<=', $today)
            ->whereHas('subscriptions', function ($query) use ($gymId, $monthStart, $today): void {
                $query
                    ->where('gym_id', $gymId)
                    ->where('status', SubscriptionStatus::Active->value)
                    ->whereDate('start_date', '>=', $monthStart->toDateString())
                    ->whereDate('start_date', '<=', $today);
            })
            ->count();

        return [
            'membres_actifs' => $activeMembers,
            'membres_actifs_delta' => $newActiveMembersThisMonth,
            'presences_aujourdhui' => $presencesToday,
            'ca_mois' => $caMois,
            'ca_mois_delta_pct' => round($caMoisDelta, 2),
            'abonnements_expirant' => $expiringSoon,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function presences7Jours(): array
    {
        $gymId = CurrentGymResolver::id();
        $days = collect(range(6, 0))->map(function (int $offset) use ($gymId): array {
            $date = now()->subDays($offset);
            $count = CheckIn::withoutGlobalScopes()
                ->where('gym_id', $gymId)
                ->whereDate('checked_in_at', $date->toDateString())
                ->count();

            return [
                'jour' => ucfirst($date->locale('fr')->dayName),
                'date' => $date->toDateString(),
                'total' => $count,
            ];
        });

        return $days->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function derniersMembres(int $limit = 5): array
    {
        return Member::withoutGlobalScopes()
            ->where('gym_id', CurrentGymResolver::id())
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Member $m): array => [
                'id' => $m->id,
                'prenom' => $m->first_name,
                'nom' => $m->last_name,
                'telephone' => $m->phone,
                'badge' => $m->isSubscriber() ? 'Abonnement' : 'Séance unique',
                'inscrit_il_y_a' => $m->created_at?->diffForHumans(),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function dernieresCommandes(int $limit = 5): array
    {
        return Commande::query()
            ->where('gym_id', CurrentGymResolver::id())
            ->with('lignes')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Commande $c): array => [
                'id' => $c->numero,
                'client' => $c->nom_client,
                'produits' => $c->lignes->pluck('nom_produit')->join(', '),
                'statut' => $c->statut->label(),
                'montant' => (float) $c->montant_total,
            ])
            ->all();
    }
}

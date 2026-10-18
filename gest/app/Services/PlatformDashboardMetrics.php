<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Gym;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PlatformDashboardMetrics
{
    /**
     * @return array{
     *     gyms_total: int,
     *     gyms_active: int,
     *     gyms_pending: int,
     *     gyms_expired: int,
     *     gyms_expiring: int,
     *     gyms_new_month: int,
     *     managers_total: int,
     *     managers_new_month: int,
     *     members_total: int,
     *     members_new_month: int
     * }
     */
    public function summary(): array
    {
        $this->authorizeSuperAdmin();

        return Cache::remember('platform-dashboard:summary:'.today()->toDateString(), now()->addMinutes(2), function (): array {
            $today = today()->toDateString();
            $monthStart = now()->startOfMonth();
            $nextMonthStart = $monthStart->copy()->addMonth();

            $gymStats = Gym::query()
                ->selectRaw('COUNT(*) AS gyms_total')
                ->selectRaw('COALESCE(SUM(CASE WHEN is_active = 1 AND is_approved = 1 AND expires_at >= ? THEN 1 ELSE 0 END), 0) AS gyms_active', [$today])
                ->selectRaw('COALESCE(SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END), 0) AS gyms_pending')
                ->selectRaw('COALESCE(SUM(CASE WHEN is_active = 1 AND is_approved = 1 AND expires_at < ? THEN 1 ELSE 0 END), 0) AS gyms_expired', [$today])
                ->first();

            $managerRoles = [UserRole::Admin->value, UserRole::Receptionist->value];

            return [
                'gyms_total' => (int) ($gymStats?->gyms_total ?? 0),
                'gyms_active' => (int) ($gymStats?->gyms_active ?? 0),
                'gyms_pending' => (int) ($gymStats?->gyms_pending ?? 0),
                'gyms_expired' => (int) ($gymStats?->gyms_expired ?? 0),
                'gyms_expiring' => (int) Gym::query()
                    ->where('is_active', true)
                    ->where('is_approved', true)
                    ->whereBetween('expires_at', [$today, today()->addDays(7)->toDateString()])
                    ->count(),
                'gyms_new_month' => (int) Gym::query()
                    ->where('created_at', '>=', $monthStart)
                    ->where('created_at', '<', $nextMonthStart)
                    ->count(),
                'managers_total' => (int) User::query()->whereIn('role', $managerRoles)->count(),
                'managers_new_month' => (int) User::query()
                    ->whereIn('role', $managerRoles)
                    ->where('created_at', '>=', $monthStart)
                    ->where('created_at', '<', $nextMonthStart)
                    ->count(),
                'members_total' => (int) Member::withoutGlobalScope('current_gym')->count(),
                'members_new_month' => (int) Member::withoutGlobalScope('current_gym')
                    ->where('created_at', '>=', $monthStart)
                    ->where('created_at', '<', $nextMonthStart)
                    ->count(),
            ];
        });
    }

    /**
     * @param  'gyms'|'members'|'managers'  $dataset
     * @return array{labels: list<string>, data: list<int>}
     */
    public function monthlyCounts(string $dataset): array
    {
        $this->authorizeSuperAdmin();

        if (! in_array($dataset, ['gyms', 'members', 'managers'], true)) {
            throw new InvalidArgumentException('Indicateur de tableau de bord inconnu.');
        }

        $start = now()->startOfMonth()->subMonths(11);
        $cacheKey = 'platform-dashboard:monthly:'.$dataset.':'.$start->format('Y-m');

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($dataset, $start): array {
            $monthExpression = match (DB::connection()->getDriverName()) {
                'mysql', 'mariadb' => "DATE_FORMAT(created_at, '%Y-%m')",
                'pgsql' => "TO_CHAR(created_at, 'YYYY-MM')",
                'sqlite' => "strftime('%Y-%m', created_at)",
                default => throw new RuntimeException('Le moteur de base de données ne prend pas en charge les graphiques mensuels.'),
            };

            $query = match ($dataset) {
                'gyms' => Gym::query(),
                'members' => Member::withoutGlobalScope('current_gym'),
                'managers' => User::query()->whereIn('role', [UserRole::Admin->value, UserRole::Receptionist->value]),
            };

            $counts = $query
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $start->copy()->addMonths(12))
                ->selectRaw("{$monthExpression} AS month_key, COUNT(*) AS monthly_count")
                ->groupByRaw($monthExpression)
                ->pluck('monthly_count', 'month_key');

            $labels = [];
            $values = [];

            for ($monthOffset = 0; $monthOffset < 12; $monthOffset++) {
                $month = $start->copy()->addMonths($monthOffset);
                $labels[] = $month->locale('fr')->translatedFormat('M y');
                $values[] = (int) ($counts[$month->format('Y-m')] ?? 0);
            }

            return [
                'labels' => $labels,
                'data' => $values,
            ];
        });
    }

    /**
     * @return array{
     *     countries: list<array{code: string, name: string, gym_count: int, user_count: int, coordinates: array{latitude: float, longitude: float}|null}>,
     *     total_gyms: int,
     *     unmapped_gyms: int
     * }
     */
    public function countryDistribution(): array
    {
        $this->authorizeSuperAdmin();

        return Cache::remember('platform-dashboard:countries', now()->addMinutes(5), function (): array {
            $countryNames = config('billing.countries', []);
            $coordinates = config('country_map', []);
            $managerRoles = [UserRole::Admin->value, UserRole::Receptionist->value];
            $rows = Gym::query()
                ->whereNotNull('country_code')
                ->where('country_code', '!=', '')
                ->selectRaw('country_code, COUNT(*) AS gym_count')
                ->groupBy('country_code')
                ->orderByDesc('gym_count')
                ->get();

            $owners = DB::table('gyms as country_gyms')
                ->join('users as owners', 'owners.id', '=', 'country_gyms.owner_id')
                ->whereNotNull('country_gyms.country_code')
                ->where('country_gyms.country_code', '!=', '')
                ->whereIn('owners.role', $managerRoles)
                ->select('country_gyms.country_code', 'country_gyms.owner_id as user_id');
            $assignedManagers = DB::table('gyms as managed_gyms')
                ->join('gym_user as assignments', 'assignments.gym_id', '=', 'managed_gyms.id')
                ->join('users as managers', 'managers.id', '=', 'assignments.user_id')
                ->whereNotNull('managed_gyms.country_code')
                ->where('managed_gyms.country_code', '!=', '')
                ->whereIn('managers.role', $managerRoles)
                ->select('managed_gyms.country_code', 'assignments.user_id');
            $userCounts = DB::query()
                ->fromSub($owners->unionAll($assignedManagers), 'country_users')
                ->select('country_code')
                ->selectRaw('COUNT(DISTINCT user_id) AS user_count')
                ->groupBy('country_code')
                ->pluck('user_count', 'country_code');

            $countries = $rows->map(function (object $row) use ($countryNames, $coordinates, $userCounts): array {
                $code = Str::upper((string) $row->country_code);

                return [
                    'code' => $code,
                    'name' => $countryNames[$code]['name'] ?? $code,
                    'gym_count' => (int) $row->gym_count,
                    'user_count' => (int) ($userCounts[$row->country_code] ?? 0),
                    'coordinates' => $coordinates[$code] ?? null,
                ];
            })->values()->all();

            return [
                'countries' => $countries,
                'total_gyms' => array_sum(array_column($countries, 'gym_count')),
                'unmapped_gyms' => array_sum(array_map(
                    fn (array $country): int => $country['coordinates'] === null ? $country['gym_count'] : 0,
                    $countries,
                )),
            ];
        });
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user() instanceof User && auth()->user()->isSuperAdmin(), 403);
    }
}

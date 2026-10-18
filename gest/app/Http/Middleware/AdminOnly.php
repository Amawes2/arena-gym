<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\Gym;
use App\Services\GymAccessService;
use App\Support\CurrentGymResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Non authentifie.'], 401);
        }

        $role = $user->roleEnum();

        $isAdmin = in_array($role, [UserRole::SuperAdmin, UserRole::Admin], true);
        $isReceptionistRequest = $role === UserRole::Receptionist && $this->isAllowedReceptionistRequest($request);

        if (! $isAdmin && ! $isReceptionistRequest) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        if (! $user->isSuperAdmin() && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $gym = Gym::query()->find(CurrentGymResolver::id());

            if (! $gym) {
                return response()->json(['message' => 'Salle introuvable.'], 404);
            }

            if (app(GymAccessService::class)->isReadOnly($gym)) {
                return response()->json([
                    'message' => 'Votre espace est en lecture seule. Consultez vos anciennes données ou demandez le renouvellement de votre abonnement.',
                    'read_only' => true,
                ], 403);
            }
        }

        return $next($request);
    }

    private function isAllowedReceptionistRequest(Request $request): bool
    {
        $allowedRoutes = [
            ['GET', 'api/membres', 'api/membres/*'],
            ['POST', 'api/membres'],
            ['PUT', 'api/membres/*'],
            ['POST', 'api/abonnements'],
            ['PUT', 'api/abonnements/*/renouveler'],
            ['GET', 'api/seances'],
            ['POST', 'api/presences'],
            ['GET', 'api/commandes'],
            ['POST', 'api/commandes'],
            ['GET', 'api/admin/stats'],
            ['GET', 'api/admin/presences/7jours'],
        ];

        return collect($allowedRoutes)->contains(function (array $route) use ($request): bool {
            $method = array_shift($route);

            return $request->isMethod($method)
                && collect($route)->contains(fn (string $pattern): bool => $request->is($pattern));
        });
    }
}

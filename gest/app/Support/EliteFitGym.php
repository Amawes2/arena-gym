<?php

namespace App\Support;

use App\Models\Gym;
use Illuminate\Support\Facades\Cache;

final class EliteFitGym
{
    public static function id(): int
    {
        $configured = config('elitefit.gym_id');

        if ($configured) {
            return (int) $configured;
        }

        return (int) Cache::rememberForever('elitefit.gym_id', function (): int {
            $gym = Gym::query()
                ->where('slug', config('elitefit.gym_slug'))
                ->first();

            if ($gym) {
                return (int) $gym->id;
            }

            $gym = Gym::query()->orderBy('id')->first();

            if (! $gym) {
                throw new \RuntimeException('Aucune salle configurée pour EliteFit.');
            }

            return (int) $gym->id;
        });
    }

    public static function resolve(): Gym
    {
        return Gym::query()->findOrFail(self::id());
    }
}

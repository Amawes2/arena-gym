<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Gym;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GymRegistrationService
{
    public function __construct(private EmailVerificationService $emailVerificationService) {}

    /**
     * @param  array{gym_name: string, prenom: string, nom: string, telephone: string, email: string, password: string, country_code?: string, currency_code?: string}  $data
     * @return array{user: User, gym: Gym, verification_email_sent: bool}
     */
    public function registerGymOwner(array $data): array
    {
        $email = strtolower(trim($data['email']));

        $registration = DB::transaction(function () use ($data, $email): array {
            PendingRegistration::query()
                ->where('email', $email)
                ->where('role', UserRole::Admin->value)
                ->delete();

            return $this->createGymAndOwner([...$data, 'email' => $email]);
        });

        $verificationEmailSent = false;

        try {
            $this->emailVerificationService->sendCode($registration['user']);
            $verificationEmailSent = true;
        } catch (Throwable $exception) {
            Log::warning('Email verification dispatch failed after gym registration.', [
                'exception' => $exception::class,
            ]);
        }

        return [...$registration, 'verification_email_sent' => $verificationEmailSent];
    }

    /**
     * @param  array{gym_name: string, prenom: string, nom: string, telephone: string, email: string, password: string, country_code?: string, currency_code?: string}  $data
     * @return array{user: User, gym: Gym}
     */
    public function createGymAndOwner(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $user = User::query()->create([
                'name' => trim($data['prenom'].' '.$data['nom']),
                'email' => strtolower(trim($data['email'])),
                'telephone' => preg_replace('/\s+/', '', $data['telephone']),
                'password' => $data['password'],
                'role' => UserRole::Admin,
            ]);

            $gym = Gym::query()->create([
                'name' => trim($data['gym_name']),
                'slug' => $this->uniqueGymSlug($data['gym_name']),
                'owner_id' => $user->id,
                'plan_saas' => 'basic',
                'is_active' => true,
                'is_approved' => false,
                'country_code' => strtoupper($data['country_code'] ?? 'CI'),
                'currency_code' => strtoupper($data['currency_code'] ?? config('billing.countries.CI.currency', 'XOF')),
                'walk_in_price' => config('elitefit.walk_in_price', 3500),
            ]);

            return ['user' => $user, 'gym' => $gym];
        });
    }

    private function uniqueGymSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'salle';
        $slug = $baseSlug;
        $suffix = 2;

        while (Gym::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}

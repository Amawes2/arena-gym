<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\EmailVerificationCode;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class PendingRegistrationService
{
    public function __construct(
        private ResendEmailService $emailService,
        private GymRegistrationService $gymRegistrationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function begin(array $data, UserRole $role): PendingRegistration
    {
        $email = Str::lower(trim((string) $data['email']));
        $data['email'] = $email;
        $data['password'] = Hash::make((string) $data['password']);

        PendingRegistration::query()
            ->where('email', $email)
            ->where('registration_expires_at', '<=', now())
            ->delete();

        $code = $this->makeCode();
        $now = now();

        $pendingRegistration = PendingRegistration::query()->create([
            'email' => $email,
            'role' => $role,
            'registration_data' => $data,
            'code_hash' => $this->hashCode($code),
            'attempts' => 0,
            'code_expires_at' => $now->copy()->addMinutes(10),
            'registration_expires_at' => $now->copy()->addHours(24),
        ]);

        $this->deliverCode($pendingRegistration, $code);

        return $pendingRegistration;
    }

    public function verifyAndCreateUser(string $email, string $code): ?User
    {
        return DB::transaction(function () use ($email, $code): ?User {
            $pendingRegistration = PendingRegistration::query()
                ->where('email', Str::lower(trim($email)))
                ->lockForUpdate()
                ->first();

            if (! $pendingRegistration) {
                return null;
            }

            if ($pendingRegistration->registration_expires_at->isPast()) {
                $pendingRegistration->delete();

                return null;
            }

            if ($pendingRegistration->code_expires_at->isPast() || $pendingRegistration->attempts >= 5) {
                return null;
            }

            if (! hash_equals($pendingRegistration->code_hash, $this->hashCode($code))) {
                $pendingRegistration->increment('attempts');

                return null;
            }

            $data = $pendingRegistration->registration_data;

            if ($pendingRegistration->role === UserRole::Admin) {
                $registration = $this->gymRegistrationService->createGymAndOwner($data);
                $user = $registration['user'];
            } elseif ($pendingRegistration->role === UserRole::Membre) {
                $user = User::query()->create([
                    'name' => trim($data['prenom'].' '.$data['nom']),
                    'email' => $pendingRegistration->email,
                    'telephone' => preg_replace('/\s+/', '', (string) $data['telephone']),
                    'password' => $data['password'],
                    'role' => UserRole::Membre,
                ]);
            } else {
                return null;
            }

            $user->forceFill(['email_verified_at' => now()])->save();
            $pendingRegistration->delete();

            return $user;
        });
    }

    public function resendPendingCode(string $email): bool
    {
        $pendingRegistration = PendingRegistration::query()
            ->where('email', Str::lower(trim($email)))
            ->where('registration_expires_at', '>', now())
            ->first();

        if (! $pendingRegistration) {
            return false;
        }

        $this->replaceCode($pendingRegistration, resetAttempts: true);

        return true;
    }

    public function resendAfterInvalidAttempt(string $email): bool
    {
        $emailHash = hash('sha256', Str::lower(trim($email)));
        $minuteKey = 'automatic-email-verification-resend-minute:'.$emailHash;
        $hourKey = 'automatic-email-verification-resend-hour:'.$emailHash;

        if (RateLimiter::tooManyAttempts($minuteKey, 1) || RateLimiter::tooManyAttempts($hourKey, 5)) {
            return false;
        }

        RateLimiter::hit($minuteKey, 60);
        RateLimiter::hit($hourKey, 3600);

        return $this->resendPendingCode($email);
    }

    private function replaceCode(PendingRegistration $pendingRegistration, bool $resetAttempts): void
    {
        $code = $this->makeCode();
        $now = now();

        $pendingRegistration->forceFill([
            'code_hash' => $this->hashCode($code),
            'attempts' => $resetAttempts ? 0 : $pendingRegistration->attempts,
            'code_expires_at' => $now->copy()->addMinutes(10),
        ])->save();

        $this->deliverCode($pendingRegistration, $code);
    }

    private function deliverCode(PendingRegistration $pendingRegistration, string $code): void
    {
        $mail = new EmailVerificationCode($code);

        $this->emailService->send($pendingRegistration->email, $mail->envelope()->subject, $mail->render());
    }

    private function makeCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}

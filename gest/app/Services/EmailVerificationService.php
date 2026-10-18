<?php

namespace App\Services;

use App\Mail\EmailVerificationCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class EmailVerificationService
{
    public function __construct(private ResendEmailService $emailService) {}

    public function sendCode(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $now = now();

        DB::table('email_verification_codes')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'code_hash' => $this->hashCode($code),
                'attempts' => 0,
                'expires_at' => $now->copy()->addMinutes(10),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $mail = new EmailVerificationCode($code);

        $this->emailService->send($user->email, $mail->envelope()->subject, $mail->render());
    }

    public function verifyCode(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code): bool {
            $verification = DB::table('email_verification_codes')
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $verification) {
                return false;
            }

            if (now()->greaterThanOrEqualTo(Carbon::parse($verification->expires_at)) || $verification->attempts >= 5) {
                DB::table('email_verification_codes')->where('user_id', $user->id)->delete();

                return false;
            }

            if (! hash_equals($verification->code_hash, $this->hashCode($code))) {
                $attempts = $verification->attempts + 1;

                if ($attempts >= 5) {
                    DB::table('email_verification_codes')->where('user_id', $user->id)->delete();
                } else {
                    DB::table('email_verification_codes')
                        ->where('user_id', $user->id)
                        ->update(['attempts' => $attempts, 'updated_at' => now()]);
                }

                return false;
            }

            $user->forceFill(['email_verified_at' => now()])->save();
            DB::table('email_verification_codes')->where('user_id', $user->id)->delete();

            return true;
        });
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}

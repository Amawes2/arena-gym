<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class ResendEmailService
{
    public function send(string $recipient, string $subject, string $html): void
    {
        $apiKey = config('services.resend.key');
        $from = config('services.resend.from');

        if (! is_string($apiKey) || trim($apiKey) === '' || ! is_string($from) || trim($from) === '') {
            throw new RuntimeException('Resend is not configured.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(10)
                ->post('https://api.resend.com/emails', [
                    'from' => $from,
                    'to' => [$recipient],
                    'subject' => $subject,
                    'html' => $html,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Resend email delivery could not reach the API.');

            throw new RuntimeException('Resend could not be reached.', previous: $exception);
        }

        if (! $response->successful()) {
            Log::warning('Resend rejected an email delivery.', ['status' => $response->status()]);

            throw new RuntimeException('Resend rejected the email delivery.');
        }
    }
}

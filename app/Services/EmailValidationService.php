<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps SendGrid's "Email Address Validation" API (a paid add-on).
 *
 * The API key configured in services.sendgrid.validation_key (from the
 * MAIL_VALIDATION_PASSWORD env var) is scoped ONLY for this endpoint - it
 * has no permission to send mail, read bounces, read blocks, etc. So this
 * class never sends anything; it only asks SendGrid up front whether an
 * address looks deliverable, which is how we catch a typo'd domain (e.g.
 * "gmial.com") before wasting a real send attempt on it.
 */
class EmailValidationService
{
    private const ENDPOINT = 'https://api.sendgrid.com/v3/validations/email';

    /**
     * @return array{checked: bool, deliverable: ?bool, verdict: ?string}
     *
     * - checked=false means we couldn't get a confident answer (no API key
     *   configured, the API call failed, or the verdict was something other
     *   than a clear Valid/Invalid, e.g. "Risky"). Callers should treat this
     *   as "proceed as normal" - a validation-service outage must never
     *   block a real user invite.
     * - checked=true, deliverable=false means SendGrid confirmed the address
     *   is not valid.
     */
    public function validate(string $email): array
    {
        $apiKey = config('services.sendgrid.validation_key');

        if (empty($apiKey)) {
            // Most likely cause if this fires unexpectedly: config was cached
            // (php artisan config:cache) before services.sendgrid.validation_key
            // was added - run `php artisan config:clear` (then config:cache
            // again if you use it) and restart the queue worker.
            Log::warning('SendGrid validation skipped - no API key configured', [
                'email' => $email,
            ]);

            return ['checked' => false, 'deliverable' => null, 'verdict' => null];
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(5)
                ->post(self::ENDPOINT, [
                    'email' => $email,
                    'source' => 'user_invite',
                ]);

            if (!$response->successful()) {
                Log::warning('SendGrid email validation call failed', [
                    'email' => $email,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['checked' => false, 'deliverable' => null, 'verdict' => null];
            }

            $verdict = $response->json('result.verdict');

            return match ($verdict) {
                'Valid' => ['checked' => true, 'deliverable' => true, 'verdict' => $verdict],
                'Invalid' => ['checked' => true, 'deliverable' => false, 'verdict' => $verdict],
                // "Risky" or anything unrecognised - not confident enough to
                // block a real invite on, so treat it like "not checked".
                default => ['checked' => false, 'deliverable' => null, 'verdict' => $verdict],
            };
        } catch (\Throwable $e) {
            Log::warning('SendGrid email validation threw', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return ['checked' => false, 'deliverable' => null, 'verdict' => null];
        }
    }
}

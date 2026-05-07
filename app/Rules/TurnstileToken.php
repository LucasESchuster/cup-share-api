<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TurnstileToken implements ValidationRule
{
    private const SITEVERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private const ERROR_MESSAGE = 'Falha na verificação anti-bot. Recarregue a página e tente novamente.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret');

        if (blank($secret)) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail(self::ERROR_MESSAGE);

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::SITEVERIFY_URL, [
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Turnstile siteverify connection failed', ['exception' => $e->getMessage()]);
            $fail(self::ERROR_MESSAGE);

            return;
        } catch (Throwable $e) {
            Log::warning('Turnstile siteverify unexpected failure', ['exception' => $e->getMessage()]);
            $fail(self::ERROR_MESSAGE);

            return;
        }

        if ($response->failed() || $response->json('success') !== true) {
            $fail(self::ERROR_MESSAGE);
        }
    }
}

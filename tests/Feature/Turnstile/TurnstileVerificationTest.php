<?php

namespace Tests\Feature\Turnstile;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TurnstileVerificationTest extends TestCase
{
    use RefreshDatabase;

    private const ERROR_MESSAGE = 'Falha na verificação anti-bot. Recarregue a página e tente novamente.';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_request_passes_when_cloudflare_returns_success(): void
    {
        config(['services.turnstile.secret' => 'fake-secret']);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $this->postJson('/api/v1/auth/magic-link', [
            'email' => 'ok@example.com',
            'cf_turnstile_response' => 'valid-token',
        ])->assertStatus(202);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
                && $request['secret'] === 'fake-secret'
                && $request['response'] === 'valid-token';
        });
    }

    public function test_request_fails_with_422_when_cloudflare_rejects_token(): void
    {
        config(['services.turnstile.secret' => 'fake-secret']);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $this->postJson('/api/v1/auth/magic-link', [
            'email' => 'bad@example.com',
            'cf_turnstile_response' => 'bad-token',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cf_turnstile_response'])
            ->assertJsonPath('errors.cf_turnstile_response.0', self::ERROR_MESSAGE);
    }

    public function test_request_fails_with_422_when_token_is_missing(): void
    {
        config(['services.turnstile.secret' => 'fake-secret']);

        Http::fake();

        $this->postJson('/api/v1/auth/magic-link', ['email' => 'no-token@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cf_turnstile_response']);

        Http::assertNothingSent();
    }

    public function test_validation_is_bypassed_when_secret_is_empty(): void
    {
        config(['services.turnstile.secret' => null]);

        Http::fake();

        $this->postJson('/api/v1/auth/magic-link', [
            'email' => 'bypass@example.com',
            'cf_turnstile_response' => 'qualquer-coisa',
        ])->assertStatus(202);

        Http::assertNothingSent();
    }

    public function test_request_fails_with_422_when_cloudflare_is_unreachable(): void
    {
        config(['services.turnstile.secret' => 'fake-secret']);

        Http::fake([
            'challenges.cloudflare.com/*' => function () {
                throw new ConnectionException('timeout');
            },
        ]);

        $this->postJson('/api/v1/auth/magic-link', [
            'email' => 'offline@example.com',
            'cf_turnstile_response' => 'valid-looking-token',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cf_turnstile_response']);
    }

    public function test_request_includes_remote_ip_in_siteverify_call(): void
    {
        config(['services.turnstile.secret' => 'fake-secret']);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $this->postJson('/api/v1/auth/magic-link', [
            'email' => 'ip@example.com',
            'cf_turnstile_response' => 'token',
        ])->assertStatus(202);

        Http::assertSent(function ($request) {
            return array_key_exists('remoteip', $request->data());
        });
    }
}

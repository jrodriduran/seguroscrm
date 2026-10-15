<?php

namespace Webkul\Insurance\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Webkul\Insurance\Exceptions\HealthSherpaFailure;

class HealthSherpaOne
{
    private const BASE_URL = 'https://api.one.healthsherpa.com';

    public function readiness(): array
    {
        return [
            'enabled' => (bool) config('healthsherpa.enabled'),
            'key_configured' => filled(config('healthsherpa.api_key')),
            'configuration_ready' => (bool) config('healthsherpa.enabled') && filled(config('healthsherpa.api_key')),
            'connection_verified' => false,
            'enrollment_available' => false,
            'policy_status_available' => false,
        ];
    }

    public function counties(string $zip): array
    {
        $result = $this->request('get', '/v1/reference/counties', ['zip_code' => $zip]);

        if (! is_array($result['counties'] ?? null)) {
            throw new HealthSherpaFailure('invalid-response', 502);
        }

        return $result;
    }

    public function quotes(array $payload): array
    {
        $result = $this->request('post', '/v1/quotes', $payload);

        if (! is_array($result['plans'] ?? null)) {
            throw new HealthSherpaFailure('invalid-response', 502);
        }

        return $result;
    }

    private function request(string $method, string $path, array $payload): array
    {
        if (! $this->readiness()['configuration_ready']) {
            throw new HealthSherpaFailure('not-configured');
        }

        // One request/second per instance; provider 429 responses remain authoritative.
        if (RateLimiter::tooManyAttempts('healthsherpa:cooldown', 1)) {
            throw new HealthSherpaFailure('rate-limited', 429, max(1, RateLimiter::availableIn('healthsherpa:cooldown')));
        }

        if (RateLimiter::tooManyAttempts('healthsherpa:requests', 1)) {
            throw new HealthSherpaFailure('rate-limited', 429, max(1, RateLimiter::availableIn('healthsherpa:requests')));
        }

        RateLimiter::hit('healthsherpa:requests', 1);

        try {
            $response = Http::acceptJson()->asJson()
                ->withHeaders(['x-api-key' => config('healthsherpa.api_key')])
                ->timeout(15)->connectTimeout(5)->withoutRedirecting()
                ->{$method}(self::BASE_URL.$path, $payload);
        } catch (ConnectionException) {
            throw new HealthSherpaFailure('unavailable');
        }

        if ($response->status() === 429) {
            $header = (string) $response->header('Retry-After');
            $seconds = max(1, ctype_digit($header) ? (int) $header : (int) strtotime($header) - time());
            RateLimiter::hit('healthsherpa:cooldown', $seconds);
            throw new HealthSherpaFailure('rate-limited', 429, $seconds);
        }

        if (! $response->successful()) {
            throw new HealthSherpaFailure(match ($response->status()) {
                400, 422 => 'invalid-request',
                401, 403 => 'access-denied',
                default => 'unavailable',
            }, in_array($response->status(), [400, 422], true) ? 422 : 503);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new HealthSherpaFailure('invalid-response', 502);
        }

        return $data;
    }
}

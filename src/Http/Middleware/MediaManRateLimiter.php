<?php

namespace FarhanShares\MediaMan\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class MediaManRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $limiter
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $limiter = 'upload')
    {
        if (!config('mediaman.rate_limiting.enabled', false)) {
            return $next($request);
        }

        $config = config("mediaman.rate_limiting.limiters.{$limiter}", [
            'requests' => 100,
            'per_minutes' => 60,
        ]);

        $key = $this->resolveRequestKey($request, $limiter);

        $response = RateLimiter::attempt(
            $key,
            $config['requests'],
            function () use ($next, $request) {
                return $next($request);
            },
            $config['per_minutes'] * 60
        );

        if (!$response) {
            return $this->buildRateLimitExceededResponse($request, $key, $config);
        }

        return $this->addHeaders(
            $response,
            $config['requests'],
            RateLimiter::remaining($key, $config['requests'])
        );
    }

    /**
     * Resolve the rate limiter key for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $limiter
     * @return string
     */
    protected function resolveRequestKey(Request $request, string $limiter): string
    {
        $strategy = config('mediaman.rate_limiting.key_strategy', 'user');

        return match ($strategy) {
            'user' => "mediaman:{$limiter}:" . ($request->user()?->id ?? $request->ip()),
            'ip' => "mediaman:{$limiter}:" . $request->ip(),
            'session' => "mediaman:{$limiter}:" . $request->session()->getId(),
            default => "mediaman:{$limiter}:" . $request->fingerprint(),
        };
    }

    /**
     * Build rate limit exceeded response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @param  array  $config
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildRateLimitExceededResponse(Request $request, string $key, array $config): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        $response = response()->json([
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
            'limit' => $config['requests'],
            'period' => $config['per_minutes'] . ' minutes',
        ], 429);

        return $this->addHeaders(
            $response,
            $config['requests'],
            0,
            $retryAfter
        );
    }

    /**
     * Add rate limit headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @param  int|null  $retryAfter
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts, ?int $retryAfter = null): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remainingAttempts),
        ]);

        if ($retryAfter !== null) {
            $response->headers->add([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            ]);
        }

        return $response;
    }

    /**
     * Clear rate limiter for a specific key.
     *
     * @param  string  $key
     * @return void
     */
    public static function clear(string $key): void
    {
        RateLimiter::clear($key);
    }

    /**
     * Get remaining attempts for a key.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return int
     */
    public static function remaining(string $key, int $maxAttempts): int
    {
        return RateLimiter::remaining($key, $maxAttempts);
    }
}

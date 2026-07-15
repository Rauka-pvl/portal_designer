<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Predictable "Back" navigation for Blade pages.
 *
 * Priority:
 * 1) explicit ?from= (safe internal URL)
 * 2) browser history (same-origin referrer) — handled on the client
 * 3) sensible fallback route for the entity
 */
class BackNavigation
{
    /** Query param used across the app. */
    public const FROM_PARAM = 'from';

    /**
     * Paths that should never be used as a return target.
     *
     * @var list<string>
     */
    private const BLOCKED_PATH_PREFIXES = [
        '/login',
        '/register',
        '/logout',
        '/forgot-password',
        '/reset-password',
        '/sanctum',
        '/up',
    ];

    public static function isSafeInternalUrl(?string $url): bool
    {
        if (! is_string($url) || trim($url) === '') {
            return false;
        }

        $url = trim($url);

        // Relative app path.
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return ! self::isBlockedPath($url);
        }

        $app = rtrim((string) config('app.url'), '/');
        if ($app === '') {
            return false;
        }

        $parts = parse_url($url);
        $appParts = parse_url($app);
        if (! is_array($parts) || ! is_array($appParts)) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $appHost = strtolower((string) ($appParts['host'] ?? ''));
        if ($host === '' || $appHost === '' || $host !== $appHost) {
            return false;
        }

        $path = (string) ($parts['path'] ?? '/');

        return ! self::isBlockedPath($path);
    }

    public static function normalizeInternalUrl(string $url): string
    {
        $url = trim($url);
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $parts = parse_url($url);
        $path = (string) ($parts['path'] ?? '/');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $path.$query.$fragment;
    }

    /**
     * Resolve href for the Back control (without relying on history.back).
     */
    public static function resolve(string $fallback, ?string $from = null): string
    {
        $from = $from ?? request()->query(self::FROM_PARAM);
        if (is_string($from) && self::isSafeInternalUrl($from)) {
            return self::normalizeInternalUrl($from);
        }

        return $fallback;
    }

    /**
     * Append or replace ?from= on a destination URL.
     */
    public static function withFrom(string $destination, ?string $from = null): string
    {
        $from = $from ?? self::currentUrlForFrom();
        if (! is_string($from) || ! self::isSafeInternalUrl($from)) {
            return $destination;
        }

        $fromPath = self::normalizeInternalUrl($from);

        // Avoid nesting from= infinitely.
        $fromPath = self::stripFromParam($fromPath);

        $hash = '';
        if (str_contains($destination, '#')) {
            [$destination, $hash] = explode('#', $destination, 2);
            $hash = '#'.$hash;
        }

        $separator = str_contains($destination, '?') ? '&' : '?';

        // Replace existing from=
        if (str_contains($destination, self::FROM_PARAM.'=')) {
            $destination = (string) preg_replace(
                '/([?&])'.preg_quote(self::FROM_PARAM, '/').'=[^&]*/',
                '$1'.self::FROM_PARAM.'='.rawurlencode($fromPath),
                $destination
            );

            return $destination.$hash;
        }

        return $destination.$separator.self::FROM_PARAM.'='.rawurlencode($fromPath).$hash;
    }

    public static function currentUrlForFrom(): string
    {
        return self::stripFromParam(request()->getRequestUri());
    }

    public static function stripFromParam(string $url): string
    {
        $hash = '';
        if (str_contains($url, '#')) {
            [$url, $hash] = explode('#', $url, 2);
            $hash = '#'.$hash;
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return $url.$hash;
        }

        $path = (string) ($parts['path'] ?? '/');
        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            unset($query[self::FROM_PARAM]);
        }

        $qs = http_build_query($query);

        return $path.($qs !== '' ? '?'.$qs : '').$hash;
    }

    private static function isBlockedPath(string $path): bool
    {
        $path = '/'.ltrim($path, '/');
        foreach (self::BLOCKED_PATH_PREFIXES as $prefix) {
            if ($path === $prefix || Str::startsWith($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}

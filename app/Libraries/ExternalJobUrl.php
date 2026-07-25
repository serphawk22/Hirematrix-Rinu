<?php

namespace App\Libraries;

/**
 * Shared URL and age rules for jobs imported from third-party sources.
 */
class ExternalJobUrl
{
    private const TRACKING_KEYS = [
        'fbclid', 'gclid', 'mc_cid', 'mc_eid', 'ref', 'referrer', 'source',
        'trk', 'trackingid', 'utm_campaign', 'utm_content', 'utm_medium',
        'utm_source', 'utm_term',
    ];

    public static function canonicalize(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5));
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return '';
        }

        $host = preg_replace('/^www\./i', '', $host) ?? $host;
        $port = isset($parts['port']) && !in_array((int) $parts['port'], [80, 443], true)
            ? ':' . (int) $parts['port']
            : '';
        $path = preg_replace('#/+#', '/', (string) ($parts['path'] ?? '/')) ?? '/';
        $path = $path === '/' ? '/' : rtrim($path, '/');

        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);
        foreach (array_keys($query) as $key) {
            if (in_array(strtolower((string) $key), self::TRACKING_KEYS, true)) {
                unset($query[$key]);
            }
        }
        ksort($query);
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        // HTTP and HTTPS variants identify the same posting. Prefer HTTPS in
        // stored canonical values without rewriting the candidate-facing URL.
        return 'https://' . $host . $port . $path . ($queryString !== '' ? '?' . $queryString : '');
    }

    public static function hash(string $url): string
    {
        $canonical = self::canonicalize($url);
        return $canonical === '' ? '' : hash('sha256', $canonical);
    }

    public static function postedAtIsExpired(?string $postedAtRaw, int $maxAgeDays = 30): bool
    {
        $text = strtolower(trim((string) $postedAtRaw));
        if ($text === '' || in_array($text, ['recently', 'today', 'just now'], true)) {
            return false;
        }

        if (preg_match('/\b(\d+)\s*(day|week|month|year|yr)s?\b/', $text, $match) !== 1) {
            return false;
        }

        $value = (int) $match[1];
        $days = match ($match[2]) {
            'week' => $value * 7,
            'month' => $value * 30,
            'year', 'yr' => $value * 365,
            default => $value,
        };

        return $days > $maxAgeDays;
    }
}

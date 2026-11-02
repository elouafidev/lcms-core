<?php

declare(strict_types=1);

namespace Plugins\Analytics\Services;

class UserAgentParser
{
    /**
     * Detect device type (desktop, mobile, tablet).
     */
    public static function detectDevice(?string $userAgent): string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return 'desktop';
        }

        $ua = mb_strtolower($userAgent);

        if (preg_match('/ipad|tablet|(android(?!.*mobile))/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/mobile|iphone|ipod|android|blackberry|opera mini|iemobile|wpdesktop/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Detect browser family (Chrome, Firefox, Safari, Edge, Opera, Other).
     */
    public static function detectBrowser(?string $userAgent): string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return 'Other';
        }

        $ua = mb_strtolower($userAgent);

        if (str_contains($ua, 'edg/')) {
            return 'Edge';
        }

        if (str_contains($ua, 'opr/') || str_contains($ua, 'opera')) {
            return 'Opera';
        }

        if (str_contains($ua, 'chrome') || str_contains($ua, 'crios')) {
            return 'Chrome';
        }

        if (str_contains($ua, 'firefox') || str_contains($ua, 'fxios')) {
            return 'Firefox';
        }

        if (str_contains($ua, 'safari')) {
            return 'Safari';
        }

        return 'Other';
    }

    /**
     * Determine if user agent corresponds to a search engine bot or crawler.
     */
    public static function isBot(?string $userAgent): bool
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return false;
        }

        $ua = mb_strtolower($userAgent);
        $patterns = [
            'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
            'curl', 'wget', 'python', 'postman', 'headless',
            'lighthouse', 'pingdom', 'uptime', 'facebookexternalhit',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($ua, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract normalized referrer host.
     */
    public static function extractReferrerHost(?string $referrer, ?string $appUrl = null): ?string
    {
        if ($referrer === null || trim($referrer) === '') {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        if (! is_string($host) || trim($host) === '') {
            return null;
        }

        $cleanHost = mb_strtolower(trim($host));
        if (str_starts_with($cleanHost, 'www.')) {
            $cleanHost = mb_substr($cleanHost, 4);
        }

        // Ignore internal referrers matching current app host
        if ($appUrl !== null) {
            $appHost = parse_url($appUrl, PHP_URL_HOST);
            if (is_string($appHost)) {
                $cleanAppHost = mb_strtolower(trim($appHost));
                if (str_starts_with($cleanAppHost, 'www.')) {
                    $cleanAppHost = mb_substr($cleanAppHost, 4);
                }
                if ($cleanHost === $cleanAppHost) {
                    return null;
                }
            }
        }

        return $cleanHost;
    }
}

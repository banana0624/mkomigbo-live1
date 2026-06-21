<?php
declare(strict_types=1);

final class BootstrapFirewall
{
    private static array $rules = [
        'deny_patterns' => [
            '/private\/bootstrap\.php$/',
            '/_bootstrap\.php$/',
            '/bootstrap\.php$/'
        ],

        'allowed_roots' => []
    ];

    private static bool $blockMode = false;

    public static function enableBlockMode(bool $state = true): void
    {
        self::$blockMode = $state;
    }

    public static function registerAllowedRoot(string $path): void
    {
        self::$rules['allowed_roots'][] = realpath($path);
    }

    public static function check(string $file, string $caller = ''): void
    {
        $real = realpath($file) ?: $file;

        foreach (self::$rules['deny_patterns'] as $pattern) {
            if (preg_match($pattern, $real)) {
                self::violation("DENIED BOOTSTRAP PATH: {$real}", $caller);
            }
        }

        foreach (self::$rules['allowed_roots'] as $root) {
            if ($root && str_starts_with($real, $root)) {
                return;
            }
        }

        // soft drift detection (not blocking by default)
        if (str_contains($real, 'legacy') || str_contains($real, 'old')) {
            error_log("[FIREWALL WARNING] Potential drift: {$real}");
        }
    }

    private static function violation(string $message, string $caller): void
    {
        error_log("[BOOTSTRAP FIREWALL] {$message} | caller={$caller}");

        if (self::$blockMode) {
            http_response_code(500);
            exit($message);
        }
    }
}
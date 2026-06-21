<?php
declare(strict_types=1);

final class SelfHealingKernel
{
    private static array $report = [];
    private static bool $autoFix = false;

    public static function enableAutoFix(bool $state = true): void
    {
        self::$autoFix = $state;
    }

    public static function scanFile(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        $content = file_get_contents($file);

        self::detectLegacyBootstrap($file, $content);
        self::detectPrivateBootstrap($file, $content);
    }

    private static function detectLegacyBootstrap(string $file, string $content): void
    {
        if (str_contains($content, '_bootstrap.php')) {
            self::report($file, '_bootstrap.php detected', [
                'fix' => "_init.php"
            ]);

            self::autoFix($file, $content, "_bootstrap.php", "_init.php");
        }
    }

    private static function detectPrivateBootstrap(string $file, string $content): void
    {
        if (str_contains($content, "private/bootstrap.php")) {
            self::report($file, 'private/bootstrap.php detected', [
                'fix' => "_init.php"
            ]);

            self::autoFix($file, $content, "private/bootstrap.php", "_init.php");
        }
    }

    private static function autoFix(string $file, string $content, string $from, string $to): void
    {
        if (!self::$autoFix) {
            return;
        }

        $new = str_replace($from, $to, $content);

        if ($new !== $content) {
            file_put_contents($file, $new);
            error_log("[SELF-HEAL] Fixed {$from} → {$to} in {$file}");
        }
    }

    private static function report(string $file, string $issue, array $meta = []): void
    {
        self::$report[] = [
            'file' => $file,
            'issue' => $issue,
            'meta' => $meta
        ];

        error_log("[SELF-HEAL REPORT] {$file} :: {$issue}");
    }

    public static function runScan(string $root): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                self::scanFile($file->getPathname());
            }
        }
    }

    public static function getReport(): array
    {
        return self::$report;
    }
}
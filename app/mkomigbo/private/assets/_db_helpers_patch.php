<?php
declare(strict_types=1);

if (!function_exists('mk_db_env')) {
  function mk_db_env(string $key): string {
    $v = getenv($key);
    return ($v === false) ? '' : (string)$v;
  }
}

if (!function_exists('mk_db_redact_dsn')) {
  function mk_db_redact_dsn(string $dsn): string {
    $dsn = preg_replace('/(password=)([^;]+)/i', '$1[REDACTED]', $dsn);
    $dsn = preg_replace('/(pwd=)([^;]+)/i', '$1[REDACTED]', $dsn);
    return (string)$dsn;
  }
}

if (!function_exists('mk_db_redact_context')) {
  function mk_db_redact_context(array $context): array {
    $redactKeys = ['pass','password','db_pass','DB_PASS','secret','token'];

    foreach ($context as $k => $v) {
      $lk = strtolower((string)$k);

      foreach ($redactKeys as $rk) {
        $rr = strtolower($rk);
        if ($lk === $rr || str_contains($lk, $rr)) {
          $context[$k] = '[REDACTED]';
          continue 2;
        }
      }

      if ($lk === 'dsn' && is_string($v)) {
        $context[$k] = mk_db_redact_dsn($v);
      }
    }
    return $context;
  }
}

if (!function_exists('mk_db_log')) {
  function mk_db_log(string $level, string $message, array $context = []): void {
    $context = mk_db_redact_context($context);
    if (function_exists('app_log')) {
      app_log($level, $message, $context);
      return;
    }
    $suffix = $context ? (' ' . json_encode($context, JSON_UNESCAPED_SLASHES)) : '';
    error_log('[DB][' . strtoupper($level) . '] ' . $message . $suffix);
  }
}

if (!function_exists('mk_db_guess_dsn')) {
  function mk_db_guess_dsn(): string {
    $dsn = defined('DB_DSN') ? (string)DB_DSN : mk_db_env('DB_DSN');
    $dsn = trim($dsn);
    if ($dsn !== '') return $dsn;

    $host = trim(mk_db_env('DB_HOST'));
    $name = trim(mk_db_env('DB_NAME'));
    $port = trim(mk_db_env('DB_PORT'));
    if ($host === '' || $name === '') return '';

    $portNum = (int)$port;
    if ($portNum <= 0) $portNum = 3306;

    return "mysql:host={$host};port={$portNum};dbname={$name};charset=utf8mb4";
  }
}

if (!function_exists('mk_db_credentials')) {
  function mk_db_credentials(): array {
    $user = defined('DB_USER') ? (string)DB_USER : mk_db_env('DB_USER');
    $pass = (string) (getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: '');
    return [trim($user), $pass];
  }
}

if (!function_exists('mk_db_is_transient_disconnect')) {
  function mk_db_is_transient_disconnect(Throwable $e): bool {
    $msg = strtolower($e->getMessage());
    return str_contains($msg, 'server has gone away') || str_contains($msg, 'lost connection');
  }
}

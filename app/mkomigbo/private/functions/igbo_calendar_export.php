<?php
declare(strict_types=1);

/**
 * /private/functions/igbo_calendar_export.php
 * Hard-required by initialize.php
 *
 * Provides:
 *   mk_igbo_calendar_export_payload(int $year): array
 */

if (!function_exists('mk_igbo_calendar_export_payload')) {

  function mk_igbo_calendar_export_payload(int $year): array
  {
    if ($year < 1900 || $year > 2200) {
      $year = (int)gmdate('Y');
    }

    // Load engine on-demand (canonical path only)
    if (!class_exists('IgboCalendarYear')) {
      $engine = rtrim((string)PRIVATE_PATH, "/\\") . '/calendar/IgboCalendarYear.php';
      if (!is_file($engine)) {
        return [
          'ok' => false,
          'year' => $year,
          'error' => 'IgboCalendarYear engine file missing',
          'missing' => $engine,
        ];
      }
      require_once $engine;
    }

    if (!class_exists('IgboCalendarYear')) {
      return [
        'ok' => false,
        'year' => $year,
        'error' => 'IgboCalendarYear class not available after include',
      ];
    }

    // Build object with flexible constructor handling
    try {
      $rc = new ReflectionClass('IgboCalendarYear');
      $ctor = $rc->getConstructor();

      if (!$ctor || $ctor->getNumberOfParameters() === 0) {
        $obj = $rc->newInstance();
      } else {
        $args = [];
        foreach ($ctor->getParameters() as $idx => $p) {
          $t = $p->getType();
          $tname = ($t instanceof ReflectionNamedType) ? $t->getName() : '';

          // Common patterns:
          // 1) first param is DateTimeImmutable (approx start)
          // 2) first param is int year
          // For other params: use defaults if available, else minimal safe fallbacks.
          if ($idx === 0) {
            if ($tname === 'DateTimeImmutable' || $tname === '\DateTimeImmutable') {
              $args[] = new DateTimeImmutable(sprintf('%04d-01-01T00:00:00Z', $year));
              continue;
            }
            if ($tname === 'int' || $tname === 'integer' || $tname === '') {
              // If untyped or int, try year
              $args[] = $year;
              continue;
            }
          }

          if ($p->isDefaultValueAvailable()) {
            $args[] = $p->getDefaultValue();
            continue;
          }

          // Last resort fallbacks
          if ($tname === 'DateTimeImmutable' || $tname === '\DateTimeImmutable') {
            $args[] = new DateTimeImmutable(sprintf('%04d-01-01T00:00:00Z', $year));
          } elseif ($tname === 'int' || $tname === 'integer' || $tname === '') {
            $args[] = 0;
          } elseif ($tname === 'string') {
            $args[] = '';
          } elseif ($tname === 'bool' || $tname === 'boolean') {
            $args[] = false;
          } elseif ($tname === 'array') {
            $args[] = [];
          } else {
            // cannot guess; pass null if allowed, else fail
            if ($t instanceof ReflectionNamedType && $t->allowsNull()) $args[] = null;
            else throw new RuntimeException('Cannot satisfy constructor param: ' . $p->getName());
          }
        }

        $obj = $rc->newInstanceArgs($args);
      }
    } catch (Throwable $e) {
      return [
        'ok' => false,
        'year' => $year,
        'error' => 'IgboCalendarYear constructor failed: ' . $e->getMessage(),
      ];
    }

    // Export
    try {
      if (method_exists($obj, 'toArray')) {
        return ['ok' => true, 'year' => $year, 'engine' => 'IgboCalendarYear::toArray', 'payload' => $obj->toArray()];
      }
      if (method_exists($obj, 'export')) {
        return ['ok' => true, 'year' => $year, 'engine' => 'IgboCalendarYear::export', 'payload' => $obj->export()];
      }
      return ['ok' => true, 'year' => $year, 'engine' => 'get_object_vars', 'payload' => get_object_vars($obj)];
    } catch (Throwable $e) {
      return [
        'ok' => false,
        'year' => $year,
        'error' => 'Export failed: ' . $e->getMessage(),
      ];
    }
  }
}

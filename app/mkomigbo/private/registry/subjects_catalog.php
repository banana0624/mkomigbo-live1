<?php
declare(strict_types=1);

/**
 * Compatibility shim: subjects_catalog()
 * Returns slug-keyed subject rows derived from the current registry.
 */

if (!function_exists('subjects_catalog')) {
  function subjects_catalog(): array {
    if (function_exists('subjects_sorted_registry')) {
      $out = [];
      foreach (subjects_sorted_registry() as $row) {
        $slug = strtolower(trim((string)($row['slug'] ?? '')));
        if ($slug === '') continue;

        $out[$slug] = [
          'id'               => (int)($row['id'] ?? 0),
          'name'             => (string)($row['name'] ?? ''),
          'meta_description' => (string)($row['description'] ?? ''),
        ];
      }
      return $out;
    }
    return [];
  }
}

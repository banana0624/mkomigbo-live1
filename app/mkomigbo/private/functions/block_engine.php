<?php

/**
 * BLOCK ENGINE
 * SaaS-style rendering system
 */

if (!function_exists('mk_block_registry')) {
  function mk_block_registry(): array {
    return [
      'hero' => 'mk_block_hero',
      'stats' => 'mk_block_stats',
      'featured_subjects' => 'mk_block_featured_subjects',
    ];
  }
}

function mk_render_block(string $type, array $data = []): void
{
  $registry = mk_block_registry();

  if (!isset($registry[$type])) {
    echo "<!-- Unknown block: {$type} -->";
    return;
  }

  $fn = $registry[$type];
  $fn($data);
}

/* -------------------------
 * BLOCK: HERO
 * ------------------------- */
function mk_block_hero(array $data): void
{
  $title = $data['title'] ?? 'Mkomigbo';
  $subtitle = $data['subtitle'] ?? 'Knowledge platform';

  echo "<header class='mk-hero'>";
  echo "<h1>{$title}</h1>";
  echo "<p>{$subtitle}</p>";
  echo "</header>";
}

/* -------------------------
 * BLOCK: STATS
 * ------------------------- */
function mk_block_stats(array $data): void
{
  $subjects = $data['subjects'] ?? 0;

  echo "<section class='mk-grid'>";
  echo "<div class='mk-card'><h3>📚 Subjects</h3><p>{$subjects}</p></div>";
  echo "<div class='mk-card'><h3>🌍 Platform</h3><p>Active CMS</p></div>";
  echo "<div class='mk-card'><h3>⚡ Status</h3><p>Stable Kernel</p></div>";
  echo "</section>";
}

/* -------------------------
 * BLOCK: FEATURED SUBJECTS
 * ------------------------- */
function mk_block_featured_subjects(array $data): void
{
  $items = $data['items'] ?? [];

  echo "<section>";
  echo "<h2>Featured Subjects</h2>";
  echo "<div class='mk-grid'>";

  foreach ($items as $s) {
    $slug = htmlspecialchars($s['slug'] ?? '');
    $name = htmlspecialchars($s['name'] ?? $slug);

    echo "<a class='mk-card' href='/subjects/{$slug}/'>";
    echo "<h3>{$name}</h3>";
    echo "</a>";
  }

  echo "</div>";
  echo "</section>";
}
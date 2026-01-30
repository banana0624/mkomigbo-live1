<?php
declare(strict_types=1);
// /private/functions/public_attachments.php

if (!function_exists('mk_safe_http_url')) {
  function mk_safe_http_url(string $url): string {
    $url = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $url));
    if ($url === '') return '';

    $p = @parse_url($url);
    if (!is_array($p) || empty($p['scheme']) || empty($p['host'])) return '';

    $scheme = strtolower((string)$p['scheme']);
    if (!in_array($scheme, ['http','https'], true)) return '';

    // block creds
    if (!empty($p['user']) || !empty($p['pass'])) return '';

    return $url;
  }
}

if (!function_exists('mk_link_label_from_url')) {
  function mk_link_label_from_url(string $url, string $fallback = 'Open link'): string {
    $p = @parse_url($url);
    if (!is_array($p)) return $fallback;

    $host = strtolower((string)($p['host'] ?? ''));
    $path = (string)($p['path'] ?? '');
    $query = (string)($p['query'] ?? '');

    // helper: normalize host “www.”
    $hostClean = preg_replace('/^www\./', '', $host);

    // YouTube labels
    if ($hostClean === 'youtube.com' || $hostClean === 'youtu.be') {
      // Try to detect channel/user/handle/video
      if ($hostClean === 'youtu.be' && $path !== '') {
        return 'YouTube — Video';
      }
      if (str_starts_with($path, '/@')) return 'YouTube — ' . ltrim($path, '/');
      if (str_starts_with($path, '/channel/')) return 'YouTube — Channel';
      if (str_starts_with($path, '/c/')) return 'YouTube — Channel';
      if (str_starts_with($path, '/user/')) return 'YouTube — User';
      if (str_starts_with($path, '/watch') && str_contains($query, 'v=')) return 'YouTube — Video';
      return 'YouTube';
    }

    // Wikipedia labels
    if (str_ends_with($hostClean, 'wikipedia.org')) {
      // /wiki/Some_Title
      if (str_starts_with($path, '/wiki/')) {
        $slug = urldecode(substr($path, 6));
        $slug = str_replace('_', ' ', $slug);
        $slug = trim($slug);
        if ($slug !== '') return 'Wikipedia — ' . $slug;
      }
      return 'Wikipedia';
    }

    // Default: domain + short path hint
    $label = ucfirst($hostClean);
    if ($path && $path !== '/') {
      $short = $path;
      if (strlen($short) > 28) $short = substr($short, 0, 28) . '…';
      $label .= ' — ' . $short;
    }

    return $label;
  }
}

if (!function_exists('mk_public_render_attachments')) {
  /**
   * @param array<int,array<string,mixed>> $attachments Rows from page_files (or similar)
   */
  function mk_public_render_attachments(array $attachments): string {
    if (empty($attachments)) return '';

    $items = [];

    foreach ($attachments as $row) {
      // Try common columns
      $title = '';
      foreach (['label','title','name','display_name','caption'] as $k) {
        if (isset($row[$k]) && is_string($row[$k]) && trim($row[$k]) !== '') {
          $title = trim($row[$k]);
          break;
        }
      }

      $url = '';
      foreach (['external_url','source_url','url','href','link_url'] as $k) {
        if (isset($row[$k]) && is_string($row[$k]) && trim($row[$k]) !== '') {
          $url = trim($row[$k]);
          break;
        }
      }

      $safe = mk_safe_http_url($url);
      if ($safe === '') continue;

      $label = ($title !== '') ? $title : mk_link_label_from_url($safe, 'Open link');

      // Escape label (assumes you have h(); if not, fallback to htmlspecialchars)
      $esc = function_exists('h')
        ? 'h'
        : function(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };

      $items[] =
        '<li class="mk-attach-item">'
        . '<a class="mk-attach-link" href="' . $esc($safe) . '" target="_blank" rel="noopener noreferrer">'
        . $esc($label)
        . '</a>'
        . '</li>';
    }

    if (empty($items)) return '';

    return
      '<section class="mk-attachments" aria-label="Attachments">'
      . '<h3 class="mk-attachments-title">Attachments</h3>'
      . '<ul class="mk-attach-list">'
      . implode('', $items)
      . '</ul>'
      . '</section>';
  }
}

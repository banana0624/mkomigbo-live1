<?php
declare(strict_types=1);

/**
 * /private/functions/seo_defaults.php
 * Global SEO defaults used by public_header.php when controller did not supply all fields.
 */

if (!function_exists('mk_seo_default')) {
  function mk_seo_default(): array
  {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = (string)($_SERVER['HTTP_HOST'] ?? '');
    $path   = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/');
    $canon  = ($host !== '') ? ($scheme . '://' . $host . $path) : $path;

    return [
      'title'       => 'Mkomigbo',
      'description' => 'Mkomigbo — an Igbo knowledge index: history, culture, language, people, and primary resources.',
      'keywords'    => 'igbo, culture, history, language, people, biafra, africa',
      'canonical'   => $canon,
      'og_type'     => 'website',
      // optional if you have a default share image:
      // 'og_image'     => '/lib/images/og-default.png',
      // 'twitter_image'=> '/lib/images/og-default.png',
    ];
  }
}

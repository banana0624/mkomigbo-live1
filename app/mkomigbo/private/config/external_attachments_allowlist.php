<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/config/external_attachments_allowlist.php
 *
 * External attachment allowlist.
 *
 * SECURITY MODEL:
 * - Only allow HTTPS URLs
 * - Only allow hosts explicitly listed here
 * - Subdomains are allowed ONLY when 'subdomains' => true
 * - Optional 'paths' restrict allowed path prefixes
 * - We never fetch remote content server-side
 * - Public "open" (staff) is a redirect after re-validation
 *
 * FORMAT:
 * return [
 *   'example.com' => [
 *     'subdomains' => true,      // allow *.example.com too
 *     'paths' => ['/wiki/', '/watch'],  // optional
 *   ],
 *   'exact.host.com' => [
 *     'paths' => ['/'],
 *   ],
 * ];
 */

return [

  /* -----------------------------
     YouTube
  ----------------------------- */

  'youtube.com' => [
    'subdomains' => true, // www.youtube.com, m.youtube.com, etc.
    'paths' => [
      '/watch',
      '/embed/',
      '/shorts/',
      '/playlist',
      '/@',
      '/channel/',
      '/c/',
      '/user/',
      '/live/',
    ],
  ],
  'youtu.be' => [
    'paths' => ['/'], // short links
  ],

  /* -----------------------------
     Wikipedia / Wikimedia
  ----------------------------- */

  'wikipedia.org' => [
    'subdomains' => true, // en.wikipedia.org, ig.wikipedia.org, etc.
    'paths' => ['/wiki/'],
  ],
  'wikimedia.org' => [
    'subdomains' => true, // commons.wikimedia.org, upload.wikimedia.org, etc.
    'paths' => ['/'],
  ],

  /* -----------------------------
     Internet Archive
  ----------------------------- */

  'archive.org' => [
    'subdomains' => true,
    'paths' => [
      '/details/',
      '/download/',
      '/stream/',
    ],
  ],

  /* -----------------------------
     GitHub
  ----------------------------- */

  'github.com' => [
    'paths' => ['/'],
  ],
  'raw.githubusercontent.com' => [
    'paths' => ['/'],
  ],

  /* -----------------------------
     Optional media platforms (safe defaults)
     (Leave them if you want them; remove if not needed)
  ----------------------------- */

  'vimeo.com' => [
    'subdomains' => true,
    'paths' => ['/'],
  ],
  'soundcloud.com' => [
    'subdomains' => true,
    'paths' => ['/'],
  ],

  // Add more trusted domains here...
];

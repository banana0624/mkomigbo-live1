<?php
/**
 * /app/mkomigbo/private/config/external_attachments_allowlist.php
 *
 * External attachment allowlist.
 *
 * SECURITY MODEL:
 * - Only allow HTTPS URLs (enforced in validator)
 * - Only allow hosts explicitly listed here
 * - Subdomains are allowed ONLY when 'subdomains' => true
 * - Optional 'paths' restrict allowed path prefixes (string prefix match)
 * - We never fetch remote content server-side
 * - Staff "open" is a redirect after re-validation
 *
 * FORMAT:
 * return [
 *   'example.com' => [
 *     'subdomains' => true,           // allow *.example.com too
 *     'paths' => ['/a/', '/b/index'], // optional path-prefix allowlist
 *   ],
 *   '*.example.org' => [
 *     'paths' => ['/wiki/'],          // wildcard host keys supported too
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
    // short links: /{id}
    'paths' => ['/'],
  ],

  /* -----------------------------
     Wikipedia
  ----------------------------- */

  'wikipedia.org' => [
    'subdomains' => true, // en.wikipedia.org, ig.wikipedia.org, etc.
    'paths' => [
      '/wiki/',        // standard articles
      '/w/index.php',  // search, oldid, etc.
    ],
  ],

  /* -----------------------------
     Wikimedia (commons + uploads)
  ----------------------------- */

  'wikimedia.org' => [
    'subdomains' => true,
    'paths' => [
      '/wiki/',       // commons.wikimedia.org/wiki/...
      '/w/index.php', // commons searches
    ],
  ],
  'upload.wikimedia.org' => [
    'paths' => [
      '/wikipedia/',  // direct media under upload.wikimedia.org/wikipedia/...
      '/wikimedia/',  // direct media under upload.wikimedia.org/wikimedia/...
      '/commons/',    // direct media under upload.wikimedia.org/commons/...
    ],
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
     Optional media platforms (safe-ish defaults)
     Remove anything you don't need.
  ----------------------------- */

  'vimeo.com' => [
    'subdomains' => true,
    'paths' => ['/'],
  ],
  'soundcloud.com' => [
    'subdomains' => true,
    'paths' => ['/'],
  ],
];

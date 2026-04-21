<?php
declare(strict_types=1);

/**
 * /public/platforms/media-library/index.php
 * Platforms: Media Library
 */

$pf = [
  'slug'   => 'media-library',
  'pretty' => 'Media Library',
  'desc'   => 'A curated media library: images, audio, video, documents, and referenced materials aligned to Subjects.',
  'lede'   => 'A unified library for media and documents — organized by Subjects, pages, and verified sources.',
  'cards'  => [
    ['icon'=>'🗂', 'title'=>'Collections', 'desc'=>'Media grouped by Subject, page, and theme.', 'pills'=>['Planned'], 'accent'=>'#2F4A5A'],
    ['icon'=>'🎞', 'title'=>'Audio & video', 'desc'=>'Interviews, talks, documentaries, and clips.', 'pills'=>['Planned'], 'accent'=>'#2F3A4A'],
    ['icon'=>'📄', 'title'=>'Documents', 'desc'=>'PDFs, scans, and reference files where permitted.', 'pills'=>['Planned'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

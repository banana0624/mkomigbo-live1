<?php
declare(strict_types=1);

/**
 * /public/platforms/knowledge-index/index.php
 * Platforms: Knowledge Index
 */

$pf = [
  'slug'   => 'knowledge-index',
  'pretty' => 'Knowledge Index',
  'desc'   => 'A structured index of topics, entities, timelines, and references across all Subjects.',
  'lede'   => 'A cross-linked index that helps you navigate topics, people, places, timelines, and sources.',
  'cards'  => [
    ['icon'=>'🧭', 'title'=>'Browse by topic', 'desc'=>'Find related pages across Subjects and themes.', 'pills'=>['Planned'], 'accent'=>'#2F4A5A'],
    ['icon'=>'👤', 'title'=>'People & places', 'desc'=>'Entities with pages, references, and media.', 'pills'=>['Planned'], 'accent'=>'#2F3A4A'],
    ['icon'=>'⏳', 'title'=>'Timelines', 'desc'=>'Chronologies anchored to sources and context.', 'pills'=>['Planned'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

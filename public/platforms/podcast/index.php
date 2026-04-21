<?php
declare(strict_types=1);

$pf = [
  'slug'   => 'podcast',
  'pretty' => 'Podcast',
  'desc'   => 'Audio episodes: conversations, history, interviews, and cultural insights.',
  'lede'   => 'Audio conversations, interviews, and research-led storytelling — organized by Subjects.',
  'cards'  => [
    ['icon'=>'🎙', 'title'=>'Interviews', 'desc'=>'Researchers, elders, contributors, and specialists.', 'pills'=>['Planned'], 'accent'=>'#2F4A5A'],
    ['icon'=>'📚', 'title'=>'Explainers', 'desc'=>'Short episodes linked to subject pages and sources.', 'pills'=>['Planned'], 'accent'=>'#2F3A4A'],
    ['icon'=>'🧭', 'title'=>'Series', 'desc'=>'Multi-part investigations with referenced materials.', 'pills'=>['Planned'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

<?php
declare(strict_types=1);

$pf = [
  'slug'   => 'vlog',
  'pretty' => 'Vlog',
  'desc'   => 'Video stories, explainers, and field clips aligned to Subjects and contributors.',
  'lede'   => 'Video explainers and stories linked directly to Subjects and contributor research.',
  'cards'  => [
    ['icon'=>'▶', 'title'=>'Explainers', 'desc'=>'Short videos anchored to pages and sources.', 'pills'=>['Planned'], 'accent'=>'#2F4A5A'],
    ['icon'=>'🎬', 'title'=>'Documentary clips', 'desc'=>'Long-form series and thematic compilations.', 'pills'=>['Planned'], 'accent'=>'#2F3A4A'],
    ['icon'=>'📍', 'title'=>'Field notes', 'desc'=>'On-location footage and cultural context.', 'pills'=>['Planned'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

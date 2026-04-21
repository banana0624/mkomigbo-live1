<?php
declare(strict_types=1);

$pf = [
  'slug'   => 'newsletter',
  'pretty' => 'Newsletter',
  'desc'   => 'Periodic updates: research highlights, new pages, and curated links.',
  'lede'   => 'Research highlights, new pages, and curated links — delivered periodically.',
  'cards'  => [
    ['icon'=>'📨', 'title'=>'Highlights', 'desc'=>'New pages and major updates.', 'pills'=>['Planned'], 'accent'=>'#2F4A5A'],
    ['icon'=>'🔗', 'title'=>'Curated links', 'desc'=>'Sources, references, and recommended reading.', 'pills'=>['Planned'], 'accent'=>'#2F3A4A'],
    ['icon'=>'🧠', 'title'=>'Research notes', 'desc'=>'What’s being worked on next.', 'pills'=>['Planned'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

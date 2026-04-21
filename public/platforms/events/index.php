<?php
declare(strict_types=1);

$pf = [
  'slug'   => 'events',
  'pretty' => 'Events',
  'desc'   => 'Talks, lectures, and community sessions tied to Subjects and contributor research.',
  'lede'   => 'Lectures, talks, and community sessions — aligned to Subjects.',
  'cards'  => [
    ['icon'=>'📅', 'title'=>'Talks', 'desc'=>'Live and recorded sessions with references.', 'pills'=>['Planned'], 'accent'=>'#2F4A5A'],
    ['icon'=>'🏫', 'title'=>'Workshops', 'desc'=>'Skill-building and research sessions.', 'pills'=>['Planned'], 'accent'=>'#2F3A4A'],
    ['icon'=>'🤝', 'title'=>'Community sessions', 'desc'=>'Contributor-led discussions and learning circles.', 'pills'=>['Planned'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

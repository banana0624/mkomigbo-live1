<?php
declare(strict_types=1);

$pf = [
  'slug'   => 'forum',
  'pretty' => 'Forum',
  'desc'   => 'Moderated discussions, Q&A, and topic threads aligned to Subjects.',
  'lede'   => 'Moderated discussions and Q&A tied to Subjects — with citations encouraged.',
  'cards'  => [
    ['icon'=>'💬', 'title'=>'Threads', 'desc'=>'Topic-based discussions linked to pages.', 'pills'=>['Planned','Moderated'], 'accent'=>'#2F4A5A'],
    ['icon'=>'✅', 'title'=>'Verified answers', 'desc'=>'Best answers highlighted with sources.', 'pills'=>['Planned'], 'accent'=>'#2F3A4A'],
    ['icon'=>'🧰', 'title'=>'Contributor tools', 'desc'=>'Drafting, review, and references.', 'pills'=>['Planned'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

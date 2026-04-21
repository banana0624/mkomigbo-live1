<?php
declare(strict_types=1);

$pf = [
  'slug'   => 'blog',
  'pretty' => 'Blog',
  'desc'   => 'Research notes, editorials, and announcements linked to Subjects and sources.',
  'lede'   => 'Posts, research notes, and announcements — organized by Subject and evidence.',
  'cards'  => [
    ['icon'=>'✍', 'title'=>'Research notes', 'desc'=>'Short updates with references and attachments.', 'pills'=>['Planned'], 'accent'=>'#2F4A5A'],
    ['icon'=>'🧾', 'title'=>'Editorials', 'desc'=>'Curated takes grounded in sources.', 'pills'=>['Planned'], 'accent'=>'#2F3A4A'],
    ['icon'=>'📢', 'title'=>'Announcements', 'desc'=>'Platform updates and releases.', 'pills'=>['Planned'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

<?php
declare(strict_types=1);

/**
 * /public/platforms/posts/index.php
 * Platforms: Posts
 */

$pf = [
  'slug'   => 'posts',
  'pretty' => 'Posts',
  'desc'   => 'Short-form posts: updates, highlights, notes, and quick references tied to Subjects and sources.',
  'lede'   => 'Fast, focused posts — for announcements, key excerpts, and research highlights.',
  'cards'  => [
    ['icon'=>'📝', 'title'=>'Research notes', 'desc'=>'Quick findings and citations linked to Subject pages.', 'pills'=>['Planned'], 'accent'=>'#2F4A5A'],
    ['icon'=>'📌', 'title'=>'Highlights', 'desc'=>'Key takeaways, snippets, and references worth saving.', 'pills'=>['Planned'], 'accent'=>'#2F3A4A'],
    ['icon'=>'🔁', 'title'=>'Updates', 'desc'=>'Platform updates and editorial progress reports.', 'pills'=>['Planned'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

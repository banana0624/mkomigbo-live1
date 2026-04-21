<?php
declare(strict_types=1);

/**
 * /public/platforms/communities/index.php
 * Platforms: Communities
 */

$pf = [
  'slug'   => 'communities',
  'pretty' => 'Communities',
  'desc'   => 'Communities is coming soon: circles, collaboration, discussion spaces, and contributor-led learning.',
  'lede'   => 'A space for structured collaboration: circles, projects, discussions, and contributor-led learning.',
  'cards'  => [
    ['icon'=>'C',   'title'=>'Circles', 'desc'=>'Topic-based groups tied to Subjects and projects.', 'pills'=>['Planned','Structured'], 'accent'=>'#2F4A5A'],
    ['icon'=>'Q&A', 'title'=>'Questions and answers', 'desc'=>'Ask, cite sources, and build verified answers together.', 'pills'=>['Planned','Moderated'], 'accent'=>'#2F3A4A'],
    ['icon'=>'R',   'title'=>'Research threads', 'desc'=>'Long-running investigations with attachments and references.', 'pills'=>['Planned','Attachments'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

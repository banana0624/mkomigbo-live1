<?php
declare(strict_types=1);

/**
 * /public/platforms/threads/index.php
 * Platforms: Threads
 */

$pf = [
  'slug'   => 'threads',
  'pretty' => 'Threads',
  'desc'   => 'Structured discussion threads: investigations, Q&A, and long-running research with references.',
  'lede'   => 'Long-form threads for research: questions, evidence, citations, and collaborative summaries.',
  'cards'  => [
    ['icon'=>'🧵', 'title'=>'Investigations', 'desc'=>'Deep dives with sources, attachments, and updates.', 'pills'=>['Planned'], 'accent'=>'#2F4A5A'],
    ['icon'=>'❓', 'title'=>'Q&A threads', 'desc'=>'Questions answered with references and editorial review.', 'pills'=>['Planned'], 'accent'=>'#2F3A4A'],
    ['icon'=>'✅', 'title'=>'Verified summaries', 'desc'=>'Threads that end in clean, publishable conclusions.', 'pills'=>['Planned'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

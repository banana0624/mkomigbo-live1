<?php
declare(strict_types=1);

$pf = [
  'slug'   => 'library',
  'pretty' => 'Library',
  'desc'   => 'A curated library of books, papers, archives, and source collections linked to Subjects.',
  'lede'   => 'Curated sources — books, papers, archives — organized by Subject.',
  'cards'  => [
    ['icon'=>'📖', 'title'=>'Reading lists', 'desc'=>'Curated per Subject and theme.', 'pills'=>['Planned'], 'accent'=>'#2F4A5A'],
    ['icon'=>'📄', 'title'=>'Papers', 'desc'=>'Academic references and PDFs where permitted.', 'pills'=>['Planned'], 'accent'=>'#2F3A4A'],
    ['icon'=>'🗃', 'title'=>'Archives', 'desc'=>'Primary-source collections and links.', 'pills'=>['Planned'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

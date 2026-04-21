<?php
declare(strict_types=1);

$pf = [
  'slug'   => 'gallery',
  'pretty' => 'Gallery',
  'desc'   => 'Curated images, artefacts, maps, and historical visuals aligned to Subjects and timelines.',
  'lede'   => 'A curated visual archive: images, artefacts, maps, and timelines tied to Subjects.',
  'cards'  => [
    ['icon'=>'🖼', 'title'=>'Collections', 'desc'=>'Organized by Subjects, eras, and themes.', 'pills'=>['Planned'], 'accent'=>'#2F4A5A'],
    ['icon'=>'🗺', 'title'=>'Maps', 'desc'=>'Geography and migration context for research.', 'pills'=>['Planned'], 'accent'=>'#2F3A4A'],
    ['icon'=>'🏺', 'title'=>'Artefacts', 'desc'=>'Curated material culture with sources.', 'pills'=>['Planned'], 'accent'=>'#4A3F2F'],
  ],
];

require __DIR__ . '/../_platform_page.php';

<?php

function mk_get_homepage_blocks(): array
{
  /**
   * Later this comes from DB.
   * For now: structured JSON-like config.
   */

  return [
    [
      'type' => 'hero',
      'data' => [
        'title' => 'Mkomigbo Knowledge System',
        'subtitle' => 'Structured African knowledge platform'
      ]
    ],
    [
      'type' => 'stats',
      'data' => [
        'subjects' => 5
      ]
    ],
    [
      'type' => 'featured_subjects',
      'data' => [
        'items' => [
          ['slug' => 'history', 'name' => 'History'],
          ['slug' => 'culture', 'name' => 'Culture'],
          ['slug' => 'religion', 'name' => 'Religion'],
          ['slug' => 'africa', 'name' => 'Africa'],
          ['slug' => 'about', 'name' => 'About'],
        ]
      ]
    ]
  ];
}
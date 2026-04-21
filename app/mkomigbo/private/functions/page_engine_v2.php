<?php

function mk_render_page(array $page): void
{
  mk_require_shared('public_header.php');

  echo "<div class='mk-page'>";

  $blocks = [];

  if (!empty($page['blocks_json'])) {
    $decoded = json_decode($page['blocks_json'], true);
    if (is_array($decoded)) {
      $blocks = $decoded;
    }
  }

  if (!empty($blocks)) {

    foreach ($blocks as $block) {
      mk_render_block(
        $block['type'] ?? '',
        $block['data'] ?? []
      );
    }

  } else {

    echo $page['body'] ?? '';

  }

  echo "</div>";

  mk_require_shared('public_footer.php');
}
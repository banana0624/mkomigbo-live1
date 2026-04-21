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

  if ($blocks) {

    foreach ($blocks as $block) {
      echo "<pre>";
      print_r($block); // TEMP (we upgrade later)
      echo "</pre>";
    }

  } else {

    echo $page['body'] ?? '';

  }

  echo "</div>";

  mk_require_shared('public_footer.php');
}
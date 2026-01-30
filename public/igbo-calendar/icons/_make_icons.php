<?php
declare(strict_types=1);

@ini_set('display_errors', '1');
error_reporting(E_ALL);

if (!extension_loaded('gd')) {
  echo "GD extension is not available. Cannot generate PNG icons.\n";
  exit(1);
}

$dir = __DIR__;
@mkdir($dir, 0755, true);

function makeIcon(string $path, int $size): void {
  $im = imagecreatetruecolor($size, $size);
  imagesavealpha($im, true);
  $bg = imagecolorallocate($im, 17, 24, 39);   // #111827
  imagefill($im, 0, 0, $bg);

  // Accent block
  $accent = imagecolorallocate($im, 191, 146, 60);
  imagefilledrectangle($im, (int)($size*0.10), (int)($size*0.10), (int)($size*0.90), (int)($size*0.90), $accent);

  // Text (simple)
  $white = imagecolorallocate($im, 255, 255, 255);
  imagestring($im, 5, (int)($size*0.34), (int)($size*0.42), "IG", $white);

  imagepng($im, $path);
  imagedestroy($im);
}

$sizes = [48,72,192,512];
foreach ($sizes as $s) {
  $p = $dir . "/icon-{$s}.png";
  makeIcon($p, $s);
  echo "Wrote {$p}\n";
}

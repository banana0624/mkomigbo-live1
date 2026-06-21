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
  if (!$im) {
    throw new RuntimeException("Could not create image: {$path}");
  }

  imagealphablending($im, false);
  imagesavealpha($im, true);

  $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
  imagefilledrectangle($im, 0, 0, $size, $size, $transparent);

  $bg      = imagecolorallocate($im, 11, 11, 11);     // #0b0b0b
  $outline = imagecolorallocate($im, 200, 183, 140);  // #c8b78c
  $body    = imagecolorallocate($im, 239, 230, 207);  // #efe6cf
  $ridge   = imagecolorallocate($im, 220, 201, 161);  // #dcc9a1
  $slit    = imagecolorallocate($im, 122, 82, 48);    // #7a5230
  $slit2   = imagecolorallocate($im, 247, 237, 216);  // #f7edd8
  $hi1     = imagecolorallocatealpha($im, 255, 248, 234, 36);
  $hi2     = imagecolorallocatealpha($im, 255, 244, 222, 68);

  $r = max(2, (int) round($size * 0.22));
  imagefilledroundedrectangle($im, 0, 0, $size - 1, $size - 1, $r, $bg);

  $cell = (int) round($size * 0.72);
  $x = (int) round(($size - $cell) / 2);
  $y = (int) round(($size - $cell) / 2);

  drawCowrie($im, $x, $y, $cell, $outline, $body, $ridge, $slit, $slit2, $hi1, $hi2);

  if (!imagepng($im, $path, 6)) {
    imagedestroy($im);
    throw new RuntimeException("Could not write PNG: {$path}");
  }

  imagedestroy($im);
}

function imagefilledroundedrectangle($im, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void {
  imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $color);
  imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $color);

  imagefilledellipse($im, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
  imagefilledellipse($im, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
  imagefilledellipse($im, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
  imagefilledellipse($im, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
}

function drawCowrie($im, int $x, int $y, int $cell, int $outline, int $body, int $ridge, int $slit, int $slit2, int $hi1, int $hi2): void {
  $cx = $x + (int) round($cell * 0.50);
  $cy = $y + (int) round($cell * 0.52);

  $wOuter = max(10, (int) round($cell * 0.54));
  $hOuter = max(16, (int) round($cell * 0.82));

  $wInner = max(6, (int) round($cell * 0.34));
  $hInner = max(10, (int) round($cell * 0.58));

  imagefilledellipse($im, $cx, $cy, $wOuter, $hOuter, $outline);
  imagefilledellipse($im, $cx, $cy, max(2, $wOuter - 4), max(2, $hOuter - 4), $body);
  imagefilledellipse($im, $cx, $cy + (int) round($cell * 0.01), $wInner, $hInner, $ridge);

  $slitW = max(2, (int) round($cell * 0.15));
  $slitH = max(4, (int) round($cell * 0.40));
  imagefilledellipse($im, $cx, $cy + (int) round($cell * 0.05), $slitW, $slitH, $slit);
  imagefilledellipse($im, $cx, $cy + (int) round($cell * 0.01), max(1, $slitW - 2), max(1, (int) round($slitH * 0.24)), $slit2);

  imagefilledellipse(
    $im,
    $cx - (int) round($cell * 0.09),
    $cy - (int) round($cell * 0.16),
    max(2, (int) round($cell * 0.12)),
    max(2, (int) round($cell * 0.20)),
    $hi1
  );

  imagefilledellipse(
    $im,
    $cx + (int) round($cell * 0.09),
    $cy - (int) round($cell * 0.20),
    max(2, (int) round($cell * 0.08)),
    max(2, (int) round($cell * 0.12)),
    $hi2
  );
}

function makeIcoFromPng(string $pngPath, string $icoPath): void {
  $pngData = @file_get_contents($pngPath);
  if ($pngData === false) {
    throw new RuntimeException("Could not read PNG for ICO: {$pngPath}");
  }

  $size = getimagesize($pngPath);
  if (!$size || empty($size[0]) || empty($size[1])) {
    throw new RuntimeException("Could not inspect PNG for ICO: {$pngPath}");
  }

  $width  = (int) $size[0];
  $height = (int) $size[1];

  $ico  = pack('vvv', 0, 1, 1);
  $ico .= pack(
    'CCCCvvVV',
    $width >= 256 ? 0 : $width,
    $height >= 256 ? 0 : $height,
    0,
    0,
    1,
    32,
    strlen($pngData),
    22
  );
  $ico .= $pngData;

  if (@file_put_contents($icoPath, $ico) === false) {
    throw new RuntimeException("Could not write ICO: {$icoPath}");
  }
}

$sizes = [16, 32, 48, 72, 192, 512];

foreach ($sizes as $s) {
  $p = $dir . "/icon-{$s}.png";
  makeIcon($p, $s);
  echo "Wrote {$p}\n";
}

makeIcoFromPng($dir . '/icon-32.png', $dir . '/favicon.ico');
echo "Wrote {$dir}/favicon.ico\n";

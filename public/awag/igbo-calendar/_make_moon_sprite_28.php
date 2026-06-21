<?php
declare(strict_types=1);

/**
 * Generates /public/igbo-calendar/moon-sprite-28.png
 * 28 frames, 24x24 each, horizontal sprite (672x24).
 *
 * Requires PHP GD enabled.
 */

$tile = 24;
$frames = 28;
$w = $tile * $frames;
$h = $tile;

$im = imagecreatetruecolor($w, $h);
imagesavealpha($im, true);
$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
imagefill($im, 0, 0, $transparent);

function clamp01(float $x): float { return max(0.0, min(1.0, $x)); }

// Draw a simple moon phase: illuminated fraction based on cosine curve.
// This is a visual sprite, not an astronomical ephemeris.
for ($i = 0; $i < $frames; $i++) {
  $x0 = $i * $tile;
  $cx = $x0 + (int)($tile / 2);
  $cy = (int)($h / 2);
  $r  = (int)($tile * 0.42);

  // phase fraction 0..1
  $frac = ($i + 0.5) / $frames; // avoid exact endpoints
  // illumination 0..1
  $illum = (1.0 - cos(2.0 * M_PI * $frac)) / 2.0;
  $illum = clamp01($illum);

  // base colors
  $dark  = imagecolorallocatealpha($im, 20, 20, 24, 0);
  $light = imagecolorallocatealpha($im, 235, 235, 245, 0);
  $rim   = imagecolorallocatealpha($im, 255, 255, 255, 80);

  // draw full dark disc
  imagefilledellipse($im, $cx, $cy, $r*2, $r*2, $dark);

  // Determine waxing/waning by frac: 0..0.5 waxing, 0.5..1 waning
  $waxing = ($frac <= 0.5);

  // Width of lit portion in pixels (0..2r)
  $litW = (int)round($illum * ($r * 2));

  // Draw lit portion as an ellipse clipped by a rectangle:
  // We fake the terminator by shifting a second ellipse.
  // This produces a clean, readable sprite for UI.
  $tmp = imagecreatetruecolor($tile, $tile);
  imagesavealpha($tmp, true);
  $t = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
  imagefill($tmp, 0, 0, $t);

  // draw light disc
  imagefilledellipse($tmp, (int)($tile/2), (int)($tile/2), $r*2, $r*2, $light);

  // carve terminator by overlaying dark ellipse shifted left/right
  $shift = (int)round(($r * 2 - $litW) / 2);
  $dx = $waxing ? -$shift : $shift;

  imagefilledellipse(
    $tmp,
    (int)($tile/2) + $dx,
    (int)($tile/2),
    $r*2,
    $r*2,
    $t
  );

  // copy tile into sprite
  imagecopy($im, $tmp, $x0, 0, 0, 0, $tile, $tile);
  imagedestroy($tmp);

  // subtle rim (helps on dark backgrounds)
  imageellipse($im, $cx, $cy, $r*2, $r*2, $rim);
}

$out = __DIR__ . '/moon-sprite-28.png';
imagepng($im, $out);
imagedestroy($im);

header('Content-Type: text/plain; charset=UTF-8');
echo "WROTE: {$out}\n";

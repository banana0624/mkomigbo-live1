<?php
declare(strict_types=1);
/**
 * /awag/engine/skin.php — Universal AWAG skin renderer
 * Usage: set $skin_id then require this file
 */
if (!isset($skin_id)) $skin_id = 'igbo';
$data_file = __DIR__ . '/../data/' . preg_replace('/[^a-z0-9_-]/', '', $skin_id) . '.json';
if (!is_file($data_file)) { http_response_code(404); echo 'Skin not found'; exit; }
$skin = json_decode(file_get_contents($data_file), true);
if (!$skin) { http_response_code(500); echo 'Invalid skin data'; exit; }
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
$color   = $skin['color'] ?? '#2d6a1f';
$month   = max(1, min(13, (int)($_GET['m'] ?? 1)));
$modules = [
    ['key'=>'rulers','title'=>'Traditional Rulers','fields'=>[['title','Festival'],['rulers','Who presides'],['events','Events']]],
    ['key'=>'farming','title'=>'Farming',  'fields'=>[['activity','Guide'],['crops','Crops']]],
    ['key'=>'fishing','title'=>'Fishing',  'fields'=>[['conditions','Conditions'],['activity','Guide'],['fish','Target fish']]],
    ['key'=>'trading','title'=>'Trading',  'fields'=>[['activity','Market guide'],['goods','Key goods']]],
    ['key'=>'herding','title'=>'Herding',  'fields'=>[['conditions','Conditions'],['activity','Guide']]],
    ['key'=>'healing','title'=>'Healing',  'fields'=>[['activity','Guide'],['herbs','Herbs'],['rituals','Ritual']]],
    ['key'=>'weather','title'=>'Weather',  'fields'=>[['season','Season'],['rains','Rainfall'],['wind','Wind'],['advisory','Advisory']]],
];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($skin['full_name'] ?? $skin['name']) ?> — AWAG</title>
<meta name="theme-color" content="<?= h($color) ?>">
<link rel="manifest" href="/awag/manifest.json">
<script>if("serviceWorker" in navigator){navigator.serviceWorker.register("/awag/sw.js",{scope:"/awag/"}).catch(function(e){console.warn("AWAG SW:",e)});}</script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{min-height:100vh;font-family:system-ui,-apple-system,sans-serif;background:#0a1a0a;color:#e8f0e8}
a{color:inherit;text-decoration:none}
.wrap{max-width:1100px;margin:0 auto;padding:0 16px}
.site-header{background:#0f1f0f;border-bottom:1px solid rgba(255,255,255,.08);position:sticky;top:0;z-index:20}
.site-header .wrap{display:flex;align-items:center;justify-content:space-between;min-height:56px;gap:12px;flex-wrap:wrap}
.brand{display:inline-flex;align-items:center;gap:10px}
.brand__mark{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.9rem;color:#fff}
.brand__name{font-weight:900;font-size:1rem}
.brand__sub{font-size:.75rem;color:rgba(232,240,232,.5)}
.nav-links{display:flex;gap:8px}
.nav-links a{padding:6px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.12);font-size:.82rem;color:rgba(232,240,232,.8);font-weight:600}
.hero{padding:20px 0 14px;border-bottom:1px solid rgba(255,255,255,.06)}
.hero h1{font-size:clamp(1.3rem,3vw,1.8rem);font-weight:900;letter-spacing:-.02em}
.hero p{margin:6px 0 0;color:rgba(232,240,232,.55);font-size:.88rem;line-height:1.6}
.market-week{margin:14px 0;padding:14px;background:rgba(255,255,255,.03);border-radius:14px;border:1px solid rgba(255,255,255,.08)}
.market-week__label{font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(232,240,232,.4);margin-bottom:10px}
.market-week__days{display:flex;gap:10px;flex-wrap:wrap}
.market-day{display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:12px;border-width:2px;border-style:solid;background:rgba(0,0,0,.25);min-width:90px}
.market-day__icon{font-size:1.5rem;line-height:1}
.market-day__name{font-weight:900;font-size:.95rem}
.market-day__el{font-size:.72rem;color:rgba(232,240,232,.45);margin-top:2px}
.month-nav{display:flex;gap:6px;align-items:center;flex-wrap:wrap;padding:12px 0}
.month-nav__label{font-size:.78rem;font-weight:700;color:rgba(232,240,232,.4);margin-right:4px}
.month-btn{padding:5px 10px;border-radius:8px;border:1px solid rgba(255,255,255,.10);font-size:.8rem;color:rgba(232,240,232,.65);text-decoration:none;display:inline-block}
.month-btn.active{font-weight:800;color:#fff;border-color:var(--c)}
.month-info{margin-bottom:14px;padding:12px 16px;background:rgba(255,255,255,.04);border-radius:12px;border:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.month-info__name{font-weight:800;font-size:1.05rem}
.month-info__greg{color:rgba(232,240,232,.5);font-size:.88rem}
.month-info__note{padding:2px 8px;border-radius:6px;background:rgba(255,255,255,.06);font-size:.8rem;color:rgba(232,240,232,.6)}
.modules{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;padding-bottom:48px}
.mod{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.10);border-radius:16px;overflow:hidden;transition:border-color .12s,transform .12s}
.mod:hover{border-color:rgba(255,255,255,.22);transform:translateY(-1px)}
.mod__head{display:flex;align-items:center;gap:10px;padding:12px 14px;background:rgba(255,255,255,.03);border-bottom:1px solid rgba(255,255,255,.07)}
.mod__icon{font-size:1.2rem;line-height:1}
.mod__title{font-size:.92rem;font-weight:800}
.mod__body{padding:12px 14px;display:flex;flex-direction:column;gap:8px}
.field__label{font-size:.7rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:rgba(232,240,232,.38);display:block}
.field__val{font-size:.86rem;color:rgba(232,240,232,.82);line-height:1.5}
.mod:nth-child(1) .mod__head{border-left:3px solid #50c850;color:#50c850}
.mod:nth-child(2) .mod__head{border-left:3px solid #50a8ff;color:#50a8ff}
.mod:nth-child(3) .mod__head{border-left:3px solid #ffcc50;color:#ffcc50}
.mod:nth-child(4) .mod__head{border-left:3px solid #ff8c50;color:#ff8c50}
.mod:nth-child(5) .mod__head{border-left:3px solid #b050ff;color:#b050ff}
.mod:nth-child(6) .mod__head{border-left:3px solid #50e0e0;color:#50e0e0}
.site-footer{padding:20px 0;border-top:1px solid rgba(255,255,255,.07);text-align:center;color:rgba(232,240,232,.35);font-size:.82rem}
.site-footer a{color:rgba(232,240,232,.45)}
@media(max-width:600px){.modules{grid-template-columns:1fr}}
</style>
</head>
<body style="--c:<?= h($color) ?>">
<header class="site-header">
  <div class="wrap">
    <div class="brand">
      <div class="brand__mark" style="background:<?= h($color) ?>"><?= h($skin['initial'] ?? 'AW') ?></div>
      <div>
        <div class="brand__name"><?= h($skin['full_name'] ?? $skin['name']) ?></div>
        <div class="brand__sub"><?= h($skin['area'] ?? $skin['region'] ?? '') ?></div>
      </div>
    </div>
    <nav class="nav-links">
      <a href="/awag/">&#8592; AWAG</a>
      <a href="/">Mkomigbo</a>
    </nav>
  </div>
</header>

<main>
<div class="wrap">

  <div class="hero">
    <h1><?= h($skin['full_name'] ?? $skin['name']) ?></h1>
    <p>
      <?= h($skin['area'] ?? '') ?>
      &nbsp;&middot;&nbsp;<?= h($skin['language'] ?? '') ?>
      &nbsp;&middot;&nbsp;<?= h($skin['calendar_type'] ?? '') ?> calendar
      <?php if (!empty($skin['market_cycle'])): ?>
      &nbsp;&middot;&nbsp;<?= (int)$skin['market_cycle'] ?>-day market week
      <?php endif; ?>
    </p>
  </div>

  <?php if (!empty($skin['market_days']) && !empty($skin['market_symbols'])): ?>
  <div class="market-week">
    <div class="market-week__label"><?= (int)$skin['market_cycle'] ?>-Day Market Week</div>
    <div class="market-week__days">
      <?php foreach ($skin['market_days'] as $day):
        $sym = $skin['market_symbols'][$day] ?? ['symbol'=>'📅','color'=>'#888888','element'=>''];
      ?>
      <div class="market-day" style="border-color:<?= h($sym['color']) ?>;color:<?= h($sym['color']) ?>">
        <span class="market-day__icon"><?= h($sym['symbol']) ?></span>
        <div>
          <div class="market-day__name"><?= h($day) ?></div>
          <div class="market-day__el"><?= h($sym['element']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="month-nav">
    <span class="month-nav__label">Month:</span>
    <?php foreach (($skin['months'] ?? []) as $mo): ?>
    <a href="?m=<?= (int)$mo['n'] ?>"
       class="month-btn<?= $mo['n'] == $month ? ' active' : '' ?>"
       style="<?= $mo['n'] == $month ? 'background:' . h($color) . ';border-color:' . h($color) . ';' : '' ?>">
      <?= (int)$mo['n'] ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php
  $mn = null;
  foreach (($skin['months'] ?? []) as $mo) {
    if ((int)$mo['n'] === $month) { $mn = $mo; break; }
  }
  if ($mn): ?>
  <div class="month-info">
    <span class="month-info__name"><?= h($mn['name'] ?? '') ?></span>
    <span class="month-info__greg"><?= h($mn['greg'] ?? '') ?></span>
    <?php if (!empty($mn['note'])): ?>
    <span class="month-info__note"><?= h($mn['note']) ?></span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="modules">
  <?php foreach ($modules as $mod):
    $entries = $skin[$mod['key']] ?? [];
    $entry = null;
    foreach ($entries as $e) {
      if ((int)($e['month'] ?? 0) === $month) { $entry = $e; break; }
    }
    if (!$entry) continue;
  ?>
  <div class="mod">
    <div class="mod__head">
      <span class="mod__icon"><?= h($entry['icon'] ?? '📋') ?></span>
      <span class="mod__title"><?= h($mod['title']) ?></span>
    </div>
    <div class="mod__body">
      <?php foreach ($mod['fields'] as [$fkey, $flabel]):
        if (empty($entry[$fkey])) continue;
        $val = is_array($entry[$fkey]) ? implode(', ', $entry[$fkey]) : (string)$entry[$fkey];
      ?>
      <div>
        <span class="field__label"><?= h($flabel) ?></span>
        <span class="field__val"><?= h($val) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
  </div>

</div>
<div class="wrap" style="padding-bottom:0"><div style="margin:24px 0 12px;font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(232,240,232,.4);">Nature &amp; Environment — Live Data</div><div class="modules" id="awag-extended-mount"><p style="color:rgba(232,240,232,.4);padding:12px 0;">Calculating...</p></div></div><span data-awag-month="<?= $month ?>" style="display:none"></span><script src="/awag/engine/awag-nature-tabs.js"></script></main>

<footer class="site-footer">
  <div class="wrap">
    <?= h($skin['full_name'] ?? $skin['name']) ?> &middot; AWAG &mdash; Africa Weekly Activities Guide &middot;
    <a href="/awag/">All Communities</a> &middot; <a href="/">Mkomigbo</a>
  </div>
</footer>
<script src="/awag/engine/awag-feedback.js"></script>
<script src="/awag/engine/awag-igbo-calendar.js"></script>
<script src="/awag/engine/awag-ijaw-calendar.js"></script>
<script src="/awag/engine/awag-tiv-calendar.js"></script>
<script src="/awag/engine/awag-bini-calendar.js"></script>
</body>
</html>
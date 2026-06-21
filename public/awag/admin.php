<?php
declare(strict_types=1);
/**
 * /awag/admin.php — AWAG Feedback Admin Panel
 * Password protected. Review community corrections and new community requests.
 */

// ── Password protection ──────────────────────────────────────────
define('ADMIN_PASS', 'Amuzi_AWAG_2026');
session_start();
if (!isset($_SESSION['awag_admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['pass'] ?? '') === ADMIN_PASS) {
        $_SESSION['awag_admin'] = true;
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $err = 'Wrong password.';
        ?><!doctype html><html lang="en"><head><meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>AWAG Admin</title>
        <style>*{box-sizing:border-box;margin:0;padding:0}body{background:#0e0804;color:#e8e0d0;font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}.box{background:#1a0e04;border:1px solid rgba(245,217,122,.2);border-radius:16px;padding:32px;max-width:360px;width:100%}h2{color:#f5d97a;font-size:1.2rem;margin-bottom:6px}p{font-size:.82rem;color:rgba(232,224,208,.5);margin-bottom:20px}input{width:100%;padding:10px 12px;background:rgba(0,0,0,.4);border:1px solid rgba(245,217,122,.2);border-radius:9px;color:#e8e0d0;font-size:.9rem;margin-bottom:12px}button{width:100%;padding:10px;background:#2d6a1f;color:#fff;border:none;border-radius:9px;font-size:.9rem;font-weight:700;cursor:pointer}.err{color:#ff6060;font-size:.82rem;margin-top:8px}</style>
        </head><body><div class="box">
        <h2>🌍 AWAG Admin</h2><p>Enter password to access feedback dashboard</p>
        <form method="post"><input type="password" name="pass" placeholder="Password" autofocus>
        <button type="submit">Login</button></form>
        <?php if(isset($err)) echo '<div class="err">'.$err.'</div>'; ?>
        </div></body></html><?php
        exit;
    }
}

if (isset($_GET['logout'])) { session_destroy(); header('Location: /awag/admin.php'); exit; }

// ── Load feedback files ──────────────────────────────────────────
$fb_dir = __DIR__ . '/feedback/';
$entries = [];
if (is_dir($fb_dir)) {
    foreach (glob($fb_dir . '*.json') as $file) {
        if (strpos(basename($file), 'rate_') === 0) continue;
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) {
            foreach ($data as $e) {
                $e['_file'] = basename($file);
                $entries[] = $e;
            }
        }
    }
}

// Sort newest first
usort($entries, fn($a,$b) => strcmp($b['timestamp']??'',$a['timestamp']??''));

// Handle mark-reviewed action
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['mark_id'])) {
    $id = $_POST['mark_id'];
    $status = $_POST['new_status'] ?? 'reviewed';
    foreach (glob($fb_dir.'*.json') as $file) {
        if (strpos(basename($file),'rate_')===0) continue;
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) continue;
        $changed = false;
        foreach ($data as &$e) {
            if (($e['id']??'') === $id) { $e['status'] = $status; $changed = true; }
        }
        if ($changed) { file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); }
    }
    header('Location: /awag/admin.php?updated=1');
    exit;
}

// ── Stats ────────────────────────────────────────────────────────
$total      = count($entries);
$pending    = count(array_filter($entries, fn($e) => ($e['status']??'pending')==='pending'));
$reviewed   = count(array_filter($entries, fn($e) => ($e['status']??'')==='reviewed'));
$applied    = count(array_filter($entries, fn($e) => ($e['status']??'')==='applied'));
$corrections  = count(array_filter($entries, fn($e) => ($e['type']??'')==='correction'));
$new_comms  = count(array_filter($entries, fn($e) => ($e['type']??'')==='new_community'));
$general    = count(array_filter($entries, fn($e) => ($e['type']??'')==='general'));

// Filter
$filter_type   = $_GET['type']   ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$filter_skin   = $_GET['skin']   ?? 'all';

$filtered = array_filter($entries, function($e) use ($filter_type,$filter_status,$filter_skin) {
    if ($filter_type!=='all'   && ($e['type']??'')!==$filter_type)     return false;
    if ($filter_status!=='all' && ($e['status']??'pending')!==$filter_status) return false;
    if ($filter_skin!=='all'   && ($e['skin']??'')!==$filter_skin)     return false;
    return true;
});

function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function badge(string $status):string {
    $colors=['pending'=>'#e0a820','reviewed'=>'#4090e0','applied'=>'#40c860'];
    $c=$colors[$status]??'#808080';
    return '<span style="padding:2px 8px;border-radius:20px;font-size:.7rem;font-weight:700;background:'.h($c).'22;color:'.h($c).';border:1px solid '.h($c).'44">'.h($status).'</span>';
}
function typebadge(string $type):string {
    $map=['correction'=>['✏️','#e07820'],'new_community'=>['➕','#20c860'],'general'=>['💬','#4090e0']];
    $d=$map[$type]??['?','#808080'];
    return '<span style="padding:2px 8px;border-radius:20px;font-size:.7rem;font-weight:700;background:'.$d[1].'22;color:'.$d[1].';border:1px solid '.$d[1].'44">'.$d[0].' '.h($type).'</span>';
}
$skins_used = array_unique(array_filter(array_column($entries,'skin')));
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AWAG Admin — Feedback</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{min-height:100vh;font-family:system-ui,-apple-system,sans-serif;background:#0a0604;color:#e8e0d0;font-size:15px}
a{color:#f5d97a;text-decoration:none}
.hdr{background:#120a04;border-bottom:1px solid rgba(245,217,122,.12);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;position:sticky;top:0;z-index:10}
.hdr__brand{font-weight:900;font-size:1.05rem;color:#f5d97a;letter-spacing:2px}
.hdr__sub{font-size:.72rem;color:rgba(245,217,122,.4);letter-spacing:1px}
.hdr__nav{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.hdr__nav a{font-size:.82rem;color:rgba(232,224,208,.55);padding:5px 12px;border:1px solid rgba(245,217,122,.15);border-radius:8px}
.hdr__nav a:hover{color:#f5d97a}
.wrap{max-width:1200px;margin:0 auto;padding:20px 16px}
.stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:24px}
.stat{background:rgba(245,217,122,.04);border:1px solid rgba(245,217,122,.10);border-radius:12px;padding:14px;text-align:center}
.stat__n{font-size:1.8rem;font-weight:900;color:#f5d97a;display:block}
.stat__l{font-size:.72rem;color:rgba(232,224,208,.45);letter-spacing:.05em;text-transform:uppercase}
.filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;padding:14px;background:rgba(245,217,122,.03);border:1px solid rgba(245,217,122,.08);border-radius:12px}
.filters select{background:rgba(0,0,0,.4);border:1px solid rgba(245,217,122,.15);border-radius:8px;color:#e8e0d0;padding:6px 10px;font-size:.84rem;font-family:inherit}
.filters label{font-size:.75rem;color:rgba(232,224,208,.45);align-self:center}
.notice{padding:10px 14px;background:rgba(64,200,96,.08);border:1px solid rgba(64,200,96,.2);border-radius:8px;color:#40c860;font-size:.84rem;margin-bottom:16px}
.empty{text-align:center;padding:48px;color:rgba(232,224,208,.35)}
.empty__icon{font-size:3rem;margin-bottom:12px}
.card{background:rgba(245,217,122,.03);border:1px solid rgba(245,217,122,.09);border-radius:14px;margin-bottom:12px;overflow:hidden}
.card__head{padding:14px 16px;display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap;border-bottom:1px solid rgba(245,217,122,.07)}
.card__meta{flex:1;display:flex;flex-direction:column;gap:5px}
.card__badges{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.card__time{font-size:.72rem;color:rgba(232,224,208,.35)}
.card__skin{font-size:.78rem;color:rgba(245,217,122,.55);font-weight:700}
.card__body{padding:14px 16px;display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:600px){.card__body{grid-template-columns:1fr}}
.field{display:flex;flex-direction:column;gap:3px}
.field__label{font-size:.68rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:rgba(245,217,122,.32)}
.field__val{font-size:.86rem;color:rgba(232,224,208,.85);line-height:1.5;white-space:pre-wrap}
.field--full{grid-column:1/-1}
.card__actions{padding:12px 16px;border-top:1px solid rgba(245,217,122,.07);display:flex;gap:8px;flex-wrap:wrap}
.btn{padding:6px 14px;border-radius:8px;font-size:.8rem;font-weight:700;cursor:pointer;border:none;font-family:inherit}
.btn--review{background:rgba(64,144,224,.15);color:#4090e0;border:1px solid rgba(64,144,224,.3)}
.btn--apply{background:rgba(64,200,96,.15);color:#40c860;border:1px solid rgba(64,200,96,.3)}
.btn--pending{background:rgba(224,168,32,.15);color:#e0a820;border:1px solid rgba(224,168,32,.3)}
</style>
</head>
<body>
<header class="hdr">
  <div>
    <div class="hdr__brand">AWAG Admin</div>
    <div class="hdr__sub">Feedback Dashboard</div>
  </div>
  <nav class="hdr__nav">
    <a href="/awag/">← AWAG</a>
    <a href="/awag/admin.php?logout=1">Logout</a>
  </nav>
</header>

<div class="wrap">

<?php if(isset($_GET['updated'])): ?>
<div class="notice">✅ Status updated successfully.</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats">
  <div class="stat"><span class="stat__n"><?= $total ?></span><span class="stat__l">Total</span></div>
  <div class="stat"><span class="stat__n" style="color:#e0a820"><?= $pending ?></span><span class="stat__l">Pending</span></div>
  <div class="stat"><span class="stat__n" style="color:#4090e0"><?= $reviewed ?></span><span class="stat__l">Reviewed</span></div>
  <div class="stat"><span class="stat__n" style="color:#40c860"><?= $applied ?></span><span class="stat__l">Applied</span></div>
  <div class="stat"><span class="stat__n"><?= $corrections ?></span><span class="stat__l">Corrections</span></div>
  <div class="stat"><span class="stat__n"><?= $new_comms ?></span><span class="stat__l">New Communities</span></div>
  <div class="stat"><span class="stat__n"><?= $general ?></span><span class="stat__l">General</span></div>
</div>

<!-- Filters -->
<form class="filters" method="get">
  <label>Type:</label>
  <select name="type" onchange="this.form.submit()">
    <option value="all" <?= $filter_type==='all'?'selected':'' ?>>All types</option>
    <option value="correction" <?= $filter_type==='correction'?'selected':'' ?>>Corrections</option>
    <option value="new_community" <?= $filter_type==='new_community'?'selected':'' ?>>New communities</option>
    <option value="general" <?= $filter_type==='general'?'selected':'' ?>>General</option>
  </select>
  <label>Status:</label>
  <select name="status" onchange="this.form.submit()">
    <option value="all" <?= $filter_status==='all'?'selected':'' ?>>All statuses</option>
    <option value="pending" <?= $filter_status==='pending'?'selected':'' ?>>Pending</option>
    <option value="reviewed" <?= $filter_status==='reviewed'?'selected':'' ?>>Reviewed</option>
    <option value="applied" <?= $filter_status==='applied'?'selected':'' ?>>Applied</option>
  </select>
  <label>Skin:</label>
  <select name="skin" onchange="this.form.submit()">
    <option value="all">All skins</option>
    <?php foreach($skins_used as $sk): ?>
    <option value="<?= h($sk) ?>" <?= $filter_skin===$sk?'selected':'' ?>><?= h($sk) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<!-- Entries -->
<?php if(empty($filtered)): ?>
<div class="empty">
  <div class="empty__icon">📭</div>
  <div>No feedback submissions yet<?= $filter_type!=='all'||$filter_status!=='all'?' matching these filters':'' ?>.</div>
  <?php if($total===0): ?><div style="margin-top:8px;font-size:.82rem">Share AWAG with your community — feedback will appear here once visitors start submitting corrections.</div><?php endif; ?>
</div>
<?php else: ?>
<?php foreach($filtered as $e):
  $type    = $e['type']    ?? 'general';
  $status  = $e['status']  ?? 'pending';
  $skin    = $e['skin']    ?? '';
  $module  = $e['module']  ?? '';
  $current = $e['current'] ?? '';
  $correct = $e['correct'] ?? '';
  $notes   = $e['notes']   ?? '';
  $name    = $e['name']    ?? '';
  $email   = $e['email']   ?? '';
  $source  = $e['source']  ?? '';
  $community = $e['community'] ?? '';
  $region  = $e['region']  ?? '';
  $area    = $e['area']    ?? '';
  $language = $e['language'] ?? '';
  $ts      = $e['timestamp'] ?? '';
  $month   = $e['month']   ?? '';
  $id      = $e['id']      ?? '';
?>
<div class="card">
  <div class="card__head">
    <div class="card__meta">
      <div class="card__badges">
        <?= typebadge($type) ?>
        <?= badge($status) ?>
        <?php if($skin): ?><span class="card__skin">🌍 <?= h(strtoupper($skin)) ?><?= $month?' · Month '.$month:'' ?></span><?php endif; ?>
        <?php if($module): ?><span style="font-size:.75rem;color:rgba(232,224,208,.4)"><?= h($module) ?></span><?php endif; ?>
      </div>
      <div class="card__time"><?= h($ts) ?><?= $name?' · '.h($name):'' ?><?= $email?' · '.h($email):'' ?></div>
    </div>
  </div>
  <div class="card__body">
    <?php if($type==='correction'): ?>
      <?php if($current): ?><div class="field"><div class="field__label">Currently shows (wrong)</div><div class="field__val" style="color:rgba(255,100,100,.8)"><?= h($current) ?></div></div><?php endif; ?>
      <?php if($correct): ?><div class="field"><div class="field__label">Should say (correct)</div><div class="field__val" style="color:rgba(100,220,100,.9)"><?= h($correct) ?></div></div><?php endif; ?>
      <?php if($source): ?><div class="field field--full"><div class="field__label">Source / Reason</div><div class="field__val"><?= h($source) ?></div></div><?php endif; ?>
    <?php elseif($type==='new_community'): ?>
      <?php if($community): ?><div class="field"><div class="field__label">Community</div><div class="field__val" style="color:#f5d97a;font-weight:700"><?= h($community) ?></div></div><?php endif; ?>
      <?php if($region): ?><div class="field"><div class="field__label">Region</div><div class="field__val"><?= h($region) ?></div></div><?php endif; ?>
      <?php if($area): ?><div class="field"><div class="field__label">Area</div><div class="field__val"><?= h($area) ?></div></div><?php endif; ?>
      <?php if($language): ?><div class="field"><div class="field__label">Language</div><div class="field__val"><?= h($language) ?></div></div><?php endif; ?>
      <?php if($notes): ?><div class="field field--full"><div class="field__label">Facts provided</div><div class="field__val"><?= h($notes) ?></div></div><?php endif; ?>
    <?php else: ?>
      <?php if($notes): ?><div class="field field--full"><div class="field__label">Feedback</div><div class="field__val"><?= h($notes) ?></div></div><?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="card__actions">
    <?php if($status!=='reviewed'): ?>
    <form method="post" style="display:inline">
      <input type="hidden" name="mark_id" value="<?= h($id) ?>">
      <input type="hidden" name="new_status" value="reviewed">
      <button type="submit" class="btn btn--review">✓ Mark Reviewed</button>
    </form>
    <?php endif; ?>
    <?php if($status!=='applied'): ?>
    <form method="post" style="display:inline">
      <input type="hidden" name="mark_id" value="<?= h($id) ?>">
      <input type="hidden" name="new_status" value="applied">
      <button type="submit" class="btn btn--apply">✅ Mark Applied</button>
    </form>
    <?php endif; ?>
    <?php if($status!=='pending'): ?>
    <form method="post" style="display:inline">
      <input type="hidden" name="mark_id" value="<?= h($id) ?>">
      <input type="hidden" name="new_status" value="pending">
      <button type="submit" class="btn btn--pending">↩ Reset to Pending</button>
    </form>
    <?php endif; ?>
    <?php if($id): ?><span style="font-size:.68rem;color:rgba(232,224,208,.2);align-self:center"><?= h($id) ?></span><?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

</div>
</body>
</html>
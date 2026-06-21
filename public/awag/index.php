<?php
declare(strict_types=1);
header('Cache-Control: no-cache');
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
$regions = [
['id'=>'guinea-coast','name'=>'Guinea Coast','icon'=>'🌿','color'=>'#2d6a1f','desc'=>'West Africa forest and coastal zone — high rainfall, yam belt, Atlantic fisheries','skins'=>[
['id'=>'igbo','name'=>'Igbo Calendar','lang'=>'Igbo','area'=>'Southeast Nigeria','color'=>'#2d6a1f','initial'=>'IG','soon'=>false],
['id'=>'yoruba','name'=>'Yoruba Calendar','lang'=>'Yoruba','area'=>'Southwest Nigeria','color'=>'#1a3a6b','initial'=>'YO','soon'=>false],
['id'=>'ibibio','name'=>'Ibibio Calendar','lang'=>'Ibibio','area'=>'South Nigeria','color'=>'#1a5a6b','initial'=>'IB','soon'=>false],
['id'=>'efik','name'=>'Efik Calendar','lang'=>'Efik','area'=>'Calabar / Cross River','color'=>'#1a3a5a','initial'=>'EF','soon'=>false],
['id'=>'oron','name'=>'Oron Calendar','lang'=>'Oron','area'=>'Oron / Akwa Ibom','color'=>'#1a4a3a','initial'=>'OR','soon'=>false],
['id'=>'bini','name'=>'Bini Calendar','lang'=>'Edo','area'=>'Edo State / Benin Kingdom','color'=>'#4a1a0a','initial'=>'BN','soon'=>false],
['id'=>'bekwarra','name'=>'Bekwarra Calendar','lang'=>'Bekwarra','area'=>'Cross River State','color'=>'#1a4a2a','initial'=>'BK','soon'=>true],
['id'=>'boki','name'=>'Boki Calendar','lang'=>'Boki','area'=>'Cross River State','color'=>'#1a3a2a','initial'=>'BO','soon'=>true],
['id'=>'ejagham','name'=>'Ejagham Calendar','lang'=>'Ejagham','area'=>'Cross River / Cameroon','color'=>'#2a3a1a','initial'=>'EJ','soon'=>true],
['id'=>'ogoja','name'=>'Ogoja Calendar','lang'=>'Ogoja','area'=>'Cross River State','color'=>'#1a4a2a','initial'=>'OG','soon'=>true],
['id'=>'akan','name'=>'Akan Calendar','lang'=>'Twi','area'=>'Ghana / Cote d Ivoire','color'=>'#1a4a2a','initial'=>'AK','soon'=>true],
['id'=>'fon','name'=>'Fon Calendar','lang'=>'Fon','area'=>'Benin / Togo','color'=>'#2a3a1a','initial'=>'FO','soon'=>true],
['id'=>'ewe','name'=>'Ewe Calendar','lang'=>'Ewe','area'=>'Ghana / Togo','color'=>'#1a3a2a','initial'=>'EW','soon'=>true],
]],
['id'=>'niger-delta','name'=>'Niger Delta','icon'=>'🌊','color'=>'#0a3a4a','desc'=>'Mangrove forests and creeks — flood agriculture, creek fishing, canoe transport, oil belt','skins'=>[
['id'=>'ijaw','name'=>'Ijaw Calendar','lang'=>'Izon','area'=>'Rivers / Bayelsa / Delta','color'=>'#0a3a4a','initial'=>'IJ','soon'=>false],
['id'=>'urhobo','name'=>'Urhobo Calendar','lang'=>'Urhobo','area'=>'Delta State','color'=>'#0a2a3a','initial'=>'UR','soon'=>true],
['id'=>'isoko','name'=>'Isoko Calendar','lang'=>'Isoko','area'=>'Delta State','color'=>'#0a3a2a','initial'=>'IS','soon'=>true],
['id'=>'itsekiri','name'=>'Itsekiri Calendar','lang'=>'Itsekiri','area'=>'Warri / Delta State','color'=>'#1a3a2a','initial'=>'IT','soon'=>true],
['id'=>'kalabari','name'=>'Kalabari Calendar','lang'=>'Kalabari','area'=>'Rivers State / Eastern Delta','color'=>'#0a2a4a','initial'=>'KL','soon'=>true],
['id'=>'nembe','name'=>'Nembe Calendar','lang'=>'Nembe','area'=>'Bayelsa State','color'=>'#0a3a3a','initial'=>'NM','soon'=>true],
['id'=>'bonny','name'=>'Bonny Calendar','lang'=>'Ibani','area'=>'Rivers State','color'=>'#0a2a3a','initial'=>'BO','soon'=>true],
['id'=>'ogoni','name'=>'Ogoni Calendar','lang'=>'Ogoni','area'=>'Rivers State','color'=>'#1a3a1a','initial'=>'OG','soon'=>true],
]],
['id'=>'middle-belt','name'=>'Middle Belt','icon'=>'🏞','color'=>'#3a2a0a','desc'=>'Nigeria Middle Belt river valleys — Benue, Niger confluence, Plateau highlands, diverse cultures','skins'=>[
['id'=>'igala','name'=>'Igala Calendar','lang'=>'Igala','area'=>'Kogi State / Niger Confluence','color'=>'#3a2a0a','initial'=>'IG','soon'=>true],
['id'=>'jukun','name'=>'Jukun Calendar','lang'=>'Jukun','area'=>'Taraba / Benue States','color'=>'#4a2a0a','initial'=>'JK','soon'=>true],
['id'=>'igbira','name'=>'Igbira Calendar','lang'=>'Igbira','area'=>'Kogi State','color'=>'#3a3a0a','initial'=>'IB','soon'=>true],
['id'=>'idoma','name'=>'Idoma Calendar','lang'=>'Idoma','area'=>'Benue State','color'=>'#4a3a0a','initial'=>'ID','soon'=>true],
['id'=>'nupe','name'=>'Nupe Calendar','lang'=>'Nupe','area'=>'Niger State / Kwara','color'=>'#3a2a10','initial'=>'NP','soon'=>true],
['id'=>'berom','name'=>'Berom Calendar','lang'=>'Berom','area'=>'Plateau State','color'=>'#3a3a10','initial'=>'BR','soon'=>true],
['id'=>'angas','name'=>'Angas Calendar','lang'=>'Angas','area'=>'Plateau State','color'=>'#3a2a10','initial'=>'AN','soon'=>true],
['id'=>'sura','name'=>'Sura Calendar','lang'=>'Sura','area'=>'Plateau State','color'=>'#2a3a10','initial'=>'SU','soon'=>true],
['id'=>'mumuye','name'=>'Mumuye Calendar','lang'=>'Mumuye','area'=>'Taraba State','color'=>'#4a3a10','initial'=>'MM','soon'=>true],
['id'=>'chamba','name'=>'Chamba Calendar','lang'=>'Chamba','area'=>'Taraba / Adamawa States','color'=>'#3a2a08','initial'=>'CH','soon'=>true],
['id'=>'kilba','name'=>'Kilba Calendar','lang'=>'Kilba','area'=>'Adamawa State','color'=>'#3a3a08','initial'=>'KL','soon'=>true],
]],
['id'=>'sahel','name'=>'Sahel & Savanna','icon'=>'🏜','color'=>'#6b3a1a','desc'=>'Sub-Saharan drylands — millet and sorghum belt, Fulani cattle routes, trans-Saharan trade','skins'=>[
['id'=>'hausa','name'=>'Hausa Calendar','lang'=>'Hausa','area'=>'Northern Nigeria / Niger','color'=>'#6b3a1a','initial'=>'HA','soon'=>false],
['id'=>'wolof','name'=>'Wolof Calendar','lang'=>'Wolof','area'=>'Senegal / Gambia','color'=>'#2d4a1a','initial'=>'WO','soon'=>false],
['id'=>'tiv','name'=>'Tiv Calendar','lang'=>'Tiv','area'=>'Benue State / Middle Belt','color'=>'#4a2a0a','initial'=>'TV','soon'=>false],
['id'=>'fulani','name'=>'Fulani Calendar','lang'=>'Fulfulde','area'=>'West and Central Africa','color'=>'#5a3a10','initial'=>'FU','soon'=>true],
['id'=>'tuareg','name'=>'Tuareg Calendar','lang'=>'Tamasheq','area'=>'Mali / Niger / Algeria','color'=>'#4a3a20','initial'=>'TU','soon'=>true],
['id'=>'mandinka','name'=>'Mandinka Calendar','lang'=>'Mandinka','area'=>'Gambia / Guinea','color'=>'#3a2a10','initial'=>'MN','soon'=>true],
['id'=>'zarma','name'=>'Zarma Calendar','lang'=>'Zarma','area'=>'Niger / Burkina Faso','color'=>'#4a2a10','initial'=>'ZA','soon'=>true],
]],
['id'=>'east-africa','name'=>'East African Rift','icon'=>'🦁','color'=>'#8b1a1a','desc'=>'Great Rift Valley highlands — dual rainy seasons, cattle culture, highland farming','skins'=>[
['id'=>'maasai','name'=>'Maasai Calendar','lang'=>'Maa','area'=>'Kenya / Tanzania','color'=>'#8b1a1a','initial'=>'MA','soon'=>false],
['id'=>'amhara','name'=>'Amhara Calendar','lang'=>'Amharic','area'=>'Ethiopia','color'=>'#4a1a6b','initial'=>'AM','soon'=>false],
['id'=>'kikuyu','name'=>'Kikuyu Calendar','lang'=>'Gikuyu','area'=>'Central Kenya','color'=>'#1a4a1a','initial'=>'KI','soon'=>true],
['id'=>'luo','name'=>'Luo Calendar','lang'=>'Dholuo','area'=>'Lake Victoria','color'=>'#1a2a5a','initial'=>'LU','soon'=>true],
['id'=>'oromo','name'=>'Oromo Calendar','lang'=>'Oromo','area'=>'Ethiopia / Kenya','color'=>'#3a1a6b','initial'=>'OR','soon'=>true],
['id'=>'somali','name'=>'Somali Calendar','lang'=>'Somali','area'=>'Horn of Africa','color'=>'#5a2a10','initial'=>'SO','soon'=>true],
]],
['id'=>'indian-ocean','name'=>'Indian Ocean Coast','icon'=>'🌊','color'=>'#1a4a4a','desc'=>'Swahili coast, monsoon trade winds, dhow culture, coral reef fisheries','skins'=>[
['id'=>'swahili','name'=>'Swahili Calendar','lang'=>'Kiswahili','area'=>'East Africa Coast','color'=>'#1a4a4a','initial'=>'SW','soon'=>false],
['id'=>'malagasy','name'=>'Malagasy Calendar','lang'=>'Malagasy','area'=>'Madagascar','color'=>'#1a3a4a','initial'=>'MG','soon'=>true],
['id'=>'comorian','name'=>'Comorian Calendar','lang'=>'Comorian','area'=>'Comoros Islands','color'=>'#0a3a3a','initial'=>'CO','soon'=>true],
['id'=>'mijikenda','name'=>'Mijikenda Calendar','lang'=>'Mijikenda','area'=>'Kenya Coast','color'=>'#1a4a3a','initial'=>'MI','soon'=>true],
]],
['id'=>'congo-basin','name'=>'Congo Basin','icon'=>'🌍','color'=>'#1a4a2a','desc'=>'Equatorial rainforest — year-round rains, river fisheries, forest farming','skins'=>[
['id'=>'lingala','name'=>'Lingala Calendar','lang'=>'Lingala','area'=>'DRC / Congo','color'=>'#1a4a2a','initial'=>'LI','soon'=>true],
['id'=>'kongo','name'=>'Kongo Calendar','lang'=>'Kikongo','area'=>'DRC / Angola','color'=>'#2a4a1a','initial'=>'KO','soon'=>true],
['id'=>'luba','name'=>'Luba Calendar','lang'=>'Tshiluba','area'=>'Central DRC','color'=>'#1a3a2a','initial'=>'LB','soon'=>true],
['id'=>'bamileke','name'=>'Bamileke Calendar','lang'=>'Bamileke','area'=>'Cameroon','color'=>'#2a3a10','initial'=>'BA','soon'=>true],
]],
['id'=>'southern-africa','name'=>'Southern Africa','icon'=>'🌾','color'=>'#5a3a10','desc'=>'Southern savanna and highveld — summer rainfall, maize belt, winter dry season','skins'=>[
['id'=>'zulu','name'=>'Zulu Calendar','lang'=>'isiZulu','area'=>'South Africa','color'=>'#5a1a1a','initial'=>'ZU','soon'=>true],
['id'=>'shona','name'=>'Shona Calendar','lang'=>'Shona','area'=>'Zimbabwe','color'=>'#3a2a10','initial'=>'SH','soon'=>true],
['id'=>'tswana','name'=>'Tswana Calendar','lang'=>'Setswana','area'=>'Botswana / SA','color'=>'#4a3a10','initial'=>'TS','soon'=>true],
['id'=>'ndebele','name'=>'Ndebele Calendar','lang'=>'isiNdebele','area'=>'Zimbabwe / SA','color'=>'#4a1a2a','initial'=>'ND','soon'=>true],
]],
['id'=>'north-africa','name'=>'North Africa','icon'=>'⛰','color'=>'#4a3a10','desc'=>'Mediterranean, Atlas Mountains, Nile Valley — winter rains, irrigation farming, desert pastoralism','skins'=>[
['id'=>'amazigh','name'=>'Amazigh Calendar','lang'=>'Tamazight','area'=>'Morocco / Algeria','color'=>'#4a3a10','initial'=>'AZ','soon'=>true],
['id'=>'nile','name'=>'Nile Calendar','lang'=>'Arabic','area'=>'Egypt / Sudan','color'=>'#3a2a10','initial'=>'NI','soon'=>true],
['id'=>'nubian','name'=>'Nubian Calendar','lang'=>'Nubian','area'=>'Sudan / Egypt','color'=>'#4a2a10','initial'=>'NU','soon'=>true],
['id'=>'tubu','name'=>'Tubu Calendar','lang'=>'Tedaga','area'=>'Chad / Libya','color'=>'#3a3a10','initial'=>'TB','soon'=>true],
]],
];

$total_live=0; $total_soon=0;
foreach($regions as $r) foreach($r['skins'] as $sk) { if($sk['soon']) $total_soon++; else $total_live++; }
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AWAG — Africa Weekly Activities Guide</title>
<meta name="description" content="Africa Weekly Activities Guide — seasonal calendars for farming, fishing, trading, herding and healing across African communities.">
<link rel="manifest" href="/awag/manifest.json">
<meta name="theme-color" content="#0e0804">
<link rel="icon" href="/awag/icons/icon-192.png">
<script>if('serviceWorker' in navigator){navigator.serviceWorker.register('/awag/sw.js',{scope:'/awag/'}).catch(function(){});}</script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{min-height:100vh;font-family:system-ui,-apple-system,sans-serif;background:#0e0804;color:#e8e0d0}
a{color:inherit;text-decoration:none}
.wrap{max-width:1140px;margin:0 auto;padding:0 16px}
.hdr{background:#120a04;border-bottom:1px solid rgba(245,217,122,.12);position:sticky;top:0;z-index:20}
.hdr .wrap{display:flex;align-items:center;justify-content:space-between;min-height:64px;gap:14px}
.brand{display:inline-flex;align-items:center;gap:12px}
.brand img{width:40px;height:40px;border-radius:10px}
.brand__name{font-weight:900;font-size:1.1rem;color:#f5d97a;letter-spacing:2px}
.brand__sub{font-size:.7rem;color:rgba(245,217,122,.45);letter-spacing:1px}
.nav-back{padding:7px 16px;border-radius:9px;border:1px solid rgba(245,217,122,.2);font-size:.84rem;color:rgba(245,217,122,.65);font-weight:600}
.hero{padding:44px 0 32px;text-align:center;border-bottom:1px solid rgba(245,217,122,.08)}
.hero h1{font-size:clamp(1.8rem,4vw,3rem);font-weight:900;color:#f5d97a;letter-spacing:-.02em;line-height:1.05}
.hero h1 span{color:#e8e0d0}
.hero p{margin:14px auto 0;max-width:62ch;line-height:1.8;color:rgba(232,224,208,.6);font-size:.98rem}
.stats{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-top:20px}
.stat{text-align:center;padding:10px 20px;border:1px solid rgba(245,217,122,.14);border-radius:12px;background:rgba(245,217,122,.04)}
.stat b{font-size:1.6rem;font-weight:900;color:#f5d97a;display:block}
.stat span{font-size:.72rem;color:rgba(232,224,208,.45);letter-spacing:1px;text-transform:uppercase}
.tags{display:flex;gap:7px;flex-wrap:wrap;justify-content:center;margin-top:18px}
.tag{padding:5px 12px;border:1px solid rgba(245,217,122,.14);border-radius:999px;font-size:.8rem;color:rgba(232,224,208,.6)}
.regions{padding:30px 0 48px}
.region{margin-bottom:38px}
.rhead{display:flex;align-items:flex-start;gap:14px;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid rgba(245,217,122,.09)}
.ricon{font-size:1.5rem;line-height:1;margin-top:2px}
.rname{font-weight:900;font-size:1.05rem;color:#f5d97a}
.rdesc{font-size:.78rem;color:rgba(232,224,208,.45);margin-top:3px;line-height:1.5}
.rcount{margin-left:auto;font-size:.75rem;color:rgba(245,217,122,.4);white-space:nowrap;padding-top:3px}
.sgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:11px}
.scard{border:1px solid rgba(245,217,122,.10);border-radius:15px;background:rgba(245,217,122,.03);overflow:hidden;display:flex;flex-direction:column;transition:transform .12s,border-color .12s}
.scard:not(.scard--soon):hover{transform:translateY(-2px);border-color:rgba(245,217,122,.24)}
.scard--soon{opacity:.48}
.scard__bar{height:4px}
.scard__body{padding:13px;flex:1;display:flex;flex-direction:column;gap:7px}
.scard__top{display:flex;align-items:center;gap:9px}
.scard__av{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.85rem;color:#fff;flex:0 0 auto}
.scard__name{font-weight:800;font-size:.88rem;color:#e8e0d0}
.scard__area{font-size:.72rem;color:rgba(232,224,208,.45)}
.smeta{display:flex;gap:5px;flex-wrap:wrap}
.pill{padding:2px 8px;border:1px solid rgba(245,217,122,.11);border-radius:999px;font-size:.7rem;color:rgba(232,224,208,.5)}
.pill--live{border-color:rgba(80,200,48,.4);color:#60c840;background:rgba(80,200,48,.07)}
.scard__act{margin-top:auto;padding-top:9px}
.btn{padding:8px 15px;border-radius:9px;font-weight:700;font-size:.82rem;display:inline-flex;align-items:center;gap:5px;border:none;cursor:pointer;text-decoration:none}
.btn--p{background:#2d6a1f;color:#fff}
.btn--s{background:rgba(245,217,122,.04);color:rgba(232,224,208,.3);border:1px solid rgba(245,217,122,.07)}
.feat-lbl{font-size:.7rem;font-weight:700;letter-spacing:.1em;color:rgba(245,217,122,.32);text-transform:uppercase;margin-bottom:13px}
.feats{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:10px;margin-bottom:48px}
.feat{padding:13px;border:1px solid rgba(245,217,122,.07);border-radius:12px;background:rgba(245,217,122,.02)}
.feat i{font-size:1.35rem;display:block;margin-bottom:6px}
.feat b{font-weight:700;font-size:.86rem;color:#e8e0d0;display:block;margin-bottom:3px}
.feat p{font-size:.76rem;color:rgba(232,224,208,.47);line-height:1.5}
.ftr{padding:22px 0;border-top:1px solid rgba(245,217,122,.07);text-align:center;color:rgba(232,224,208,.3);font-size:.8rem}
.ftr a{color:rgba(245,217,122,.4)}
@media(max-width:580px){.sgrid{grid-template-columns:repeat(auto-fill,minmax(155px,1fr))}.stats{gap:8px}}
</style>
</head>
<body>
<header class="hdr">
  <div class="wrap">
    <div class="brand">
      <img src="/awag/icons/icon-192.png" alt="AWAG">
      <div><div class="brand__name">AWAG</div><div class="brand__sub">Africa Weekly Activities Guide</div></div>
    </div>
    <a href="/" class="nav-back">← Mkomigbo</a>
  </div>
</header>
<main>
<div class="wrap">
<section class="hero">
  <h1>Your Region.<br><span>Your Calendar.</span></h1>
  <p>Seasonal guides for farming, fishing, trading, cattle herding, traditional healing and community life — rooted in African ecological wisdom, built for offline use.</p>
  <div class="stats">
    <div class="stat"><b><?= $total_live ?></b><span>Live communities</span></div>
    <div class="stat"><b><?= count($regions) ?></b><span>Ecological zones</span></div>
    <div class="stat"><b><?= $total_live+$total_soon ?></b><span>Communities planned</span></div>
    <div class="stat"><b>11</b><span>Modules per skin</span></div>
  </div>
  <div class="tags">
    <span class="tag">🌾 Farming</span><span class="tag">🎣 Fishing</span><span class="tag">🐄 Herding</span>
    <span class="tag">🛒 Trading</span><span class="tag">🌿 Healing</span><span class="tag">🌙 Lunar</span>
    <span class="tag">🌊 Tides</span><span class="tag">💨 Winds</span><span class="tag">🌧 Rainfall</span>
    <span class="tag">📴 Offline</span><span class="tag">📱 Installable</span>
  </div>
</section>
<div class="regions">
<?php foreach($regions as $region):
  $live=count(array_filter($region['skins'],fn($s)=>!$s['soon']));
  $tot=count($region['skins']);
?>
<div class="region" id="<?= h($region['id']) ?>">
  <div class="rhead">
    <span class="ricon"><?= $region['icon'] ?></span>
    <div><div class="rname"><?= h($region['name']) ?></div><div class="rdesc"><?= h($region['desc']) ?></div></div>
    <div class="rcount"><?= $live ?>/<?= $tot ?> live</div>
  </div>
  <div class="sgrid">
  <?php foreach($region['skins'] as $sk): ?>
    <div class="scard<?= $sk['soon']?' scard--soon':'' ?>">
      <div class="scard__bar" style="background:<?= h($sk['color']) ?>"></div>
      <div class="scard__body">
        <div class="scard__top">
          <div class="scard__av" style="background:<?= h($sk['color']) ?>"><?= h($sk['initial']) ?></div>
          <div><div class="scard__name"><?= h($sk['name']) ?></div><div class="scard__area"><?= h($sk['area']) ?></div></div>
        </div>
        <div class="smeta">
          <span class="pill"><?= h($sk['lang']) ?></span>
          <?php if(!$sk['soon']): ?><span class="pill pill--live">Live</span>
          <?php else: ?><span class="pill">Coming soon</span><?php endif; ?>
        </div>
        <div class="scard__act">
          <?php if(!$sk['soon']): ?>
          <a href="/awag/skins/<?= h($sk['id']) ?>/" class="btn btn--p">Open Calendar &rarr;</a>
          <?php else: ?><span class="btn btn--s">Coming soon</span><?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
</div>
<div class="feat-lbl">What AWAG covers</div>
<div class="feats">
  <div class="feat"><i>🌾</i><b>Farming Seasons</b><p>Planting and harvest cycles by crop, region, and rainfall pattern.</p></div>
  <div class="feat"><i>🎣</i><b>Fishing Periods</b><p>Best fishing times by water body, season, tides, and moon phase.</p></div>
  <div class="feat"><i>🌊</i><b>Tides and Winds</b><p>Coastal tide guides, trade winds, harmattan, and monsoon cycles.</p></div>
  <div class="feat"><i>🐄</i><b>Cattle Herding</b><p>Grazing seasons, migration corridors, and water availability.</p></div>
  <div class="feat"><i>🛒</i><b>Market Cycles</b><p>4-day, 5-day, and 7-day market week cycles by community.</p></div>
  <div class="feat"><i>🌿</i><b>Traditional Healing</b><p>Herb harvest seasons, moon phases, and ceremonial timing.</p></div>
  <div class="feat"><i>👑</i><b>Traditional Rulers</b><p>Festival calendars, ceremonial dates, and community observances.</p></div>
  <div class="feat"><i>🌙</i><b>Lunar Calendar</b><p>Moon phases integrated with traditional calendar systems.</p></div>
  <div class="feat"><i>🌧</i><b>Rainfall Patterns</b><p>Early and late rains, dry spells, annual pattern per zone.</p></div>
  <div class="feat"><i>📴</i><b>Works Offline</b><p>Install on any device. Works without internet. Updates when connected.</p></div>
</div>
</div>
</main>
<footer class="ftr"><div class="wrap">&copy; <?= date('Y') ?> Mkomigbo &middot; AWAG &mdash; Africa Weekly Activities Guide &middot; <a href="/">Mkomigbo Home</a></div></footer>
<script src="/awag/engine/awag-feedback.js"></script>
</body></html>

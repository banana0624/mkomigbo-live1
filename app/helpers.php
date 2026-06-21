<?php

declare(strict_types=1);

function mk_render_page(array $page): void
{
    $title = htmlspecialchars((string)($page['title'] ?? 'Mkomigbo'), ENT_QUOTES, 'UTF-8');
    $body  = $page['body'] ?? '';
    $slug  = (string)($page['slug'] ?? '');

    // For the homepage, render the full landing page
    if ($slug === 'home' || $body === '') {
        mk_render_homepage($page);
        return;
    }

    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . $title . ' – Mkomigbo</title>';
    echo '<link rel="stylesheet" href="/assets/css/ui.css">';
    echo '<link rel="stylesheet" href="/assets/css/public.css">';
    echo '</head>';
    echo '<body>';
    echo '<header class="site-header"><div class="container"><a class="brand" href="/"><img src="/assets/images/logos/mk-logo.png" alt="Mkomigbo" width="32" height="32" style="border-radius:8px;"><span class="brand__text"><span class="brand__title">Mkomigbo</span><span class="brand__sub">Knowledge Platform</span></span></a><nav class="nav"><a href="/subjects/">Subjects</a><a href="/platforms/">Platforms</a><a href="/contributors/">Contributors</a><a href="/igbo-calendar/">Calendar</a></nav></div></header>';
    echo '<main class="site-main"><div class="container">';
    echo '<h1>' . $title . '</h1>';
    echo $body;
    echo '</div></main>';
    echo '<footer style="margin-top:48px;padding:24px 0;border-top:1px solid var(--border);text-align:center;color:var(--muted);font-size:.9rem;"><div class="container">© ' . date('Y') . ' Mkomigbo · <a href="/staff/">Staff</a></div></footer>';
    echo '</body></html>';
}

function mk_render_homepage(array $page): void {
    $nav_links = [
        ['/subjects/','Subjects'],
        ['/platforms/','Platforms'],
        ['/contributors/','Contributors'],
        ['/awag/','🌍 AWAG'],
        ['/igbo-calendar/','Calendar'],
    ];
    $sections = [
        ['href'=>'/subjects/history',     'icon'=>'🏺','title'=>'History',     'desc'=>'Timelines, migrations, kingdoms, and turning points of Igbo civilization.','color'=>'#2b6cb0'],
        ['href'=>'/subjects/culture',     'icon'=>'🎭','title'=>'Culture',     'desc'=>'Arts, customs, festivals, clothing, and values that define Igbo life.','color'=>'#805ad5'],
        ['href'=>'/subjects/language1',   'icon'=>'🗣️','title'=>'Language',    'desc'=>'Igbo language systems, orthography, writing, and learning tracks.','color'=>'#2f855a'],
        ['href'=>'/subjects/spirituality','icon'=>'🔮','title'=>'Esoterism',   'desc'=>'Cosmology, metaphysics, and spiritual practice in Igbo tradition.','color'=>'#744210'],
        ['href'=>'/subjects/religion',    'icon'=>'🕊️','title'=>'Religion',    'desc'=>'Religious life, institutions, and belief systems in Igbo society.','color'=>'#b7791f'],
        ['href'=>'/subjects/tradition',   'icon'=>'🌿','title'=>'Tradition',   'desc'=>'Traditional norms, rites, and communal frameworks.','color'=>'#276749'],
        ['href'=>'/subjects/nigeria',     'icon'=>'🇳🇬','title'=>'Nigeria',    'desc'=>'Nigeria: politics, society, and the Igbo experience.','color'=>'#1a365d'],
        ['href'=>'/subjects/biafra',      'icon'=>'⚡','title'=>'Biafra',      'desc'=>'Biafra history, war, aftermath, and memory.','color'=>'#9b2c2c'],
    ];
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

    echo '<!DOCTYPE html><html lang="en"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Mkomigbo – Igbo Knowledge Platform</title>';
    echo '<meta name="description" content="A comprehensive resource for Igbo history, culture, language, spirituality, and the broader African experience.">';
    echo '<link rel="stylesheet" href="/assets/css/ui.css">';
    echo '<link rel="stylesheet" href="/assets/css/public.css">';
    echo '<link rel="stylesheet" href="/assets/css/home.css">';
    echo '<style>
*{box-sizing:border-box}
.mk-hp-wrap{max-width:1140px;margin:0 auto;padding:0 16px}
.mk-hp-hdr{background:#fff;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:20}
.mk-hp-hdr-inner{display:flex;align-items:center;justify-content:space-between;min-height:64px;gap:14px;flex-wrap:wrap}
.mk-hp-brand{display:inline-flex;align-items:center;gap:10px;text-decoration:none;color:inherit}
.mk-hp-brand img{border-radius:10px}
.mk-hp-brand__title{font-weight:900;font-size:1.08rem;display:block}
.mk-hp-brand__sub{font-size:.78rem;color:#6b7280;display:block}
.mk-hp-nav{display:flex;gap:4px;flex-wrap:wrap;align-items:center}
.mk-hp-nav a{padding:7px 13px;border-radius:10px;text-decoration:none;color:#374151;font-weight:600;font-size:.88rem;border:1px solid transparent}
.mk-hp-nav a:hover{background:#f3f4f6;border-color:#e5e7eb}
.mk-hp-nav a.active{color:#0d6efd;background:rgba(13,110,253,.08);border-color:rgba(13,110,253,.25);font-weight:800}
.mk-hp-hero{margin-top:24px;border:1px solid #e5e7eb;border-radius:20px;background:linear-gradient(160deg,#f8faff 0%,#fff 60%);overflow:hidden;box-shadow:0 20px 55px rgba(0,0,0,.07)}
.mk-hp-hero__bar{height:5px;background:linear-gradient(90deg,#1a2744,#2b6cb0,#276749)}
.mk-hp-hero__inner{padding:32px 28px 28px}
.mk-hp-hero__title{margin:0 0 10px;font-size:clamp(1.8rem,4vw,3rem);line-height:1.05;letter-spacing:-.02em;color:#111}
.mk-hp-hero__sub{margin:0 0 20px;max-width:68ch;line-height:1.8;font-size:1.02rem;color:#374151}
.mk-hp-cta{display:flex;gap:10px;flex-wrap:wrap}
.mk-hp-cta a{padding:10px 20px;border-radius:12px;text-decoration:none;font-weight:700;font-size:.92rem;display:inline-flex;align-items:center;gap:6px}
.mk-hp-cta__primary{background:#1a2744;color:#fff}
.mk-hp-cta__primary:hover{background:#2d3f6b}
.mk-hp-cta__outline{background:#fff;color:#111;border:1px solid #d1d5db}
.mk-hp-cta__outline:hover{border-color:#9ca3af;background:#f9fafb}
.mk-hp-cta__awag{background:linear-gradient(135deg,#0a3a4a,#1a5a3a);color:#f5d97a;border:1px solid rgba(245,217,122,.3)}
.mk-hp-cta__awag:hover{background:linear-gradient(135deg,#0e4a5a,#1e6a42)}
.mk-hp-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin:28px 0 0}
.mk-hp-stat{text-align:center;padding:14px;border:1px solid #e5e7eb;border-radius:14px;background:#fff}
.mk-hp-stat__n{font-size:1.8rem;font-weight:900;color:#1a2744;display:block}
.mk-hp-stat__l{font-size:.72rem;color:#6b7280;letter-spacing:.06em;text-transform:uppercase}
.mk-hp-awag{margin-top:28px;border:1px solid rgba(245,217,122,.3);border-radius:18px;background:linear-gradient(135deg,#0e0804,#1a0e04);overflow:hidden;display:flex;flex-direction:column}
.mk-hp-awag__bar{height:4px;background:linear-gradient(90deg,#c85e28,#f5d97a,#2d6a1f)}
.mk-hp-awag__inner{padding:22px 22px 20px;display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap}
.mk-hp-awag__text{flex:1;min-width:200px}
.mk-hp-awag__title{font-size:1.15rem;font-weight:900;color:#f5d97a;margin:0 0 6px}
.mk-hp-awag__desc{font-size:.88rem;color:rgba(232,224,208,.7);line-height:1.65;margin:0 0 14px}
.mk-hp-awag__tags{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.mk-hp-awag__tag{padding:3px 10px;border:1px solid rgba(245,217,122,.2);border-radius:999px;font-size:.75rem;color:rgba(245,217,122,.7)}
.mk-hp-awag__btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#2d6a1f;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:.86rem}
.mk-hp-awag__btn:hover{background:#3d8a2f}
.mk-hp-awag__stats{display:flex;flex-direction:column;gap:8px;min-width:120px}
.mk-hp-awag__stat{text-align:center;padding:10px 16px;border:1px solid rgba(245,217,122,.15);border-radius:12px;background:rgba(245,217,122,.04)}
.mk-hp-awag__stat b{font-size:1.4rem;font-weight:900;color:#f5d97a;display:block}
.mk-hp-awag__stat span{font-size:.68rem;color:rgba(232,224,208,.4);letter-spacing:.06em;text-transform:uppercase}
.mk-hp-sec-title{display:flex;align-items:center;justify-content:space-between;margin:32px 0 14px;gap:10px}
.mk-hp-sec-title h2{margin:0;font-size:1.1rem;font-weight:900;color:#111}
.mk-hp-sec-title a{font-size:.86rem;color:#6b7280;text-decoration:none}
.mk-hp-sec-title a:hover{color:#111}
.mk-hp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px}
.mk-hp-card{display:flex;gap:13px;align-items:flex-start;padding:15px;border:1px solid #e5e7eb;border-radius:15px;background:#fff;text-decoration:none;color:inherit;transition:transform .12s,box-shadow .12s,border-color .12s}
.mk-hp-card:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(0,0,0,.09);border-color:rgba(0,0,0,.18)}
.mk-hp-card__icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex:0 0 auto;color:#fff}
.mk-hp-card__title{margin:0 0 3px;font-weight:800;font-size:.95rem;color:#111}
.mk-hp-card__desc{margin:0;font-size:.82rem;line-height:1.5;color:#6b7280}
.mk-hp-about{margin:32px 0 0;padding:24px;border:1px solid #e5e7eb;border-radius:18px;background:linear-gradient(180deg,#f8faff,#fff)}
.mk-hp-about h2{margin:0 0 8px;font-size:1.1rem;font-weight:900;color:#111}
.mk-hp-about p{margin:0 0 14px;max-width:80ch;line-height:1.75;color:#374151;font-size:.94rem}
.mk-hp-about__links{display:flex;gap:8px;flex-wrap:wrap}
.mk-hp-about__links a{padding:8px 16px;border:1px solid #d1d5db;border-radius:10px;text-decoration:none;color:#111;font-weight:600;font-size:.88rem;background:#fff}
.mk-hp-about__links a:hover{border-color:#9ca3af;background:#f9fafb}
.mk-hp-footer{margin-top:48px;padding:24px 0;border-top:1px solid #e5e7eb}
.mk-hp-footer-inner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.mk-hp-footer__copy{color:#9ca3af;font-size:.86rem}
.mk-hp-footer__nav{display:flex;gap:14px;flex-wrap:wrap}
.mk-hp-footer__nav a{color:#9ca3af;text-decoration:none;font-size:.86rem}
.mk-hp-footer__nav a:hover{color:#374151}
@media(max-width:600px){
  .mk-hp-hero__inner{padding:20px 16px}
  .mk-hp-awag__inner{padding:16px}
  .mk-hp-grid{grid-template-columns:1fr 1fr}
  .mk-hp-stats{grid-template-columns:1fr 1fr}
}
</style>';
    echo '</head><body>';

    // Header
    echo '<header class="mk-hp-hdr"><div class="mk-hp-wrap"><div class="mk-hp-hdr-inner">';
    echo '<a class="mk-hp-brand" href="/"><img src="/assets/images/logos/mk-logo.png" width="34" height="34" alt="Mkomigbo"><span><span class="mk-hp-brand__title">Mkomigbo</span><span class="mk-hp-brand__sub">Knowledge Platform</span></span></a>';
    echo '<nav class="mk-hp-nav">';
    foreach ($nav_links as [$href, $label]) {
        echo '<a href="'.$h($href).'">'.$h($label).'</a>';
    }
    echo '</nav></div></div></header>';

    // Main
    echo '<main><div class="mk-hp-wrap">';

    // Hero
    echo '<section class="mk-hp-hero">';
    echo '<div class="mk-hp-hero__bar"></div>';
    echo '<div class="mk-hp-hero__inner">';
    echo '<h1 class="mk-hp-hero__title">Igbo Knowledge Platform</h1>';
    echo '<p class="mk-hp-hero__sub">A comprehensive resource for Igbo history, culture, language, spirituality, and the broader African experience — built by scholars, for everyone.</p>';
    echo '<div class="mk-hp-cta">';
    echo '<a href="/subjects/" class="mk-hp-cta__primary">Explore Subjects →</a>';
    echo '<a href="/awag/" class="mk-hp-cta__awag">🌍 AWAG — Africa Weekly Activities</a>';
    echo '<a href="/igbo-calendar/" class="mk-hp-cta__outline">📅 Igbo Calendar</a>';
    echo '<a href="/contributors/" class="mk-hp-cta__outline">Meet Contributors</a>';
    echo '</div>';
    echo '<div class="mk-hp-stats">';
    echo '<div class="mk-hp-stat"><span class="mk-hp-stat__n">19</span><span class="mk-hp-stat__l">Subject areas</span></div>';
    echo '<div class="mk-hp-stat"><span class="mk-hp-stat__n">13</span><span class="mk-hp-stat__l">AWAG communities</span></div>';
    echo '<div class="mk-hp-stat"><span class="mk-hp-stat__n">61</span><span class="mk-hp-stat__l">Communities planned</span></div>';
    echo '<div class="mk-hp-stat"><span class="mk-hp-stat__n">11</span><span class="mk-hp-stat__l">Modules per calendar</span></div>';
    echo '</div>';
    echo '</div></section>';

    // AWAG feature block
    echo '<div class="mk-hp-awag">';
    echo '<div class="mk-hp-awag__bar"></div>';
    echo '<div class="mk-hp-awag__inner">';
    echo '<div class="mk-hp-awag__text">';
    echo '<div class="mk-hp-awag__title">🌍 AWAG — Africa Weekly Activities Guide</div>';
    echo '<p class="mk-hp-awag__desc">Seasonal calendars for farming, fishing, trading, cattle herding, traditional healing and community life — rooted in African ecological wisdom. Works offline. Install on any device.</p>';
    echo '<div class="mk-hp-awag__tags">';
    foreach (['🌾 Farming','🎣 Fishing','🐄 Herding','🛒 Trading','🌿 Healing','🌙 Lunar','🌊 Tides','💨 Winds','🌧 Rainfall','📴 Offline'] as $tag) {
        echo '<span class="mk-hp-awag__tag">'.$h($tag).'</span>';
    }
    echo '</div>';
    echo '<a href="/awag/" class="mk-hp-awag__btn">Open AWAG →</a>';
    echo '</div>';
    echo '<div class="mk-hp-awag__stats">';
    echo '<div class="mk-hp-awag__stat"><b>13</b><span>Live</span></div>';
    echo '<div class="mk-hp-awag__stat"><b>9</b><span>Zones</span></div>';
    echo '<div class="mk-hp-awag__stat"><b>61</b><span>Planned</span></div>';
    echo '</div>';
    echo '</div></div>';

    // Subjects grid
    echo '<div class="mk-hp-sec-title"><h2>Explore by Subject</h2><a href="/subjects/">All 19 subjects →</a></div>';
    echo '<div class="mk-hp-grid">';
    foreach ($sections as $s) {
        echo '<a href="'.$h($s['href']).'" class="mk-hp-card">';
        echo '<div class="mk-hp-card__icon" style="background:'.$h($s['color']).'">'.$s['icon'].'</div>';
        echo '<div><p class="mk-hp-card__title">'.$h($s['title']).'</p><p class="mk-hp-card__desc">'.$h($s['desc']).'</p></div>';
        echo '</a>';
    }
    echo '</div>';

    // About
    echo '<section class="mk-hp-about">';
    echo '<h2>About Mkomigbo</h2>';
    echo '<p>Mkomigbo is an open knowledge project dedicated to preserving and sharing Igbo heritage. From ancient history and oral tradition to contemporary diaspora experience, every subject is researched, sourced, and made freely accessible.</p>';
    echo '<div class="mk-hp-about__links">';
    echo '<a href="/subjects/about">About the Project</a>';
    echo '<a href="/contributors/">Contributors</a>';
    echo '<a href="/platforms/">Platforms</a>';
    echo '<a href="/contribute/">Contribute</a>';
    echo '</div></section>';

    echo '</div></main>';

    // Footer
    echo '<footer class="mk-hp-footer"><div class="mk-hp-wrap"><div class="mk-hp-footer-inner">';
    echo '<span class="mk-hp-footer__copy">© '.date('Y').' Mkomigbo · Igbo Knowledge Platform</span>';
    echo '<nav class="mk-hp-footer__nav">';
    foreach ($nav_links as [$href, $label]) {
        echo '<a href="'.$h($href).'">'.$h($label).'</a>';
    }
    echo '</nav></div></div></footer>';

    echo '</body></html>';
}


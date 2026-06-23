<?php
declare(strict_types=1);

$page_title = 'Mkomigbo — Igbo Knowledge Platform';
$page_desc  = 'The open knowledge platform for Igbo history, culture, language, religion, and African heritage. 20 subjects, 130+ pages, free access to thousands of texts.';
$nav_active = 'home';
$extra_css  = ['/assets/css/home.css'];

require_once __DIR__ . '/_init.php';

if (function_exists('mk_require_shared')) {
    try { mk_require_shared('public_header.php'); } catch (Throwable $e) {}
}
?>
<style>
.mk-home-hero{background:linear-gradient(135deg,#0a1a0a 0%,#0f2d0f 60%,#1a3a1a 100%);color:#e8f0e8;padding:72px 0 60px;position:relative;overflow:hidden;}
.mk-home-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 60% 40%,rgba(45,106,31,.18) 0%,transparent 70%);pointer-events:none;}
.mk-home-hero .wrap{max-width:1100px;margin:0 auto;padding:0 24px;position:relative;z-index:1;}
.mk-home-hero__eyebrow{font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:rgba(45,200,80,.7);margin-bottom:16px;}
.mk-home-hero__title{font-size:clamp(2.2rem,5vw,3.8rem);font-weight:900;line-height:1.05;letter-spacing:-0.02em;margin:0 0 20px;color:#fff;}
.mk-home-hero__title em{font-style:normal;color:#5de87a;}
.mk-home-hero__subtitle{font-size:clamp(1rem,2vw,1.2rem);color:rgba(232,240,232,.7);max-width:62ch;line-height:1.75;margin:0 0 32px;}
.mk-home-hero__cta{display:flex;gap:12px;flex-wrap:wrap;}
.mk-home-hero__btn{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;border-radius:12px;font-weight:800;font-size:.95rem;text-decoration:none;transition:transform .15s,box-shadow .15s;}
.mk-home-hero__btn:hover{transform:translateY(-2px);}
.mk-home-hero__btn--primary{background:#2d6a1f;color:#fff;box-shadow:0 4px 20px rgba(45,106,31,.4);}
.mk-home-hero__btn--primary:hover{box-shadow:0 8px 28px rgba(45,106,31,.5);}
.mk-home-hero__btn--ghost{background:rgba(255,255,255,.07);color:#e8f0e8;border:1px solid rgba(255,255,255,.18);}
.mk-home-hero__btn--ghost:hover{background:rgba(255,255,255,.14);}
.mk-home-hero__stats{display:flex;gap:36px;flex-wrap:wrap;margin-top:48px;padding-top:32px;border-top:1px solid rgba(255,255,255,.1);}
.mk-home-hero__stat-val{font-size:2rem;font-weight:900;color:#5de87a;line-height:1;}
.mk-home-hero__stat-label{font-size:.78rem;color:rgba(232,240,232,.5);margin-top:4px;text-transform:uppercase;letter-spacing:.07em;}
.mk-home-section{padding:56px 0;}
.mk-home-section--alt{background:rgba(0,0,0,.02);border-top:1px solid rgba(0,0,0,.06);border-bottom:1px solid rgba(0,0,0,.06);}
.mk-home-section .wrap{max-width:1100px;margin:0 auto;padding:0 24px;}
.mk-home-section__eyebrow{font-size:.68rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#2d6a1f;margin-bottom:8px;}
.mk-home-section__title{font-size:clamp(1.5rem,3vw,2rem);font-weight:900;margin:0 0 10px;color:#111;}
.mk-home-section__desc{color:#6b7280;max-width:64ch;line-height:1.7;margin:0 0 28px;}
.mk-home-subjects{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;}
.mk-home-subject{display:block;padding:16px;border:1px solid #e5e7eb;border-radius:14px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .15s,transform .15s;border-top:3px solid #2d6a1f;}
.mk-home-subject:hover{box-shadow:0 8px 24px rgba(0,0,0,.1);transform:translateY(-2px);}
.mk-home-subject__name{font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;}
.mk-home-subject__desc{font-size:.78rem;color:#6b7280;line-height:1.5;}
.mk-home-philosophy{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;}
.mk-home-phil-card{display:block;padding:22px;border:1px solid #e5e7eb;border-radius:14px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .15s,transform .15s;}
.mk-home-phil-card:hover{box-shadow:0 8px 28px rgba(0,0,0,.1);transform:translateY(-2px);}
.mk-home-phil-card__icon{font-size:1.8rem;margin-bottom:10px;}
.mk-home-phil-card__title{font-weight:900;font-size:1rem;color:#111;margin-bottom:6px;}
.mk-home-phil-card__claim{font-size:.8rem;color:#6b7280;line-height:1.6;font-style:italic;}
.mk-home-awag{display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:center;}
@media(max-width:640px){.mk-home-awag{grid-template-columns:1fr;}}
.mk-home-awag__text h3{font-size:1.4rem;font-weight:900;margin:0 0 12px;}
.mk-home-awag__text p{color:#6b7280;line-height:1.7;margin:0 0 20px;}
.mk-home-awag__communities{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;}
.mk-home-awag__pill{padding:4px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:20px;font-size:.78rem;font-weight:700;color:#166534;}
.mk-home-awag__visual{background:linear-gradient(135deg,#0a1a0a,#0f2d0f);border-radius:18px;padding:28px;color:#e8f0e8;text-align:center;}
.mk-home-awag__visual-title{font-size:1.2rem;font-weight:900;color:#5de87a;margin-bottom:8px;}
.mk-home-awag__market{display:flex;justify-content:center;gap:12px;margin-top:16px;}
.mk-home-awag__day{text-align:center;}
.mk-home-awag__day-emoji{font-size:1.4rem;display:block;margin-bottom:4px;}
.mk-home-awag__day-name{font-size:.72rem;font-weight:800;opacity:.7;}
.mk-home-libraries{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;}
.mk-home-library{padding:16px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;text-decoration:none;color:inherit;display:block;transition:box-shadow .12s;}
.mk-home-library:hover{box-shadow:0 4px 16px rgba(0,0,0,.08);}
.mk-home-library__name{font-weight:800;font-size:.9rem;color:#111;margin-bottom:4px;}
.mk-home-library__desc{font-size:.75rem;color:#6b7280;line-height:1.5;}
.mk-home-footer-cta{background:linear-gradient(135deg,#0a1a0a,#0f2d0f);color:#e8f0e8;padding:56px 0;text-align:center;}
.mk-home-footer-cta h2{font-size:clamp(1.6rem,3vw,2.4rem);font-weight:900;color:#fff;margin:0 0 14px;}
.mk-home-footer-cta p{color:rgba(232,240,232,.6);max-width:52ch;margin:0 auto 28px;line-height:1.7;}
</style>

<section class="mk-home-hero">
  <div class="wrap">
    <div class="mk-home-hero__eyebrow">Open Knowledge Platform</div>
    <h1 class="mk-home-hero__title">The knowledge of<br><em>the Igbo people</em><br>belongs to the world</h1>
    <p class="mk-home-hero__subtitle">Mkomigbo is a free, open knowledge platform documenting the history, culture, language, religion, and philosophy of the Igbo people of West Africa — and their connections to the broader African and global world.</p>
    <div class="mk-home-hero__cta">
      <a href="/subjects/" class="mk-home-hero__btn mk-home-hero__btn--primary">Explore All Subjects</a>
      <a href="/subjects/about/igbo_philosophy/" class="mk-home-hero__btn mk-home-hero__btn--ghost">Igbo Philosophy</a>
      <a href="/awag/" class="mk-home-hero__btn mk-home-hero__btn--ghost">AWAG Calendar</a>
    </div>
    <div class="mk-home-hero__stats">
      <div><div class="mk-home-hero__stat-val">20</div><div class="mk-home-hero__stat-label">Subject Areas</div></div>
      <div><div class="mk-home-hero__stat-val">137+</div><div class="mk-home-hero__stat-label">Pages of knowledge</div></div>
      <div><div class="mk-home-hero__stat-val">13</div><div class="mk-home-hero__stat-label">AWAG Communities</div></div>
      <div><div class="mk-home-hero__stat-val">Free</div><div class="mk-home-hero__stat-label">Always and forever</div></div>
    </div>
  </div>
</section>

<section class="mk-home-section">
  <div class="wrap">
    <div class="mk-home-section__eyebrow">Knowledge Library</div>
    <h2 class="mk-home-section__title">20 Subject Areas</h2>
    <p class="mk-home-section__desc">Each subject is documented at thesis level — not summaries, but arguments. Every page has sources, cross-links, and free download access to the texts it references.</p>
    <div class="mk-home-subjects">
      <?php
      $subjects=[['history','History','From Igbo-Ukwu (9th c.) to the present'],['slavery','Slavery','The Atlantic trade and its Igbo dimensions'],['people','People','Notable figures from Equiano to Adichie'],['persons','Persons','Biographical portraits in depth'],['culture','Culture','Kola, masquerade, festivals, values'],['religion','Religion','Odinala and world religious traditions'],['esoterism','Esoterism','Inner teachings across traditions'],['tradition','Tradition','Governance, masquerade, land, rites'],['language1','Language I','Igbo language, scripts, orthography'],['language2','Language II','Grammar, tones, usage'],['struggles','Struggles','Five centuries of Igbo resistance'],['biafra','Biafra','The Republic of Biafra 1967-1970'],['nigeria','Nigeria','From colonial creation to today'],['pogrom','Pogrom','The 1966 massacres and their legacy'],['resistance','Resistance','IPOB and self-determination'],['africa','Africa','Continental context and connections'],['uk','United Kingdom','Igbo and Nigerian diaspora in Britain'],['europe','Europe','African diaspora across Europe'],['arabs','Arabs','Arab-African historical relationships'],['about','About','What Mkomigbo is and stands for']];
      foreach($subjects as [$slug,$name,$desc]):?>
      <a href="/subjects/<?=htmlspecialchars($slug)?>/" class="mk-home-subject">
        <div class="mk-home-subject__name"><?=htmlspecialchars($name)?></div>
        <div class="mk-home-subject__desc"><?=htmlspecialchars($desc)?></div>
      </a>
      <?php endforeach;?>
    </div>
    <div style="margin-top:20px;"><a href="/subjects/" style="font-weight:800;color:#2d6a1f;text-decoration:none;">Browse all subjects with full descriptions</a></div>
  </div>
</section>

<section class="mk-home-section mk-home-section--alt">
  <div class="wrap">
    <div class="mk-home-section__eyebrow">Original Scholarship</div>
    <h2 class="mk-home-section__title">Igbo Philosophy</h2>
    <p class="mk-home-section__desc">Six original thesis pages constituting a complete Igbo metaphysics — assembled here for the first time.</p>
    <div class="mk-home-philosophy">
      <a href="/subjects/religion/quaternity/" class="mk-home-phil-card"><div class="mk-home-phil-card__icon">&#x2B21;</div><div class="mk-home-phil-card__title">Trinity vs Quaternity</div><div class="mk-home-phil-card__claim">Four is the number of completeness. The Fourth Wise Man, Odinala fourfold divine, and what the Trinity left out.</div></a>
      <a href="/subjects/religion/igbo_time/" class="mk-home-phil-card"><div class="mk-home-phil-card__icon">&#x1F319;</div><div class="mk-home-phil-card__title">Igbo Time</div><div class="mk-home-phil-card__claim">The Igbo count nights not days. This resolves the three nights of Jesus and reveals time as theology.</div></a>
      <a href="/subjects/religion/igbo_soul/" class="mk-home-phil-card"><div class="mk-home-phil-card__icon">&#x1F300;</div><div class="mk-home-phil-card__title">The Igbo Soul</div><div class="mk-home-phil-card__claim">Chi, ogbanje, ilo uwa — five elements of the person and reincarnation as homecoming not escape.</div></a>
      <a href="/subjects/tradition/ofo_ogu/" class="mk-home-phil-card"><div class="mk-home-phil-card__icon">&#x2696;&#xFE0F;</div><div class="mk-home-phil-card__title">Ofo na Ogu</div><div class="mk-home-phil-card__claim">Authority derives from moral integrity. The Igbo governed for millennia without a king.</div></a>
      <a href="/subjects/language1/scripts/" class="mk-home-phil-card"><div class="mk-home-phil-card__icon">&#x270D;&#xFE0F;</div><div class="mk-home-phil-card__title">Igbo Scripts</div><div class="mk-home-phil-card__claim">From Nsibidi to Ndebe — the full history of writing Igbo and why Roman script captures only 30%.</div></a>
      <a href="/subjects/esoterism/dibia/" class="mk-home-phil-card"><div class="mk-home-phil-card__icon">&#x1F33F;</div><div class="mk-home-phil-card__title">The Dibia</div><div class="mk-home-phil-card__claim">Agwu, the three types of dibia, and the philosophy of knowledge that cannot be separated from danger.</div></a>
    </div>
    <div style="margin-top:24px;"><a href="/subjects/about/igbo_philosophy/" style="font-weight:800;color:#2d6a1f;text-decoration:none;">View the complete Igbo Philosophy index</a></div>
  </div>
</section>

<section class="mk-home-section">
  <div class="wrap">
    <div class="mk-home-awag">
      <div class="mk-home-awag__text">
        <div class="mk-home-section__eyebrow">Community Calendar</div>
        <h3>AWAG — Africa Weekly Activities Guide</h3>
        <p>A practical seasonal calendar for 13 African communities across 9 ecological zones. Farming, fishing, trading, herding, healing, and community life calculated live from your local calendar system. Works offline. Installable on any device.</p>
        <div class="mk-home-awag__communities">
          <?php foreach(['Igbo','Amhara','Yoruba','Hausa','Ijaw','Bini','Efik','Ibibio','Tiv','Maasai','Swahili','Wolof','Oron'] as $c):?>
          <span class="mk-home-awag__pill"><?=htmlspecialchars($c)?></span>
          <?php endforeach;?>
        </div>
        <a href="/awag/" style="display:inline-flex;align-items:center;gap:8px;padding:12px 22px;background:#2d6a1f;color:#fff;border-radius:10px;font-weight:800;text-decoration:none;font-size:.9rem;">Open AWAG</a>
      </div>
      <div class="mk-home-awag__visual">
        <div class="mk-home-awag__visual-title">Igbo Market Week</div>
        <div style="font-size:.78rem;opacity:.6;margin-bottom:4px;">4-night cycle</div>
        <div class="mk-home-awag__market">
          <div class="mk-home-awag__day"><span class="mk-home-awag__day-emoji">&#x1F525;</span><div class="mk-home-awag__day-name" style="color:#ff6030;">Eke</div></div>
          <div class="mk-home-awag__day"><span class="mk-home-awag__day-emoji">&#x1F4A7;</span><div class="mk-home-awag__day-name" style="color:#3090ff;">Orie</div></div>
          <div class="mk-home-awag__day"><span class="mk-home-awag__day-emoji">&#x1F30D;</span><div class="mk-home-awag__day-name" style="color:#30a050;">Afo</div></div>
          <div class="mk-home-awag__day"><span class="mk-home-awag__day-emoji">&#x1F4A8;</span><div class="mk-home-awag__day-name" style="color:#a0c0ff;">Nkwo</div></div>
        </div>
        <div style="margin-top:20px;font-size:.75rem;opacity:.5;line-height:1.6;">13 communities · 9 ecological zones<br>Farming · Fishing · Trading · Healing</div>
      </div>
    </div>
  </div>
</section>

<section class="mk-home-section mk-home-section--alt">
  <div class="wrap">
    <div class="mk-home-section__eyebrow">Open Access</div>
    <h2 class="mk-home-section__title">Free Knowledge — Always</h2>
    <p class="mk-home-section__desc">Every sources page on Mkomigbo links directly to free downloads of the texts it references.</p>
    <div class="mk-home-libraries">
      <a href="https://archive.org" target="_blank" class="mk-home-library"><div class="mk-home-library__name">Internet Archive</div><div class="mk-home-library__desc">Millions of free books — search any author or title</div></a>
      <a href="https://www.sacred-texts.com" target="_blank" class="mk-home-library"><div class="mk-home-library__name">Sacred Texts</div><div class="mk-home-library__desc">The definitive free library of sacred and esoteric texts</div></a>
      <a href="https://www.globalgreyebooks.com" target="_blank" class="mk-home-library"><div class="mk-home-library__name">Global Grey Ebooks</div><div class="mk-home-library__desc">Beautifully formatted free PDF and ePub classics</div></a>
      <a href="https://www.gutenberg.org" target="_blank" class="mk-home-library"><div class="mk-home-library__name">Project Gutenberg</div><div class="mk-home-library__desc">Free public domain books including rare texts</div></a>
      <a href="https://www.slavevoyages.org" target="_blank" class="mk-home-library"><div class="mk-home-library__name">Slave Voyages</div><div class="mk-home-library__desc">Complete database of the transatlantic slave trade</div></a>
      <a href="https://www.sefaria.org" target="_blank" class="mk-home-library"><div class="mk-home-library__name">Sefaria</div><div class="mk-home-library__desc">Jewish texts — Torah, Talmud, Kabbalah in Hebrew and English</div></a>
    </div>
  </div>
</section>

<section class="mk-home-footer-cta">
  <div style="max-width:1100px;margin:0 auto;padding:0 24px;">
    <h2>Knowledge belongs to everyone</h2>
    <p>Mkomigbo is free, open, and built to last. Every page, every source, every argument — available to anyone who seeks it.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="/subjects/" class="mk-home-hero__btn mk-home-hero__btn--primary">Explore the Library</a>
      <a href="/subjects/about/intro/" class="mk-home-hero__btn mk-home-hero__btn--ghost">About Mkomigbo</a>
    </div>
  </div>
</section>

<?php
if(function_exists('mk_require_shared')){try{mk_require_shared('public_footer.php');}catch(Throwable $e){echo '</body></html>';}}else{echo '</body></html>';}
?>
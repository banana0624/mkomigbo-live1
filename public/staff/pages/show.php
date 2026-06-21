<?php
// --- Attachments (Professional block) ---------------------------------
require_once __DIR__ . "/../../../private/functions/attachments_engine.php";

require_once __DIR__ . '/../../_init.php';

auth_require_role('staff');

$e = mk_attachments_engine();
$att = $e->load_for_subject_page((int)($page['subject_id'] ?? 0), (int)($page['id'] ?? 0));

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function human_bytes(int $bytes): string {
    if ($bytes < 1024) return $bytes . " B";
    $units = ["KB","MB","GB","TB"];
    $v = $bytes / 1024;
    foreach ($units as $u) {
        if ($v < 1024) return rtrim(rtrim(number_format($v, 2), '0'), '.') . " " . $u;
        $v /= 1024;
    }
    return rtrim(rtrim(number_format($v, 2), '0'), '.') . " PB";
}

function badge_kind(array $a): string {
    $k = (string)($a['kind'] ?? '');
    $m = (string)($a['mime_type'] ?? '');
    if ($k !== '') return strtoupper($k);
    if (str_starts_with($m, 'image/')) return "IMAGE";
    if (str_starts_with($m, 'video/')) return "VIDEO";
    if (str_starts_with($m, 'audio/')) return "AUDIO";
    if ($m === 'application/pdf') return "PDF";
    return "FILE";
}

function best_open_url(array $a): string {
    $href = (string)($a['href'] ?? '');
    $canon = trim((string)($a['canonical_url'] ?? ''));
    $is_external = !empty($a['is_external']);
    if ($is_external && $canon !== '') return $canon;
    return $href !== '' ? $href : '#';
}
?>

<section class="mk-card mk-card--attachments">
  <div class="mk-card__head">
    <h3 class="mk-card__title">Attachments</h3>
    <div class="mk-card__meta">
      <span class="mk-pill"><?= count($att) ?></span>
    </div>
  </div>

  <?php if (empty($att)): ?>
    <div class="mk-empty">No attachments for this page yet.</div>
  <?php else: ?>
    <div class="mk-attachments">
      <?php foreach ($att as $a): ?>
        <?php
          $open = best_open_url($a);
          $label = (string)($a['label'] ?? $a['original_name'] ?? 'Attachment');
          $src_label = (string)($a['source_label'] ?? ($a['source_key'] ?? ''));
          $is_external = !empty($a['is_external']);
          $host = trim((string)($a['external_host'] ?? $a['host'] ?? ''));
          $authors = trim((string)($a['authors'] ?? ''));
          $year = trim((string)($a['pub_year'] ?? ''));
          $doi = trim((string)($a['doi'] ?? ''));
          $isbn = trim((string)($a['isbn'] ?? ''));
          $lang = trim((string)($a['lang'] ?? ''));
          $size = (int)($a['file_size'] ?? 0);
          $created = trim((string)($a['created_at'] ?? ''));
          $kind = badge_kind($a);
        ?>

        <article class="mk-att">
          <div class="mk-att__top">
            <div class="mk-att__left">
              <div class="mk-att__title">
                <a class="mk-att__link"
                   href="<?= h($open) ?>"
                   <?= $is_external ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                  <?= h($label) ?>
                </a>
              </div>

              <div class="mk-att__sub">
                <span class="mk-tag"><?= h($kind) ?></span>
                <?php if ($src_label !== ''): ?><span class="mk-dot">•</span><span class="mk-muted"><?= h($src_label) ?></span><?php endif; ?>
                <?php if ($host !== ''): ?><span class="mk-dot">•</span><span class="mk-muted"><?= h($host) ?></span><?php endif; ?>
                <?php if ($size > 0): ?><span class="mk-dot">•</span><span class="mk-muted"><?= h(human_bytes($size)) ?></span><?php endif; ?>
              </div>
            </div>

            <div class="mk-att__right">
              <?php if (!$is_external && !empty($a['href'])): ?>
                <a class="mk-btn mk-btn--ghost" href="<?= h((string)$a['href']) ?>">Open</a>
              <?php else: ?>
                <a class="mk-btn mk-btn--ghost" href="<?= h($open) ?>" target="_blank" rel="noopener noreferrer">Open</a>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($authors || $year || $doi || $isbn || $lang): ?>
            <div class="mk-att__meta">
              <?php if ($authors): ?><div><span class="mk-muted">Authors:</span> <?= h($authors) ?></div><?php endif; ?>
              <?php if ($year): ?><div><span class="mk-muted">Year:</span> <?= h($year) ?></div><?php endif; ?>
              <?php if ($doi): ?><div><span class="mk-muted">DOI:</span> <?= h($doi) ?></div><?php endif; ?>
              <?php if ($isbn): ?><div><span class="mk-muted">ISBN:</span> <?= h($isbn) ?></div><?php endif; ?>
              <?php if ($lang): ?><div><span class="mk-muted">Language:</span> <?= h($lang) ?></div><?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($created): ?>
            <div class="mk-att__foot">
              <span class="mk-muted">Added:</span> <?= h($created) ?>
            </div>
          <?php endif; ?>
        </article>

      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
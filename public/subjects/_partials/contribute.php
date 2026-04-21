<?php
declare(strict_types=1);

// Safe context
$subject = isset($subject_slug) ? basename((string)$subject_slug) : '';
$page    = isset($page_slug) ? basename((string)$page_slug) : '';
?>

<div class="mk-card" style="margin-top:16px;">
  <div class="mk-card__body">

    <h3 style="margin-top:0;">Contribute to this page</h3>

    <p class="mk-muted">
      Suggest improvements, corrections, or additions to improve this content.
    </p>

    <!-- Button -->
    <div style="margin-top:10px;">
      <a class="mk-btn mk-btn--primary"
         href="<?= h(mk_u('/contribute/?subject=' . rawurlencode($subject) . '&page=' . rawurlencode($page))) ?>">
        Submit contribution
      </a>
    </div>

  </div>
</div>
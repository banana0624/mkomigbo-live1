<?php
declare(strict_types=1);
/**
 * /public/subjects/_partials/contribute.php
 *
 * Visitor input section — rendered at the bottom of every subject page.
 *
 * Context variables available (set by page.php):
 *   $subject_slug  (string)
 *   $page_slug     (string)
 *
 * Uses:
 *   - submit_comment.php  (POST endpoint — already handles CSRF, rate limit, file upload)
 *   - mk_registry_subjects_by_slug()  (slug → integer subject_id)
 *   - App\Core\Csrf::token()          (CSRF token generation)
 *   - comments_list()                 (display approved comments)
 */

/* -------------------------------------------------------
   1. Resolve subject_id and page_id from slugs
------------------------------------------------------- */
$_contrib_subject_slug = isset($subject_slug) ? strtolower(trim(basename((string)$subject_slug))) : '';
$_contrib_page_slug    = isset($page_slug)    ? strtolower(trim(basename((string)$page_slug)))    : '';

$_contrib_subject_id = 0;
$_contrib_page_id    = 0;

// Slug → integer subject_id via registry
if ($_contrib_subject_slug !== '' && function_exists('mk_registry_subjects_by_slug')) {
    $_contrib_registry = mk_registry_subjects_by_slug();
    if (isset($_contrib_registry[$_contrib_subject_slug])) {
        $_contrib_subject_id = (int)($_contrib_registry[$_contrib_subject_slug]['id'] ?? 0);
    }
}

// Page slug → integer page_id (stable positional map)
$_contrib_page_map = [
    'intro'    => 1,
    'overview' => 2,
    'topics'   => 3,
    'people'   => 4,
    'sources'  => 5,
    // extended pages get IDs above 5
    'timeline'         => 6,
    'profiles'         => 7,
    'african'          => 8,
    'eastern'          => 9,
    'abrahamic'        => 10,
    'doctrine'         => 11,
    'african_doctrine' => 12,
    'eastern_doctrine' => 13,
    'ancient_doctrine' => 14,
    'modern_doctrine'  => 15,
    'modern'           => 16,
    'metempsychosis'   => 17,
];
if ($_contrib_page_slug !== '' && isset($_contrib_page_map[$_contrib_page_slug])) {
    $_contrib_page_id = $_contrib_page_map[$_contrib_page_slug];
}

// If IDs could not be resolved, render nothing — never expose broken forms
if ($_contrib_subject_id <= 0 || $_contrib_page_id <= 0) {
    return;
}

/* -------------------------------------------------------
   2. CSRF token
------------------------------------------------------- */
$_contrib_csrf = '';
if (class_exists('App\\Core\\Csrf')) {
    $_contrib_csrf = \App\Core\Csrf::token();
} elseif (function_exists('csrf_token')) {
    $_contrib_csrf = (string)csrf_token();
} elseif (isset($_SESSION['csrf_token'])) {
    $_contrib_csrf = (string)$_SESSION['csrf_token'];
} else {
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $_contrib_csrf = $_SESSION['csrf_token'];
    }
}

/* -------------------------------------------------------
   3. Notice from redirect
------------------------------------------------------- */
$_contrib_notice = isset($_GET['notice']) ? (string)$_GET['notice'] : '';

/* -------------------------------------------------------
   4. Return URL (current page path, no query string)
------------------------------------------------------- */
$_contrib_return = '/subjects/' . rawurlencode($_contrib_subject_slug)
                 . '/' . rawurlencode($_contrib_page_slug) . '/';

/* -------------------------------------------------------
   5. Load approved comments for this page
------------------------------------------------------- */
$_contrib_comments = [];
if (function_exists('comments_list')) {
    try {
        $_contrib_comments = comments_list(
            (string)$_contrib_subject_id,
            (string)$_contrib_page_id,
            'approved'
        );
    } catch (Throwable $_e) {
        $_contrib_comments = [];
    }
}

/* -------------------------------------------------------
   6. h() safety guard
------------------------------------------------------- */
if (!function_exists('_ch')) {
    function _ch(string $v): string {
        return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

?>
<div id="comments" style="margin-top:32px;">

  <?php /* ---- Notice bar ---- */ ?>
  <?php if ($_contrib_notice === 'pending'): ?>
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
      <span style="font-size:1.2rem;">✅</span>
      <span><strong>Thank you.</strong> Your contribution has been received and is awaiting review. It will appear here once approved.</span>
    </div>
  <?php elseif ($_contrib_notice === 'error'): ?>
    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
      <span style="font-size:1.2rem;">⚠️</span>
      <span><strong>Something went wrong.</strong> Please try again. If the problem persists, try refreshing the page.</span>
    </div>
  <?php elseif ($_contrib_notice === 'invalid'): ?>
    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
      <span style="font-size:1.2rem;">⚠️</span>
      <span><strong>Invalid submission.</strong> Please check your entry and try again. Comments must be between 2 and 2,000 characters.</span>
    </div>
  <?php elseif ($_contrib_notice === 'rate'): ?>
    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
      <span style="font-size:1.2rem;">🕐</span>
      <span><strong>Too many submissions.</strong> Please wait a few minutes before trying again.</span>
    </div>
  <?php endif; ?>

  <?php /* ---- Approved comments ---- */ ?>
  <?php if (!empty($_contrib_comments)): ?>
    <div style="margin-bottom:28px;">
      <h3 style="font-size:1rem;font-weight:700;color:#374151;margin-bottom:14px;letter-spacing:.02em;">
        CONTRIBUTIONS (<?= count($_contrib_comments) ?>)
      </h3>
      <?php foreach ($_contrib_comments as $_c): ?>
        <?php
          $_c_name = trim((string)($_c['author_name'] ?? $_c['name'] ?? ''));
          $_c_body = trim((string)($_c['body'] ?? $_c['content'] ?? ''));
          $_c_date = trim((string)($_c['created_at'] ?? ''));
          $_c_date_fmt = '';
          if ($_c_date !== '') {
              try {
                  $_c_date_fmt = (new DateTime($_c_date))->format('j M Y');
              } catch (Throwable $_ex) {
                  $_c_date_fmt = substr($_c_date, 0, 10);
              }
          }
          if ($_c_body === '') continue;
        ?>
        <div style="border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;margin-bottom:12px;background:#fafafa;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <div style="width:32px;height:32px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-weight:700;color:#6b7280;font-size:.85rem;flex-shrink:0;">
              <?= _ch($_c_name !== '' ? mb_strtoupper(mb_substr($_c_name, 0, 1, 'UTF-8'), 'UTF-8') : '?') ?>
            </div>
            <div>
              <span style="font-weight:700;color:#111;font-size:.9rem;">
                <?= _ch($_c_name !== '' ? $_c_name : 'Anonymous') ?>
              </span>
              <?php if ($_c_date_fmt !== ''): ?>
                <span style="color:#9ca3af;font-size:.8rem;margin-left:8px;"><?= _ch($_c_date_fmt) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div style="color:#374151;font-size:.92rem;line-height:1.65;white-space:pre-wrap;"><?= _ch($_c_body) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php /* ---- Submission form ---- */ ?>
  <div style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">

    <div style="background:#f9fafb;border-bottom:1px solid #e5e7eb;padding:16px 20px;">
      <h3 style="margin:0;font-size:1rem;font-weight:700;color:#111;">Add your contribution</h3>
      <p style="margin:4px 0 0;font-size:.85rem;color:#6b7280;">
        Corrections, additions, sources, or context. All submissions are reviewed before publication.
        Accepted file types: PDF, JPG, PNG, WEBP, TXT — max 5 MB each, up to 3 files.
      </p>
    </div>

    <div style="padding:20px;">
      <form
        method="POST"
        action="<?= _ch('/subjects/submit_comment.php') ?>"
        enctype="multipart/form-data"
        autocomplete="off"
        style="display:flex;flex-direction:column;gap:14px;"
      >
        <?php /* Hidden fields */ ?>
        <input type="hidden" name="csrf"       value="<?= _ch($_contrib_csrf) ?>">
        <input type="hidden" name="subject_id" value="<?= _ch((string)$_contrib_subject_id) ?>">
        <input type="hidden" name="page_id"    value="<?= _ch((string)$_contrib_page_id) ?>">
        <input type="hidden" name="return"     value="<?= _ch($_contrib_return) ?>">

        <?php /* Honeypot — hidden from real users, catches bots */ ?>
        <div style="position:absolute;left:-9999px;top:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">
          <input type="text" name="website" value="" tabindex="-1" autocomplete="off">
        </div>

        <?php /* Name */ ?>
        <div>
          <label for="contrib-name" style="display:block;font-size:.85rem;font-weight:600;color:#374151;margin-bottom:5px;">
            Your name <span style="color:#9ca3af;font-weight:400;">(optional)</span>
          </label>
          <input
            type="text"
            id="contrib-name"
            name="name"
            maxlength="80"
            placeholder="How you would like to be credited"
            style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:7px;font-size:.9rem;color:#111;background:#fff;box-sizing:border-box;"
          >
        </div>

        <?php /* Body */ ?>
        <div>
          <label for="contrib-body" style="display:block;font-size:.85rem;font-weight:600;color:#374151;margin-bottom:5px;">
            Your contribution <span style="color:#dc2626;">*</span>
          </label>
          <textarea
            id="contrib-body"
            name="body"
            required
            minlength="2"
            maxlength="2000"
            rows="5"
            placeholder="Write your correction, addition, source reference, or context here…"
            style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:7px;font-size:.9rem;color:#111;background:#fff;resize:vertical;box-sizing:border-box;font-family:inherit;line-height:1.5;"
          ></textarea>
          <div style="text-align:right;font-size:.78rem;color:#9ca3af;margin-top:3px;">Max 2,000 characters</div>
        </div>

        <?php /* File upload */ ?>
        <div>
          <label for="contrib-files" style="display:block;font-size:.85rem;font-weight:600;color:#374151;margin-bottom:5px;">
            Attach files <span style="color:#9ca3af;font-weight:400;">(optional)</span>
          </label>
          <input
            type="file"
            id="contrib-files"
            name="attachments[]"
            multiple
            accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,application/pdf,image/jpeg,image/png,image/webp,text/plain"
            style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:7px;font-size:.85rem;background:#fff;box-sizing:border-box;cursor:pointer;"
          >
          <div style="font-size:.78rem;color:#9ca3af;margin-top:3px;">
            PDF, JPG, PNG, WEBP, TXT · Max 5 MB per file · Up to 3 files
          </div>
        </div>

        <?php /* Submit */ ?>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding-top:4px;">
          <span style="font-size:.8rem;color:#9ca3af;">
            Submissions are moderated. Your IP address is logged for security.
          </span>
          <button
            type="submit"
            style="background:#111;color:#fff;border:none;border-radius:7px;padding:10px 24px;font-size:.9rem;font-weight:600;cursor:pointer;letter-spacing:.01em;transition:background .15s;"
            onmouseover="this.style.background='#333'"
            onmouseout="this.style.background='#111'"
          >
            Submit contribution
          </button>
        </div>

      </form>
    </div>
  </div>

</div>
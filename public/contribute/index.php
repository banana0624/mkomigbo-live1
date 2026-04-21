<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

if (function_exists('mk__session_start')) {
    mk__session_start();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

require_once APP_ROOT . '/private/functions/contributions.php';

if (!function_exists('h')) {
    function h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mk_contrib_csrf_token')) {
    function mk_contrib_csrf_token(): string
    {
        if (empty($_SESSION['contrib_csrf_token']) || !is_string($_SESSION['contrib_csrf_token'])) {
            $_SESSION['contrib_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['contrib_csrf_token'];
    }
}

if (!function_exists('mk_contrib_flash_get')) {
    function mk_contrib_flash_get(string $key, $default = null)
    {
        if (!array_key_exists($key, $_SESSION)) {
            return $default;
        }
        $value = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $value;
    }
}

$old = mk_contrib_flash_get('contrib_old', []);
$errors = mk_contrib_flash_get('contrib_errors', []);
$success = mk_contrib_flash_get('contrib_success', '');

$subject = isset($_GET['subject']) ? basename((string)$_GET['subject']) : 'history';
$page    = isset($_GET['page']) ? basename((string)$_GET['page']) : 'intro';

$pagePathDefault = '/subjects/' . $subject . '/' . $page . '/';

if (!is_array($old)) {
    $old = [];
}
if (!is_array($errors)) {
    $errors = [];
}
if (!is_string($success)) {
    $success = '';
}

function oldv(array $old, string $key, string $default = ''): string
{
    $v = $old[$key] ?? $default;
    return is_string($v) ? $v : $default;
}

$subjectAreas = mk_contribution_subject_areas();
$submissionTypes = mk_contribution_submission_types();
$positionTypes = mk_contribution_position_types();

$pageTitle = 'Contribute to this platform';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= h($pageTitle) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="mk-shell" style="max-width:900px; margin:30px auto; padding:0 16px;">
  <div class="mk-prose">
    <h1><?= h($pageTitle) ?></h1>

    <p>
      Use this page to submit corrections, criticism, additions, questions, source material, and supporting files.
      All submissions are reviewed before publication.
    </p>

    <?php if ($success !== ''): ?>
      <div style="margin:16px 0; padding:12px 14px; border:1px solid #b9dec3; border-radius:10px; background:#f4fbf5; color:#1f5d2f;">
        <?= h($success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
      <div style="margin:16px 0; padding:12px 14px; border:1px solid #e2b4b4; border-radius:10px; background:#fff6f6; color:#8a1f1f;">
        <?= h((string)$errors['general']) ?>
      </div>
    <?php endif; ?>

    <form action="/contribute/submit.php" method="post" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf_token" value="<?= h(mk_contrib_csrf_token()) ?>">

      <div style="position:absolute; left:-9999px; top:auto; width:1px; height:1px; overflow:hidden;">
        <label for="website">Website</label>
        <input type="text" id="website" name="website" value="">
      </div>

      <fieldset style="border:1px solid #ddd; border-radius:12px; padding:16px; margin:0 0 16px;">
        <legend><strong>Your details</strong></legend>

        <div style="margin-bottom:12px;">
          <label for="contributor_name"><strong>Name</strong></label><br>
          <input id="contributor_name" name="contributor_name" type="text" maxlength="150" required
                 value="<?= h(oldv($old, 'contributor_name')) ?>"
                 style="width:100%; max-width:560px; padding:10px;">
          <?php if (!empty($errors['contributor_name'])): ?>
            <div style="color:#8a1f1f; margin-top:6px;"><?= h((string)$errors['contributor_name']) ?></div>
          <?php endif; ?>
        </div>

        <div>
          <label for="contributor_email"><strong>Email</strong></label><br>
          <input id="contributor_email" name="contributor_email" type="email" maxlength="190" required
                 value="<?= h(oldv($old, 'contributor_email')) ?>"
                 style="width:100%; max-width:560px; padding:10px;">
          <?php if (!empty($errors['contributor_email'])): ?>
            <div style="color:#8a1f1f; margin-top:6px;"><?= h((string)$errors['contributor_email']) ?></div>
          <?php endif; ?>
        </div>
      </fieldset>

      <fieldset style="border:1px solid #ddd; border-radius:12px; padding:16px; margin:0 0 16px;">
        <legend><strong>What are you submitting?</strong></legend>

        <div style="margin-bottom:12px;">
          <label for="subject_area"><strong>Subject area</strong></label><br>
          <select id="subject_area" name="subject_area" required style="width:100%; max-width:320px; padding:10px;">
            <option value="">Select subject area</option>
            <?php foreach ($subjectAreas as $item): ?>
              <option value="<?= h($item) ?>" <?= oldv($old, 'subject_area') === $item ? 'selected' : '' ?>><?= h(ucfirst($item)) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['subject_area'])): ?>
            <div style="color:#8a1f1f; margin-top:6px;"><?= h((string)$errors['subject_area']) ?></div>
          <?php endif; ?>
        </div>

        <div style="margin-bottom:12px;">
          <label for="page_path"><strong>Page or topic path</strong></label><br>
          <input id="page_path" name="page_path" type="text" maxlength="255" required
                 value="<?= h(oldv($old, 'page_path', $pagePathDefault)) ?>"
                 placeholder="/subjects/history/intro/"
                 style="width:100%; max-width:560px; padding:10px;">
          <?php if (!empty($errors['page_path'])): ?>
            <div style="color:#8a1f1f; margin-top:6px;"><?= h((string)$errors['page_path']) ?></div>
          <?php endif; ?>
        </div>

        <div style="margin-bottom:12px;">
          <label for="submission_type"><strong>Submission type</strong></label><br>
          <select id="submission_type" name="submission_type" required style="width:100%; max-width:320px; padding:10px;">
            <option value="">Select submission type</option>
            <?php foreach ($submissionTypes as $item): ?>
              <option value="<?= h($item) ?>" <?= oldv($old, 'submission_type') === $item ? 'selected' : '' ?>><?= h(str_replace('_', ' ', ucfirst($item))) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['submission_type'])): ?>
            <div style="color:#8a1f1f; margin-top:6px;"><?= h((string)$errors['submission_type']) ?></div>
          <?php endif; ?>
        </div>

        <div>
          <label for="position_type"><strong>Position</strong> <span style="color:#666;">(optional)</span></label><br>
          <select id="position_type" name="position_type" style="width:100%; max-width:320px; padding:10px;">
            <option value="">Select position</option>
            <?php foreach ($positionTypes as $item): ?>
              <option value="<?= h($item) ?>" <?= oldv($old, 'position_type') === $item ? 'selected' : '' ?>><?= h(ucfirst($item)) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['position_type'])): ?>
            <div style="color:#8a1f1f; margin-top:6px;"><?= h((string)$errors['position_type']) ?></div>
          <?php endif; ?>
        </div>
      </fieldset>

      <fieldset style="border:1px solid #ddd; border-radius:12px; padding:16px; margin:0 0 16px;">
        <legend><strong>Your message</strong></legend>

        <div style="margin-bottom:12px;">
          <label for="title"><strong>Title</strong></label><br>
          <input id="title" name="title" type="text" maxlength="220" required
                 value="<?= h(oldv($old, 'title')) ?>"
                 style="width:100%; max-width:700px; padding:10px;">
          <?php if (!empty($errors['title'])): ?>
            <div style="color:#8a1f1f; margin-top:6px;"><?= h((string)$errors['title']) ?></div>
          <?php endif; ?>
        </div>

        <div>
          <label for="message_text"><strong>Message</strong></label><br>
          <textarea id="message_text" name="message_text" rows="10" required
                    style="width:100%; max-width:760px; padding:10px;"><?= h(oldv($old, 'message_text')) ?></textarea>
          <?php if (!empty($errors['message_text'])): ?>
            <div style="color:#8a1f1f; margin-top:6px;"><?= h((string)$errors['message_text']) ?></div>
          <?php endif; ?>
        </div>
      </fieldset>

      <fieldset style="border:1px solid #ddd; border-radius:12px; padding:16px; margin:0 0 16px;">
        <legend><strong>Optional attachment</strong></legend>

        <p style="margin-top:0; color:#555;">
          Allowed: PDF, DOC, DOCX, TXT, RTF, JPG, JPEG, PNG, WEBP, MP3, WAV, MP4, ZIP. Maximum size: 10 MB.
        </p>

        <input id="attachment" name="attachment" type="file">
        <?php if (!empty($errors['attachment'])): ?>
          <div style="color:#8a1f1f; margin-top:6px;"><?= h((string)$errors['attachment']) ?></div>
        <?php endif; ?>
      </fieldset>

      <fieldset style="border:1px solid #ddd; border-radius:12px; padding:16px; margin:0 0 16px;">
        <legend><strong>Consent</strong></legend>

        <label>
          <input type="checkbox" name="consent" value="1" <?= oldv($old, 'consent') === '1' ? 'checked' : '' ?>>
          I confirm that this submission is mine to share, does not knowingly violate another person’s rights,
          and may be reviewed, edited, or declined before publication.
        </label>
        <?php if (!empty($errors['consent'])): ?>
          <div style="color:#8a1f1f; margin-top:6px;"><?= h((string)$errors['consent']) ?></div>
        <?php endif; ?>
      </fieldset>

      <div style="margin:18px 0 30px;">
        <button type="submit" style="padding:12px 18px;">Submit contribution</button>
      </div>
      
      <input type="hidden" name="subject" value="<?= h($subject) ?>">
      <input type="hidden" name="page" value="<?= h($page) ?>">
      
    </form>
  </div>
</div>
</body>
</html>
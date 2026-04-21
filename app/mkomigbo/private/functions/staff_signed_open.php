<?php
declare(strict_types=1);

function mk_staff_signed_open_url(int $fileId, int $pageId, int $ttlSeconds = 900): string {
  if (!defined('MK_ENABLE_SIGNED_OPEN') || MK_ENABLE_SIGNED_OPEN !== true) {
    return '/staff/page-files/open.php?page_id=' . $pageId . '&file_id=' . $fileId;
  }
  if (!defined('MK_SIGNED_OPEN_SECRET') || !is_string(MK_SIGNED_OPEN_SECRET) || MK_SIGNED_OPEN_SECRET === '') {
    return '/staff/page-files/open.php?page_id=' . $pageId . '&file_id=' . $fileId;
  }

  $exp = time() + max(60, $ttlSeconds); // minimum 60s
  $msg = $fileId . '|' . $pageId . '|' . $exp;
  $sig = hash_hmac('sha256', $msg, MK_SIGNED_OPEN_SECRET);

  return '/staff/page-files/open.php?page_id=' . $pageId
       . '&file_id=' . $fileId
       . '&exp=' . $exp
       . '&sig=' . $sig;
}

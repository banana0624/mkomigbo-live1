<?php
declare(strict_types=1);

// Signed open links (GET) for attachments
if (!defined('MK_ENABLE_SIGNED_OPEN')) {
  define('MK_ENABLE_SIGNED_OPEN', true);
}
if (!defined('MK_SIGNED_OPEN_SECRET')) {
  // Replace with your real long random secret (keep private)
  define('MK_SIGNED_OPEN_SECRET', 'CHANGE_ME_TO_A_LONG_RANDOM_SECRET');
}

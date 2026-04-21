<?php
declare(strict_types=1);

/**
 * /private/shared/public_footer.php
 * Shared footer wrapper.
 *
 * Contract:
 * - public_header.php opens: <div class="mk-main">
 * - public_footer.php closes it exactly once, then prints footer + closes body/html.
 *
 * Optional variables:
 * - $page_scripts (string) raw HTML scripts (set before including footer)
 * - $footer_variant (string) e.g. 'Public' (default) or 'Staff' or 'Subjects'
 */

if (defined('MK_PUBLIC_FOOTER_INCLUDED')) {
  $dbg = defined('APP_DEBUG') ? (bool)APP_DEBUG : false;
  if ($dbg) {
    throw new RuntimeException('public_footer.php included twice');
  }
  return;
}
define('MK_PUBLIC_FOOTER_INCLUDED', true);

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

$year = (int)date('Y');

$footer_variant = (isset($footer_variant) && is_string($footer_variant) && trim($footer_variant) !== '')
  ? trim($footer_variant)
  : 'Public';

/**
 * Close wrapper exactly once.
 * Primary signal: $GLOBALS['mk__main_open'] === true
 * Fallback: if header ran but the flag was not set, close once (guarded by constant).
 */
$mainClosed = defined('MK_PUBLIC_MAIN_CLOSED');

if (!$mainClosed) {

  if (isset($GLOBALS['mk__main_open']) && $GLOBALS['mk__main_open'] === true) {
    echo "</div>\n";
    $GLOBALS['mk__main_open'] = false;
    define('MK_PUBLIC_MAIN_CLOSED', true);

  } elseif (defined('MK_PUBLIC_HEADER_INCLUDED')) {
    // Header ran; close the wrapper once even if the flag wasn't set.
    echo "</div>\n";
    define('MK_PUBLIC_MAIN_CLOSED', true);
  }
}
?>

<footer class="site-footer">
  <div class="container footer-row">
    <div class="footer-left">
      <small>&copy; <?= $year ?> Mkomigbo</small>
    </div>
    <div class="footer-right">
      <small class="muted">Built with care • <?= h($footer_variant) ?></small>
    </div>
  </div>
</footer>

<?php
// Optional page-level scripts (raw HTML, intentional)
if (isset($page_scripts) && is_string($page_scripts) && trim($page_scripts) !== '') {
  echo $page_scripts;
}
?>

</body>
</html>

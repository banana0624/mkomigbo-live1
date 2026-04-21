<?php
declare(strict_types=1);

/**
 * /private/shared/staff_footer.php
 * Shared staff shell footer
 */

if (!empty($GLOBALS['mk__main_open'])) {
  echo "</main>\n";
  $GLOBALS['mk__main_open'] = false;
}
?>
  <footer style="padding:0 18px 28px;">
    <div style="max-width:1180px;margin:0 auto;border-top:1px solid rgba(17,24,39,.10);padding-top:14px;color:#667085;font-size:.92rem;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <div>Staff workspace • Mkomi Igbo</div>
      <div>Secure internal tools and publishing workflow</div>
    </div>
  </footer>
</div>
</body>
</html>
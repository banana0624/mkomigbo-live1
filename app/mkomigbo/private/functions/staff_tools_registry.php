<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/functions/staff_tools_registry.php
 * Registry (allowlist) for /staff/tools/run.php
 */

if (!function_exists('mk_staff_tools_registry')) {

  function mk_staff_tools_registry(): array
  {
    $appRoot   = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
    $toolsBase = ($appRoot !== '') ? ($appRoot . '/private/tools') : '';

    $mk = static function (
      string $key,
      string $title,
      string $desc,
      string $group,
      string $minRole,
      string $relPath,
      bool $mutating = false
    ) use ($toolsBase): array {

      $relPath = ltrim($relPath, "/\\");
      $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));

      // HARD POLICY: PHP only
      if ($ext !== 'php') return [];

      $abs = ($toolsBase !== '') ? ($toolsBase . '/' . $relPath) : '';

      return [
        'key'      => $key,
        'title'    => $title,
        'desc'     => $desc,
        'group'    => $group,
        'min_role' => $minRole,
        'rel_path' => $relPath,
        'abs_path' => $abs,     // runner may ignore; harmless
        'mutating' => $mutating // runner may enforce for owner-only, etc.
      ];
    };

    $tools = [];

    /* =========================================================
       DIAGNOSTICS
       ========================================================= */

    $tools[] = $mk(
      'diagnostics/diag',
      'Diagnostics Overview',
      'General diagnostics entrypoint (read-only health snapshot).',
      'Diagnostics',
      'admin',
      'diag.php'
    );

    $tools[] = $mk(
      'diagnostics/project-audit',
      'Project Audit',
      'Scans project layout + configuration for common hazards and miswires.',
      'Diagnostics',
      'admin',
      'project_audit.php'
    );

    $tools[] = $mk(
      'diagnostics/subjects',
      'Subjects Diagnostics',
      'Checks subjects/pages wiring and common routing/data issues.',
      'Diagnostics',
      'admin',
      'subjects_diag.php'
    );

    $tools[] = $mk(
      'diagnostics/staff-tools/diagnostics',
      'Staff Tools Diagnostics',
      'Diagnostics focused on staff area wiring and internal tool health.',
      'Diagnostics',
      'admin',
      'staff-tools/diagnostics.php'
    );

    $tools[] = $mk(
      'diagnostics/staff-tools/scan',
      'Scan (Staff Tools)',
      'Performs a safe scan of staff tools / environment checks.',
      'Diagnostics',
      'admin',
      'staff-tools/scan.php'
    );

    $tools[] = $mk(
      'diagnostics/staff-tools/scan-project',
      'Scan Project (Staff Tools)',
      'Project-wide scan (read-only) through the staff tools suite.',
      'Diagnostics',
      'admin',
      'staff-tools/scan_project.php'
    );

    // ✅ NEW: Attachments Integrity Scan
    $tools[] = $mk(
      'diagnostics/attachments/integrity-scan',
      'Attachments Integrity Scan',
      'Checks DB <-> disk consistency for page_files, and validates external URLs.',
      'Diagnostics',
      'admin',
      'diagnostics/attachments_integrity_scan.php'
    );

    /* -------------------------
       Lint & Validation
    ------------------------- */

    $tools[] = $mk(
      'diagnostics/lint/quick-scan',
      'Lint Quick Scan',
      'Fast scan for common HTML/CSS/JS/PHP/SQL issues and suspicious patterns.',
      'Diagnostics',
      'admin',
      'lint/quick_scan.php'
    );

    $tools[] = $mk(
      'diagnostics/lint/php-syntax',
      'PHP Syntax Check',
      'Runs php -l across the project (safe, read-only).',
      'Diagnostics',
      'admin',
      'lint/php_syntax.php'
    );

    $tools[] = $mk(
      'diagnostics/lint/html-report',
      'HTML Report (Basic)',
      'Finds broken tags/patterns in HTML templates (basic heuristic checks).',
      'Diagnostics',
      'admin',
      'lint/html_report.php'
    );

    $tools[] = $mk(
      'diagnostics/lint/css-report',
      'CSS Report (Basic)',
      'Finds common CSS errors (unbalanced braces, suspicious tokens).',
      'Diagnostics',
      'admin',
      'lint/css_report.php'
    );

    $tools[] = $mk(
      'diagnostics/lint/js-report',
      'JS Report (Heuristic)',
      'Scans JS files for risky patterns (eval/new Function/document.write/innerHTML).',
      'Diagnostics',
      'admin',
      'lint/js_report.php'
    );

    $tools[] = $mk(
      'diagnostics/lint/sql-report',
      'SQL Report (Basic)',
      'Scans .sql and embedded SQL strings for common hazards (destructive SQL keywords, etc.).',
      'Diagnostics',
      'admin',
      'lint/sql_report.php'
    );

    $tools[] = $mk(
      'diagnostics/lint/file-integrity',
      'File Integrity',
      'Lists zero-byte files, unreadable files, and suspicious extensions under public.',
      'Diagnostics',
      'admin',
      'lint/file_integrity.php'
    );

    $tools[] = $mk(
      'diagnostics/lint/export-history-csv',
      'Export History CSV (Append)',
      'Appends latest quick scan summary row into logs/tools/quick_scan_history.csv.',
      'Diagnostics',
      'admin',
      'lint/export_history_csv.php'
    );

    $tools[] = $mk(
      'diagnostics/lint/critical-alert',
      'Critical Alert Flag',
      'Creates/clears logs/tools/critical_alert.flag based on latest quick scan.',
      'Diagnostics',
      'admin',
      'lint/critical_alert.php'
    );

    $tools[] = $mk(
      'diagnostics/lint/weekly-email-summary',
      'Weekly Email Summary',
      'Builds a weekly summary from quick_scan_history.csv and emails it (if configured).',
      'Diagnostics',
      'owner',
      'lint/weekly_email_summary.php'
    );

    $tools[] = $mk(
      'diagnostics/lint/trend-chart',
      'Trend Chart (Quick Scan)',
      'Shows score trend from logs/tools/quick_scan_history.csv (SVG chart, no libraries).',
      'Diagnostics',
      'admin',
      'lint/trend_chart.php'
    );

    /* =========================================================
       OPS
       ========================================================= */

    $tools[] = $mk(
      'ops/audit-report',
      'Ops Audit Report',
      'Operational audit report generator (read-only report output).',
      'Ops',
      'owner',
      'ops/audit_report.php'
    );

    $tools[] = $mk(
      'ops/init-offenders-list',
      'Init Offenders List',
      'Lists files that reference invalid init paths/strings (read-only list).',
      'Ops',
      'owner',
      'ops/_init_offenders.list.php'
    );

    $tools[] = $mk(
      'ops/scrub-bad-init-strings',
      'Scrub Bad Init Strings',
      'Scrubs invalid init references (MUTATING). Owner-only.',
      'Ops',
      'owner',
      'ops/scrub_bad_init_strings.php',
      true
    );

    $tools[] = $mk(
      'ops/fix-public-init-offenders',
      'Fix Public Init Offenders',
      'Fixes public init offenders (MUTATING). Owner-only.',
      'Ops',
      'owner',
      'ops/fix_public_init_offenders.php',
      true
    );
    
    $tools[] = $mk(
      'ops/attachments/normalize-external',
      'Normalize External Attachments (Cleanup)',
      'Owner-only cleanup: normalizes external_url/external_host for existing page_files rows (dry-run unless apply=1).',
      'Ops',
      'owner',
      'ops/attachments_normalize_external.php',
      true
    );


    /* =========================================================
       STAFF TOOLS
       ========================================================= */

    $tools[] = $mk(
      'staff-tools/audit',
      'Staff Audit',
      'Staff-focused audit checks and summaries.',
      'Staff Tools',
      'admin',
      'staff-tools/audit.php'
    );

    $tools[] = $mk(
      'staff-tools/audit-staff-account',
      'Audit Staff Account',
      'Audits staff account configuration and security posture.',
      'Staff Tools',
      'admin',
      'audit_staff_account.php'
    );

    $tools[] = $mk(
      'staff-tools/backfill-bio-html',
      'Backfill Bio HTML',
      'Backfills/normalizes stored bio HTML (MUTATING). Owner-only.',
      'Staff Tools',
      'owner',
      'staff-tools/backfill_bio_html.php',
      true
    );

    $tools[] = $mk(
      'staff-tools/error-log',
      'Error Log Viewer',
      'Displays recent server/PHP errors in a safe formatted view.',
      'Staff Tools',
      'admin',
      'staff-tools/error_log.php'
    );

    /* Remove empty entries (PHP-only policy may blank some) */
    $tools = array_values(array_filter($tools, static fn($t) => is_array($t) && !empty($t)));

    /* Enforce unique keys (first wins) */
    $seen = [];
    $final = [];
    foreach ($tools as $t) {
      $k = (string)($t['key'] ?? '');
      if ($k === '' || isset($seen[$k])) continue;
      $seen[$k] = true;
      $final[] = $t;
    }

    return $final;
  }
}

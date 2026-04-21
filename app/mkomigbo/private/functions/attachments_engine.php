<?php
declare(strict_types=1);

/**
 * Attachments engine for Mkomigbo
 * DB: page_files
 * Public handler: /attachments/file.php?id=PAGE_FILE_ID
 *
 * Output per item includes:
 * - kind (best-effort)
 * - bytes (int)
 * - badge (short label: PDF/IMG/VID/AUD/WEB/DOC/DATA/FILE)
 * - size_human (e.g., 15.2 KB)
 *
 * This file is schema-tolerant: it only selects columns that exist.
 */

if (!function_exists('mk_attachments_engine')) {

  function mk_attachments_engine(): object {

    return new class {

      /* ---------- schema helpers ---------- */

      private function pdo(): ?PDO {
        if (!function_exists('db')) return null;
        $pdo = db();
        return ($pdo instanceof PDO) ? $pdo : null;
      }

      private function column_exists(PDO $pdo, string $table, string $column): bool {
        static $cache = [];
        $k = strtolower($table . '.' . $column);
        if (array_key_exists($k, $cache)) return (bool)$cache[$k];

        try {
          $st = $pdo->prepare("
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1
          ");
          $st->execute([$table, $column]);
          $cache[$k] = (bool)$st->fetchColumn();
          return (bool)$cache[$k];
        } catch (Throwable $e) {
          $cache[$k] = false;
          return false;
        }
      }

      private function h(string $v): string {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
      }

      private function safe_str($v): string {
        return is_string($v) ? trim($v) : trim((string)($v ?? ''));
      }

      /* ---------- metadata normalization ---------- */

      private function host_from_url(string $url): string {
        $p = @parse_url($url);
        if (!is_array($p)) return '';
        $host = isset($p['host']) ? strtolower((string)$p['host']) : '';
        $host = preg_replace('/^www\./i', '', $host) ?? $host;
        $host = rtrim($host, '.');
        return $host ?: '';
      }

      private function guess_kind(string $mime, string $nameOrPath, bool $isExternal, string $externalUrl): string {
        $m = strtolower(trim($mime));

        if ($m !== '') {
          if (str_starts_with($m, 'application/pdf')) return 'pdf';
          if (str_starts_with($m, 'image/')) return 'image';
          if (str_starts_with($m, 'audio/')) return 'audio';
          if (str_starts_with($m, 'video/')) return 'video';
          if (preg_match('~(text/plain|text/markdown|text/html)~', $m)) return 'doc';
          if (preg_match('~(application/json|application/xml|text/csv)~', $m)) return 'dataset';
        }

        // External heuristics
        if ($isExternal) {
          $host = $this->host_from_url($externalUrl);
          if ($host !== '') {
            if (preg_match('~(^|\.)youtu\.be$|(^|\.)youtube\.com$~', $host)) return 'video';
            if (preg_match('~(^|\.)wikipedia\.org$|(^|\.)wikimedia\.org$~', $host)) return 'web';
            if (preg_match('~(^|\.)archive\.org$~', $host)) return 'web';
          }
          // URL path extension
          $p = @parse_url($externalUrl);
          $path = is_array($p) ? (string)($p['path'] ?? '') : '';
          $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
          if ($ext === 'pdf') return 'pdf';
          return 'web';
        }

        $ext = strtolower(pathinfo($nameOrPath, PATHINFO_EXTENSION));
        if ($ext === 'pdf') return 'pdf';
        if (in_array($ext, ['png','jpg','jpeg','gif','webp','svg'], true)) return 'image';
        if (in_array($ext, ['mp3','wav','ogg','m4a','flac'], true)) return 'audio';
        if (in_array($ext, ['mp4','webm','mov','mkv'], true)) return 'video';
        if (in_array($ext, ['csv','tsv','json','xml'], true)) return 'dataset';
        if (in_array($ext, ['doc','docx','rtf','txt','md','html','htm','ppt','pptx','xls','xlsx'], true)) return 'doc';

        return 'other';
      }

      private function badge_from_kind(string $kind): string {
        $k = strtolower(trim($kind));
        return match ($k) {
          'pdf'     => 'PDF',
          'image'   => 'IMG',
          'video'   => 'VID',
          'audio'   => 'AUD',
          'doc'     => 'DOC',
          'dataset' => 'DATA',
          'web'     => 'WEB',
          default   => 'FILE',
        };
      }

      private function human_bytes(int $bytes): string {
        if ($bytes <= 0) return '';
        $u = ['B','KB','MB','GB','TB'];
        $i = 0;
        $v = (float)$bytes;
        while ($v >= 1024 && $i < count($u) - 1) { $v /= 1024; $i++; }
        $fmt = ($i === 0) ? '%.0f' : (($v < 10) ? '%.2f' : '%.1f');
        return sprintf($fmt, $v) . ' ' . $u[$i];
      }

      /* ---------- public API ---------- */

      /**
       * Load attachments for a page.
       * $subject_id is accepted for call compatibility; not required for DB query.
       */
      public function load_for_subject_page(int $subject_id, int $page_id): array {
        $pdo = $this->pdo();
        if (!$pdo) return [];
        if ($page_id < 1) return [];

        // Build SELECT list based on existing columns
        $baseCols = ['id','page_id'];
        $optCols = [
          'is_external',
          'external_url',
          'external_host',
          'stored_path',
          'stored_name',
          'file_path',
          'original_name',
          'mime_type',
          'kind',
          'source_key',
          'source_label',
          'host',
          'canonical_url',
          'title',
          'authors',
          'pub_year',
          'doi',
          'isbn',
          'lang',
          'file_size',
          'sort_order',
          'created_at',
        ];

        $cols = $baseCols;
        foreach ($optCols as $c) {
          if ($this->column_exists($pdo, 'page_files', $c)) $cols[] = $c;
        }

        $sql = "
          SELECT " . implode(',', $cols) . "
          FROM page_files
          WHERE page_id = :pid
          ORDER BY
            " . (in_array('sort_order', $cols, true) ? "COALESCE(sort_order, 999999) ASC," : "") . "
            id ASC
        ";

        $st = $pdo->prepare($sql);
        $st->execute([':pid' => $page_id]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];

        foreach ($rows as $r) {
          $id = (int)($r['id'] ?? 0);
          if ($id <= 0) continue;

          $is_external = ((int)($r['is_external'] ?? 0) === 1) || ($this->safe_str($r['external_url'] ?? '') !== '');
          $external_url = $this->safe_str($r['external_url'] ?? '');

          $href = $is_external
            ? $external_url
            : ('/attachments/file.php?id=' . rawurlencode((string)$id));

          // label precedence: title > original_name > fallback
          $label = $this->safe_str($r['title'] ?? '');
          if ($label === '') $label = $this->safe_str($r['original_name'] ?? '');
          if ($label === '') $label = 'Attachment #' . $id;

          $mime = $this->safe_str($r['mime_type'] ?? '');

          // Use stored kind if present, else guess
          $kind = $this->safe_str($r['kind'] ?? '');
          if ($kind === '') {
            $nameOrPath = $this->safe_str($r['original_name'] ?? '');
            if ($nameOrPath === '') $nameOrPath = $this->safe_str($r['file_path'] ?? $this->safe_str($r['stored_name'] ?? ''));
            $kind = $this->guess_kind($mime, $nameOrPath, $is_external, $external_url);
          }

          // bytes
          $bytes = (int)($r['file_size'] ?? 0);
          if ($bytes < 0) $bytes = 0;

          // external_host (from DB, else derive)
          $external_host = $this->safe_str($r['external_host'] ?? '');
          if ($external_host === '' && $is_external && $external_url !== '') {
            $external_host = $this->host_from_url($external_url);
          }

          // a short badge label
          $badge = $this->badge_from_kind($kind);

          // optional: thumbnail hint (only safe for local images)
          $is_image = (strtolower($kind) === 'image');
          $thumb_src = (!$is_external && $is_image) ? $href : '';

          $out[] = [
            // existing fields used by your show.php
            'id'            => $id,
            'page_id'       => (int)($r['page_id'] ?? 0),
            'is_external'   => $is_external,
            'href'          => $href,
            'label'         => $label,

            // helpful metadata
            'original_name' => $this->safe_str($r['original_name'] ?? ''),
            'mime_type'     => $mime,
            'kind'          => $kind,

            // requested additions
            'bytes'         => $bytes,
            'size_human'    => $this->human_bytes($bytes),
            'badge'         => $badge,

            // optional extras (safe)
            'is_image'      => $is_image,
            'thumb_src'     => $thumb_src,

            // passthrough if they exist in DB
            'source_key'    => $this->safe_str($r['source_key'] ?? ''),
            'source_label'  => $this->safe_str($r['source_label'] ?? ''),
            'authors'       => $this->safe_str($r['authors'] ?? ''),
            'pub_year'      => $this->safe_str($r['pub_year'] ?? ''),
            'doi'           => $this->safe_str($r['doi'] ?? ''),
            'isbn'          => $this->safe_str($r['isbn'] ?? ''),
            'lang'          => $this->safe_str($r['lang'] ?? ''),
            'canonical_url' => $this->safe_str($r['canonical_url'] ?? ''),
            'external_host' => $external_host,
            'created_at'    => $this->safe_str($r['created_at'] ?? ''),
          ];
        }

        return $out;
      }

      /**
       * Optional render helper (kept for compatibility).
       * Produces a basic list; your staff UI already custom-renders, so this is mostly for public pages.
       */
      public function render(array $items): string {
        if (!$items) return '';

        $html  = "<section class=\"mk-attachments\">\n";
        $html .= "  <h3 class=\"mk-attachments__title\">Attachments</h3>\n";
        $html .= "  <ul class=\"mk-attachments__list\">\n";

        foreach ($items as $it) {
          $href  = (string)($it['href'] ?? '#');
          $label = (string)($it['label'] ?? 'Attachment');
          $badge = (string)($it['badge'] ?? '');
          $size  = (string)($it['size_human'] ?? '');
          $is_ext = !empty($it['is_external']);
          $ext = $is_ext ? " target=\"_blank\" rel=\"noopener noreferrer\"" : "";

          $meta = [];
          if ($badge !== '') $meta[] = $badge;
          if ($size !== '')  $meta[] = $size;

          $meta_txt = $meta
            ? (" <span class=\"mk-attachments__meta\">" . $this->h(implode(" • ", $meta)) . "</span>")
            : "";

          $html .= "    <li class=\"mk-attachments__item\">";
          $html .= "<a class=\"mk-attachments__link\" href=\"" . $this->h($href) . "\"{$ext}>" . $this->h($label) . "</a>";
          $html .= $meta_txt;
          $html .= "</li>\n";
        }

        $html .= "  </ul>\n";
        $html .= "</section>\n";
        return $html;
      }
    };
  }
}
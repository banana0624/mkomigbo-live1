<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/functions/attachments_engine.php
 *
 * Attachments Engine (DB + Local + Remote)
 *
 * Local convention (private storage):
 *   PRIVATE_PATH/subjects-media/{subject-slug}/{page-slug}/
 *   (fallback: APP_ROOT/private/subjects-media/...)
 *
 * Optional remote links.json (stored alongside local files):
 *   [
 *     {"url":"https://www.youtube.com/watch?v=XXXX","title":"Title","caption":"..."},
 *     {"url":"https://en.wikipedia.org/wiki/Igbo_people","title":"","caption":"..."}
 *   ]
 *
 * Optional local meta.json:
 *   {
 *     "file.pdf": {"title":"...", "caption":"..."},
 *     "image.jpg": {"title":"...", "caption":"..."}
 *   }
 *
 * Public delivery endpoints (CENTRAL):
 *   /media.php?subject={subject}&page={page}&file={filename}&scope=private&dl=0|1
 *   /download.php?subject={subject}&page={page}&file={filename}&scope=private
 */

if (!function_exists('mk_attachments_engine')) {

  function mk_attachments_engine(): object
  {
    return new class {

      /* =========================================================
         PUBLIC API
         ========================================================= */

      /**
       * @param mixed $pdo PDO|null
       * @return array<int, array<string,mixed>>
       */
      public function load_for_subject_page(
        string $subject_slug,
        string $page_slug,
        $pdo,
        int $page_id,
        string $body_html
      ): array {
        $subject_slug = $this->slug_norm($subject_slug);
        $page_slug    = $this->slug_norm($page_slug);

        if (!$this->slug_ok($subject_slug) || !$this->slug_ok($page_slug)) return [];

        $items = [];

        // 1) DB attachments (page_files / attachments)
        try {
          if ($pdo instanceof PDO && $page_id > 0) {
            $items = array_merge($items, $this->load_db_attachments($pdo, $page_id, $subject_slug, $page_slug));
          }
        } catch (Throwable $e) {
          // ignore; local + remote still work
        }

        // 2) Local folder scan
        $items = array_merge($items, $this->load_local_attachments($subject_slug, $page_slug));

        // 3) Optional remote links.json
        $items = array_merge($items, $this->load_remote_links($subject_slug, $page_slug));

        // Normalize + de-dupe by URL
        $seen = [];
        $out  = [];

        foreach ($items as $it) {
          $u = isset($it['url']) ? (string)$it['url'] : '';
          $u = $this->normalize_url_any($u);
          if ($u === '') continue;

          // For external links, tighten normalization (strip whitespace/punct wrappers)
          $kind = isset($it['kind']) ? (string)$it['kind'] : 'doc';
          if ($kind === 'link') $u = $this->normalize_external_url($u);
          if ($u === '') continue;

          if (isset($seen[$u])) continue;
          $seen[$u] = true;

          $it['url'] = $u;

          // Ensure required fields
          if (!isset($it['kind']) || !is_string($it['kind']) || trim($it['kind']) === '') $it['kind'] = 'doc';

          if (!isset($it['title']) || !is_string($it['title']) || trim($it['title']) === '') {
            $it['title'] = ($it['kind'] === 'link') ? $this->label_from_url($u, 'External link') : 'Attachment';
          }

          if (!isset($it['caption']) || !is_string($it['caption'])) $it['caption'] = '';
          if (!isset($it['mime']) || !is_string($it['mime'])) $it['mime'] = '';
          if (!isset($it['size']) || !is_int($it['size'])) $it['size'] = (int)($it['size'] ?? 0);
          if (!isset($it['source']) || !is_string($it['source'])) $it['source'] = '';

          $out[] = $it;
        }

        // Deterministic sort: kind -> title -> url
        $kindOrder = ['image'=>10,'video'=>20,'audio'=>30,'doc'=>40,'link'=>50];
        usort($out, function(array $a, array $b) use ($kindOrder): int {
          $ka = (string)($a['kind'] ?? 'doc');
          $kb = (string)($b['kind'] ?? 'doc');
          $oa = $kindOrder[$ka] ?? 999;
          $ob = $kindOrder[$kb] ?? 999;
          if ($oa !== $ob) return $oa <=> $ob;

          $ta = trim((string)($a['title'] ?? ''));
          $tb = trim((string)($b['title'] ?? ''));
          $ta = $this->lower($ta);
          $tb = $this->lower($tb);
          if ($ta !== $tb) return $ta <=> $tb;

          $ua = (string)($a['url'] ?? '');
          $ub = (string)($b['url'] ?? '');
          return strcmp($ua, $ub);
        });

        return $out;
      }

      /**
       * Render attachments list.
       * @param array<int, array<string,mixed>> $items
       */
      public function render(array $items): string
      {
        if (empty($items)) return '';

        $groups = [
          'image' => [],
          'video' => [],
          'audio' => [],
          'doc'   => [],
          'link'  => [],
        ];

        foreach ($items as $it) {
          $k = (string)($it['kind'] ?? 'doc');
          if (!isset($groups[$k])) $k = 'doc';
          $groups[$k][] = $it;
        }

        $html  = '<section class="mk-attachments" aria-label="Attachments">';
        $html .= '  <div class="mk-card" style="margin-top:14px;">';
        $html .= '    <div class="mk-card__body">';
        $html .= '      <h2 style="margin:0 0 10px 0;">Attachments</h2>';

        $html .= $this->render_group('Images',     $groups['image']);
        $html .= $this->render_group('Video',      $groups['video']);
        $html .= $this->render_group('Audio',      $groups['audio']);
        $html .= $this->render_group('Documents',  $groups['doc']);
        $html .= $this->render_group('Links',      $groups['link']);

        $html .= '    </div>';
        $html .= '  </div>';
        $html .= '</section>';

        return $html;
      }

      /* =========================================================
         RENDER HELPERS
         ========================================================= */

      /**
       * @param array<int, array<string,mixed>> $items
       */
      private function render_group(string $label, array $items): string
      {
        if (empty($items)) return '';

        $h = '';
        $h .= '<div class="mk-attach-group" style="margin-top:14px;">';
        $h .= '  <div class="mk-muted" style="font-weight:700; margin:0 0 8px 0;">' . $this->eh($label) . '</div>';
        $h .= '  <div class="mk-attach-grid" style="display:grid; gap:12px;">';

        foreach ($items as $it) $h .= $this->render_item($it);

        $h .= '  </div>';
        $h .= '</div>';
        return $h;
      }

      /**
       * @param array<string,mixed> $it
       */
      private function render_item(array $it): string
      {
        $kind    = (string)($it['kind'] ?? 'doc');
        $url     = (string)($it['url'] ?? '');
        $mime    = (string)($it['mime'] ?? '');
        $size    = (int)($it['size'] ?? 0);
        $source  = (string)($it['source'] ?? '');

        $title   = trim((string)($it['title'] ?? ''));
        $caption = trim((string)($it['caption'] ?? ''));

        if ($title === '') $title = ($kind === 'link') ? $this->label_from_url($url, 'External link') : 'Attachment';

        $meta = [];
        if ($mime !== '') $meta[] = $mime;
        if ($size > 0) $meta[] = $this->human_bytes($size);
        if ($source !== '') $meta[] = $source;

        $meta_html = '';
        if (!empty($meta)) {
          $meta_html = '<div class="mk-card__meta" style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">';
          foreach ($meta as $m) $meta_html .= '<span class="mk-pill">' . $this->eh($m) . '</span>';
          $meta_html .= '</div>';
        }

        $card  = '<article class="mk-card mk-attach-card">';
        $card .= '  <div class="mk-card__bar" aria-hidden="true"></div>';
        $card .= '  <div class="mk-card__body">';

        if ($kind === 'image') {
          $card .= '    <div style="display:grid; gap:10px;">';
          $card .= '      <img src="' . $this->eh($url) . '" alt="' . $this->eh($title) . '" loading="lazy" style="max-width:100%; height:auto; border-radius:14px; display:block;">';
          $card .= '      <div>';
          $card .= '        <div style="font-weight:800;">' . $this->eh($title) . '</div>';
          if ($caption !== '') $card .= '        <div class="mk-muted" style="margin-top:4px;">' . $this->eh($caption) . '</div>';
          $card .=          $meta_html;
          $card .= '      </div>';
          $card .= '    </div>';

        } elseif ($kind === 'video') {
          $card .= '    <div style="display:grid; gap:10px;">';
          $card .= '      <video controls preload="metadata" style="width:100%; border-radius:14px; display:block;">';
          $card .= '        <source src="' . $this->eh($url) . '"' . ($mime !== '' ? ' type="' . $this->eh($mime) . '"' : '') . '>';
          $card .= '      </video>';
          $card .= '      <div style="font-weight:800;">' . $this->eh($title) . '</div>';
          if ($caption !== '') $card .= '      <div class="mk-muted" style="margin-top:4px;">' . $this->eh($caption) . '</div>';
          $card .=        $meta_html;
          $card .= '    </div>';

        } elseif ($kind === 'audio') {
          $card .= '    <div style="display:grid; gap:10px;">';
          $card .= '      <audio controls preload="metadata" style="width:100%;">';
          $card .= '        <source src="' . $this->eh($url) . '"' . ($mime !== '' ? ' type="' . $this->eh($mime) . '"' : '') . '>';
          $card .= '      </audio>';
          $card .= '      <div style="font-weight:800;">' . $this->eh($title) . '</div>';
          if ($caption !== '') $card .= '      <div class="mk-muted" style="margin-top:4px;">' . $this->eh($caption) . '</div>';
          $card .=        $meta_html;
          $card .= '    </div>';

        } elseif ($kind === 'link') {
          $target = !empty($it['external']) ? ' target="_blank" rel="noopener noreferrer nofollow"' : '';
          $card .= '    <div style="font-weight:800;">';
          $card .= '      <a href="' . $this->eh($url) . '"' . $target . ' style="text-decoration:none;">' . $this->eh($title) . '</a>';
          $card .= '    </div>';
          if ($caption !== '') $card .= '    <div class="mk-muted" style="margin-top:4px;">' . $this->eh($caption) . '</div>';
          $card .=      $meta_html;

        } else {
          // doc (and unknown)
          $card .= '    <a class="mk-btn" href="' . $this->eh($url) . '" style="text-decoration:none;">Download / Open</a>';
          $card .= '    <div style="font-weight:800; margin-top:10px;">' . $this->eh($title) . '</div>';
          if ($caption !== '') $card .= '    <div class="mk-muted" style="margin-top:4px;">' . $this->eh($caption) . '</div>';
          $card .=      $meta_html;
        }

        $card .= '  </div>';
        $card .= '</article>';

        return $card;
      }

      /* =========================================================
         LOCAL ATTACHMENTS
         ========================================================= */

      /**
       * @return array<int, array<string,mixed>>
       */
      private function load_local_attachments(string $subject_slug, string $page_slug): array
      {
        $base = $this->local_base_dir();
        if ($base === '' || !is_dir($base)) return [];

        $dir = rtrim($base . '/' . $subject_slug . '/' . $page_slug, "/\\");
        if (!is_dir($dir) || !is_readable($dir)) return [];

        $files = @scandir($dir);
        if (!is_array($files)) return [];

        $meta_map = $this->load_local_metadata($dir);
        $allow_ext = $this->allowed_extensions();

        $max_count = 80;
        $max_size  = 200 * 1024 * 1024; // 200MB per file

        $items = [];
        $n = 0;

        foreach ($files as $name) {
          if ($name === '.' || $name === '..') continue;
          if ($name === '' || $name[0] === '.') continue;
          if (strcasecmp($name, 'links.json') === 0) continue;
          if (strcasecmp($name, 'meta.json') === 0) continue;

          // hard-block dangerous
          if (preg_match('/\.(php|phtml|phar|cgi|pl|asp|aspx|js)$/i', $name)) continue;

          $abs = $dir . '/' . $name;
          if (!is_file($abs) || !is_readable($abs)) continue;

          $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
          if ($ext === '' || !isset($allow_ext[$ext])) continue;

          $size = (int)@filesize($abs);
          if ($size <= 0 || $size > $max_size) continue;

          $mime = $this->mime_for_file($abs, $allow_ext[$ext]);
          $kind = $allow_ext[$ext]['kind'];

          $title   = $meta_map[$name]['title'] ?? $this->title_from_filename($name);
          $caption = $meta_map[$name]['caption'] ?? '';

          $url = $this->local_public_url($subject_slug, $page_slug, $name, $kind);

          $items[] = [
            'kind'   => $kind,
            'title'  => $title,
            'caption'=> $caption,
            'url'    => $url,
            'mime'   => $mime,
            'size'   => $size,
            'source' => 'local',
          ];

          $n++;
          if ($n >= $max_count) break;
        }

        return $items;
      }

      private function local_base_dir(): string
      {
        if (defined('PRIVATE_PATH')) {
          return rtrim((string)PRIVATE_PATH, "/\\") . '/subjects-media';
        }
        if (defined('APP_ROOT')) {
          return rtrim((string)APP_ROOT, "/\\") . '/private/subjects-media';
        }
        return '';
      }

      /**
       * CENTRAL URLs only:
       * - docs -> /download.php
       * - media/images/audio/video -> /media.php
       */
      private function local_public_url(string $subject, string $page, string $file, string $kind): string
      {
        $scope = 'private';

        $media_path = defined('MK_MEDIA_PUBLIC_PATH') ? (string)MK_MEDIA_PUBLIC_PATH : '/media.php';
        $dl_path    = defined('MK_DOWNLOAD_PUBLIC_PATH') ? (string)MK_DOWNLOAD_PUBLIC_PATH : '/download.php';

        if ($kind === 'doc') {
          $q = http_build_query([
            'subject' => $subject,
            'page'    => $page,
            'file'    => $file,
            'scope'   => $scope,
          ]);
          $path = $dl_path . '?' . $q;
          return function_exists('url_for') ? (string)url_for($path) : $path;
        }

        $q = http_build_query([
          'subject' => $subject,
          'page'    => $page,
          'file'    => $file,
          'scope'   => $scope,
          'dl'      => '0',
        ]);

        $path = $media_path . '?' . $q;
        return function_exists('url_for') ? (string)url_for($path) : $path;
      }

      /**
       * @return array<string, array{title?:string, caption?:string}>
       */
      private function load_local_metadata(string $dir): array
      {
        $file = rtrim($dir, "/\\") . '/meta.json';
        if (!is_file($file) || !is_readable($file)) return [];

        $raw = @file_get_contents($file);
        if (!is_string($raw) || trim($raw) === '') return [];

        $json = json_decode($raw, true);
        if (!is_array($json)) return [];

        $out = [];
        foreach ($json as $k => $v) {
          if (!is_string($k) || $k === '' || !is_array($v)) continue;
          $t = isset($v['title']) && is_string($v['title']) ? trim($v['title']) : '';
          $c = isset($v['caption']) && is_string($v['caption']) ? trim($v['caption']) : '';
          $row = [];
          if ($t !== '') $row['title'] = $t;
          if ($c !== '') $row['caption'] = $c;
          if (!empty($row)) $out[$k] = $row;
        }
        return $out;
      }

      /* =========================================================
         REMOTE LINKS (links.json)
         ========================================================= */

      /**
       * @return array<int, array<string,mixed>>
       */
      private function load_remote_links(string $subject_slug, string $page_slug): array
      {
        $base = $this->local_base_dir();
        if ($base === '' || !is_dir($base)) return [];

        $dir = rtrim($base . '/' . $subject_slug . '/' . $page_slug, "/\\");
        if (!is_dir($dir) || !is_readable($dir)) return [];

        $file = $dir . '/links.json';
        if (!is_file($file) || !is_readable($file)) return [];

        $raw = @file_get_contents($file);
        if (!is_string($raw) || trim($raw) === '') return [];

        $data = json_decode($raw, true);
        if (!is_array($data)) return [];

        $out = [];
        foreach ($data as $row) {
          if (!is_array($row)) continue;

          $url_raw = isset($row['url']) && is_string($row['url']) ? (string)$row['url'] : '';
          $url = $this->normalize_external_url($url_raw);
          if ($url === '' || !$this->safe_external_url($url)) continue;

          $title   = isset($row['title']) && is_string($row['title']) ? trim($row['title']) : '';
          $caption = isset($row['caption']) && is_string($row['caption']) ? trim($row['caption']) : '';

          if ($title === '') $title = $this->label_from_url($url, 'External link');

          $out[] = [
            'kind'     => 'link',
            'title'    => $title,
            'caption'  => $caption,
            'url'      => $url,
            'mime'     => '',
            'size'     => 0,
            'source'   => 'remote',
            'external' => true,
          ];
        }

        return $out;
      }

      /* =========================================================
         DB ATTACHMENTS
         ========================================================= */

      /**
       * @return array<int, array<string,mixed>>
       */
      private function load_db_attachments(PDO $pdo, int $page_id, string $subject_slug, string $page_slug): array
      {
        $items = [];

        if ($this->table_exists($pdo, 'page_files')) {
          $items = array_merge($items, $this->load_db_page_files($pdo, $page_id, $subject_slug, $page_slug));
        } elseif ($this->table_exists($pdo, 'attachments')) {
          $items = array_merge($items, $this->load_db_attachments_table($pdo, $page_id));
        }

        return $items;
      }

      /**
       * @return array<int, array<string,mixed>>
       */
      private function load_db_page_files(PDO $pdo, int $page_id, string $subject_slug, string $page_slug): array
      {
        $cols = $this->table_columns($pdo, 'page_files');
        if (empty($cols)) return [];

        $has = static fn(string $c): bool => in_array($c, $cols, true);
        if (!$has('page_id') || !$has('id')) return [];

        $select = ['id','page_id'];
        foreach ([
          'is_external',
          'external_url','external_host',
          'stored_path','stored_name','file_path',
          'original_name','mime_type','file_size',
          'sort_order','created_at'
        ] as $c) {
          if ($has($c)) $select[] = $c;
        }

        $order = $has('sort_order')
          ? "sort_order IS NULL, sort_order ASC, id ASC"
          : "id ASC";

        $st = $pdo->prepare("SELECT " . implode(', ', $select) . " FROM page_files WHERE page_id = ? ORDER BY {$order}");
        $st->execute([$page_id]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];

        foreach ($rows as $r) {
          $isExternal = (int)($r['is_external'] ?? 0) === 1;

          if ($isExternal) {
            $url = $this->normalize_external_url((string)($r['external_url'] ?? ''));
            if ($url === '' || !$this->safe_external_url($url)) continue;

            $title = trim((string)($r['original_name'] ?? ''));
            if ($title === '') $title = $this->label_from_url($url, 'External link');

            $out[] = [
              'kind'     => 'link',
              'title'    => $title,
              'caption'  => '',
              'url'      => $url,
              'mime'     => '',
              'size'     => 0,
              'source'   => 'db',
              'external' => true,
            ];
            continue;
          }

          // local file row
          $storedName = trim((string)($r['stored_name'] ?? ''));
          $storedPath = trim((string)($r['stored_path'] ?? ''));
          $filePath   = trim((string)($r['file_path'] ?? ''));

          $mime = trim((string)($r['mime_type'] ?? ''));
          $size = (int)($r['file_size'] ?? 0);

          $title = trim((string)($r['original_name'] ?? ''));
          if ($title === '') $title = ($storedName !== '' ? $storedName : 'Attachment');

          // derive filename robustly
          $file = $storedName;
          if ($file === '') {
            if ($filePath !== '') $file = basename($filePath);
            elseif ($storedPath !== '') $file = basename($storedPath);
          }
          if ($file === '') continue;

          $kind = $this->kind_from_hint('', $mime, $file);
          $url  = $this->local_public_url($subject_slug, $page_slug, $file, $kind);

          $out[] = [
            'kind'   => $kind,
            'title'  => $title,
            'caption'=> '',
            'url'    => $url,
            'mime'   => $mime,
            'size'   => $size,
            'source' => 'db',
          ];
        }

        return $out;
      }

      /**
       * Fallback for other schemas
       * @return array<int, array<string,mixed>>
       */
      private function load_db_attachments_table(PDO $pdo, int $page_id): array
      {
        $cols = $this->table_columns($pdo, 'attachments');
        if (empty($cols)) return [];
        if (!in_array('page_id', $cols, true)) return [];
        if (!in_array('url', $cols, true) && !in_array('path', $cols, true)) return [];

        $select = ['page_id'];
        foreach (['title','caption','url','path','mime','kind','type'] as $c) {
          if (in_array($c, $cols, true)) $select[] = $c;
        }

        $st = $pdo->prepare("SELECT " . implode(', ', $select) . " FROM attachments WHERE page_id = ? ORDER BY id ASC");
        $st->execute([$page_id]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
          $url_raw = isset($r['url']) && is_string($r['url']) ? (string)$r['url'] : '';
          $url = $this->normalize_external_url($url_raw);

          $path = isset($r['path']) && is_string($r['path']) ? trim($r['path']) : '';

          $title = isset($r['title']) && is_string($r['title']) ? trim($r['title']) : '';
          $caption = isset($r['caption']) && is_string($r['caption']) ? trim($r['caption']) : '';
          $mime = isset($r['mime']) && is_string($r['mime']) ? trim($r['mime']) : '';

          $kind = '';
          if (isset($r['kind']) && is_string($r['kind'])) $kind = strtolower(trim($r['kind']));
          elseif (isset($r['type']) && is_string($r['type'])) $kind = strtolower(trim($r['type']));

          if ($url !== '' && $this->safe_external_url($url)) {
            $useTitle = $title !== '' ? $title : $this->label_from_url($url, 'External link');
            $out[] = [
              'kind'     => 'link',
              'title'    => $useTitle,
              'caption'  => $caption,
              'url'      => $url,
              'mime'     => $mime,
              'size'     => 0,
              'source'   => 'db',
              'external' => true,
            ];
          } else {
            if ($path !== '' && $this->starts_with($path, '/')) {
              $out[] = [
                'kind'    => $this->kind_from_hint($kind, $mime, $path),
                'title'   => ($title !== '' ? $title : 'Attachment'),
                'caption' => $caption,
                'url'     => (function_exists('url_for') ? (string)url_for($path) : $path),
                'mime'    => $mime,
                'size'    => 0,
                'source'  => 'db',
              ];
            }
          }
        }

        return $out;
      }

      private function kind_from_hint(string $kind, string $mime, string $nameOrPath): string
      {
        $kind = strtolower(trim($kind));
        if (in_array($kind, ['image','video','audio','doc','link'], true)) return $kind;

        $mime = strtolower(trim($mime));
        if ($this->starts_with($mime, 'image/')) return 'image';
        if ($this->starts_with($mime, 'video/')) return 'video';
        if ($this->starts_with($mime, 'audio/')) return 'audio';

        $ext = strtolower(pathinfo($nameOrPath, PATHINFO_EXTENSION));
        $allow = $this->allowed_extensions();
        if ($ext !== '' && isset($allow[$ext])) return $allow[$ext]['kind'];

        return 'doc';
      }

      /* =========================================================
         EXTERNAL URL POLICY (CONFIG)
         ========================================================= */

      private function allowlist_config(): array
      {
        static $cache = null;
        if (is_array($cache)) return $cache;

        $cache = [];

        $base = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
        if ($base === '') return $cache;

        $cfg = $base . '/private/config/external_attachments_allowlist.php';
        if (!is_file($cfg)) return $cache;

        $tmp = require $cfg;
        if (!is_array($tmp)) return $cache;

        $out = [];
        foreach ($tmp as $host => $rule) {
          if (!is_string($host) || trim($host) === '') continue;
          $h = strtolower(trim($host));
          $out[$h] = is_array($rule) ? $rule : [];
        }

        $cache = $out;
        return $cache;
      }

      /**
       * https only; no creds; block localhost + IP-literals;
       * allow exact host always; allow subdomain only when rule['subdomains'] is true.
       * Optional 'paths' restriction supported.
       */
      private function safe_external_url(string $url): bool
      {
        $url = $this->normalize_external_url($url);
        if ($url === '') return false;

        $p = @parse_url($url);
        if (!is_array($p)) return false;

        $scheme = strtolower((string)($p['scheme'] ?? ''));
        if ($scheme !== 'https') return false;

        if (!empty($p['user']) || !empty($p['pass'])) return false;

        $host = strtolower((string)($p['host'] ?? ''));
        if ($host === '') return false;

        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') return false;
        if (filter_var($host, FILTER_VALIDATE_IP)) return false;

        $path = (string)($p['path'] ?? '/');
        if ($path === '') $path = '/';

        $cfg = $this->allowlist_config();
        if (empty($cfg)) return false;

        $rule = null;

        // 1) Exact host match
        if (array_key_exists($host, $cfg)) {
          $rule = $cfg[$host];
        } else {
          // 2) Root+subdomain match ONLY if subdomains enabled
          foreach ($cfg as $root => $r) {
            if (!is_string($root) || $root === '' || !is_array($r)) continue;

            $allowSubs = !empty($r['subdomains']);
            if (!$allowSubs) continue;

            if ($host !== $root && $this->ends_with($host, '.' . $root)) {
              $rule = $r;
              break;
            }
          }
        }

        if (!is_array($rule)) return false;

        // Optional path restriction
        $paths = (isset($rule['paths']) && is_array($rule['paths'])) ? $rule['paths'] : [];
        if (empty($paths)) return true;

        foreach ($paths as $pref) {
          if (!is_string($pref) || $pref === '') continue;
          if ($this->starts_with($path, $pref)) return true;
        }

        return false;
      }

      /* =========================================================
         URL NORMALIZATION + LABELS
         ========================================================= */

      private function normalize_url_any(string $url): string
      {
        $url = preg_replace('/[\x00-\x1F\x7F]/u', '', $url) ?? $url;
        return trim($url);
      }

      private function normalize_external_url(string $url): string
      {
        $url = preg_replace('/[\x00-\x1F\x7F]/u', '', $url) ?? $url;
        $url = trim($url);
        if ($url === '') return '';

        if ($url[0] === '<' && substr($url, -1) === '>') {
          $url = trim(substr($url, 1, -1));
        }

        if (preg_match('/\s/u', $url)) {
          $parts = preg_split('/\s+/u', $url);
          if (is_array($parts) && isset($parts[0])) $url = trim((string)$parts[0]);
        }

        $url = rtrim($url, " \t\n\r\0\x0B.,;:)]}'\"");
        return $url;
      }

      private function label_from_url(string $url, string $fallback = 'External link'): string
      {
        $url = $this->normalize_external_url($url);
        if ($url === '') return $fallback;

        $p = @parse_url($url);
        if (!is_array($p)) return $fallback;

        $host = strtolower((string)($p['host'] ?? ''));
        $path = (string)($p['path'] ?? '');
        $query = (string)($p['query'] ?? '');

        $hostClean = preg_replace('/^www\./i', '', $host) ?? $host;

        // YouTube
        if ($hostClean === 'youtu.be' || $hostClean === 'youtube.com') {
          if ($hostClean === 'youtu.be' && $path !== '' && $path !== '/') return 'YouTube — Video';
          if ($this->starts_with($path, '/watch') && $this->contains($query, 'v=')) return 'YouTube — Video';
          if ($this->starts_with($path, '/shorts/')) return 'YouTube — Shorts';
          return 'YouTube';
        }

        // Wikipedia
        if ($this->ends_with($hostClean, 'wikipedia.org')) {
          if ($this->starts_with($path, '/wiki/')) {
            $slug = urldecode(substr($path, 6));
            $slug = str_replace('_', ' ', $slug);
            $slug = trim($slug);
            if ($slug !== '') return 'Wikipedia — ' . $slug;
          }
          return 'Wikipedia';
        }

        $label = $hostClean !== '' ? ucfirst($hostClean) : $fallback;

        if ($path !== '' && $path !== '/') {
          $short = $path;
          if (strlen($short) > 28) $short = substr($short, 0, 28) . '…';
          $label .= ' — ' . $short;
        }

        return $label;
      }

      /* =========================================================
         SCHEMA HELPERS
         ========================================================= */

      private function table_exists(PDO $pdo, string $table): bool
      {
        try {
          $st = $pdo->prepare("
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            LIMIT 1
          ");
          $st->execute([$table]);
          return (bool)$st->fetchColumn();
        } catch (Throwable $e) {
          return false;
        }
      }

      /**
       * @return array<int, string>
       */
      private function table_columns(PDO $pdo, string $table): array
      {
        try {
          $st = $pdo->prepare("
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
          ");
          $st->execute([$table]);
          $cols = $st->fetchAll(PDO::FETCH_COLUMN);

          $out = [];
          if (is_array($cols)) {
            foreach ($cols as $c) {
              if (is_string($c) && $c !== '') $out[] = $c;
            }
          }
          return $out;
        } catch (Throwable $e) {
          return [];
        }
      }

      /* =========================================================
         UTILS
         ========================================================= */

      private function slug_norm(string $s): string { return strtolower(trim($s)); }

      private function slug_ok(string $s): bool
      {
        return ($s !== '') && (bool)preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $s);
      }

      private function eh(string $v): string
      {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
      }

      private function human_bytes(int $n): string
      {
        if ($n < 1024) return $n . ' B';
        $kb = $n / 1024;
        if ($kb < 1024) return number_format($kb, 1) . ' KB';
        $mb = $kb / 1024;
        if ($mb < 1024) return number_format($mb, 1) . ' MB';
        $gb = $mb / 1024;
        return number_format($gb, 1) . ' GB';
      }

      private function title_from_filename(string $name): string
      {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = str_replace(['_', '-'], ' ', $base);
        $base = preg_replace('/\s+/u', ' ', $base) ?? $base;
        $base = trim($base);
        if ($base === '') $base = $name;
        return ucwords($base);
      }

      /**
       * @return array<string, array{kind:string, mime:string}>
       */
      private function allowed_extensions(): array
      {
        return [
          // Images
          'jpg'  => ['kind'=>'image','mime'=>'image/jpeg'],
          'jpeg' => ['kind'=>'image','mime'=>'image/jpeg'],
          'png'  => ['kind'=>'image','mime'=>'image/png'],
          'webp' => ['kind'=>'image','mime'=>'image/webp'],
          'gif'  => ['kind'=>'image','mime'=>'image/gif'],
          'svg'  => ['kind'=>'image','mime'=>'image/svg+xml'],

          // Video
          'mp4'  => ['kind'=>'video','mime'=>'video/mp4'],
          'webm' => ['kind'=>'video','mime'=>'video/webm'],
          'mov'  => ['kind'=>'video','mime'=>'video/quicktime'],

          // Audio
          'mp3'  => ['kind'=>'audio','mime'=>'audio/mpeg'],
          'wav'  => ['kind'=>'audio','mime'=>'audio/wav'],
          'ogg'  => ['kind'=>'audio','mime'=>'audio/ogg'],
          'm4a'  => ['kind'=>'audio','mime'=>'audio/mp4'],

          // Docs
          'pdf'  => ['kind'=>'doc','mime'=>'application/pdf'],
          'txt'  => ['kind'=>'doc','mime'=>'text/plain'],
          'csv'  => ['kind'=>'doc','mime'=>'text/csv'],
          'doc'  => ['kind'=>'doc','mime'=>'application/msword'],
          'docx' => ['kind'=>'doc','mime'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
          'ppt'  => ['kind'=>'doc','mime'=>'application/vnd.ms-powerpoint'],
          'pptx' => ['kind'=>'doc','mime'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
          'xls'  => ['kind'=>'doc','mime'=>'application/vnd.ms-excel'],
          'xlsx' => ['kind'=>'doc','mime'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
          'zip'  => ['kind'=>'doc','mime'=>'application/zip'],
        ];
      }

      private function mime_for_file(string $abs, array $fallback): string
      {
        $mime = '';
        try {
          if (class_exists('finfo')) {
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $m = $fi->file($abs);
            if (is_string($m) && $m !== '') $mime = $m;
          }
        } catch (Throwable $e) {}
        if ($mime === '' && isset($fallback['mime'])) $mime = (string)$fallback['mime'];
        return $mime;
      }

      private function starts_with(string $haystack, string $needle): bool
      {
        if ($needle === '') return true;
        return substr($haystack, 0, strlen($needle)) === $needle;
      }

      private function ends_with(string $haystack, string $needle): bool
      {
        if ($needle === '') return true;
        $len = strlen($needle);
        return $len === 0 ? true : (substr($haystack, -$len) === $needle);
      }

      private function contains(string $haystack, string $needle): bool
      {
        if ($needle === '') return true;
        return strpos($haystack, $needle) !== false;
      }

      private function lower(string $s): string
      {
        if (function_exists('mb_strtolower')) return (string)mb_strtolower($s, 'UTF-8');
        return strtolower($s);
      }

    };
  }
}

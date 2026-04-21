<?php
declare(strict_types=1);

if (!function_exists('mk_attachments_engine')) {
  /**
   * Attachments engine: loads + renders page_files attachments list.
   * Returns an object with:
   * - load_for_subject_page(int $subject_id, int $page_id): array
   * - render(array $items): string
   */
  function mk_attachments_engine(): object {
    return new class {

      public function load_for_subject_page(int $subject_id, int $page_id): array {
        if (!function_exists('db')) return [];
        $pdo = db();
        if (!($pdo instanceof PDO)) return [];

        $st = $pdo->prepare("
          SELECT
            id,page_id,is_external,external_url,external_host,
            stored_path,stored_name,file_path,original_name,mime_type,
            kind,source_key,source_label,host,canonical_url,title,authors,
            pub_year,doi,isbn,lang,file_size,sort_order,created_at
          FROM page_files
          WHERE page_id = :pid
          ORDER BY COALESCE(sort_order, 999999) ASC, id ASC
        ");
        $st->execute([':pid' => $page_id]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
          $id = (int)($r['id'] ?? 0);
          if ($id < 1) continue;

          $is_external = ((int)($r['is_external'] ?? 0) === 1);
          $href = $is_external
            ? trim((string)($r['external_url'] ?? ''))
            : ('/attachments/file.php?id=' . rawurlencode((string)$id));

          $label = trim((string)($r['title'] ?? ''));
          if ($label === '') $label = trim((string)($r['original_name'] ?? ''));
          if ($label === '') $label = 'Attachment #' . $id;

          $out[] = [
            'id'            => $id,
            'page_id'        => (int)($r['page_id'] ?? 0),
            'is_external'    => $is_external,
            'href'           => $href,
            'label'          => $label,
            'original_name'  => (string)($r['original_name'] ?? ''),
            'mime_type'      => (string)($r['mime_type'] ?? ''),
            'kind'           => (string)($r['kind'] ?? ''),
            'source_key'     => (string)($r['source_key'] ?? ''),
            'source_label'   => (string)($r['source_label'] ?? ''),
            'host'           => (string)($r['host'] ?? ''),
            'canonical_url'  => (string)($r['canonical_url'] ?? ''),
            'external_host'  => (string)($r['external_host'] ?? ''),
            'authors'        => (string)($r['authors'] ?? ''),
            'pub_year'       => (string)($r['pub_year'] ?? ''),
            'doi'            => (string)($r['doi'] ?? ''),
            'isbn'           => (string)($r['isbn'] ?? ''),
            'lang'           => (string)($r['lang'] ?? ''),
            'file_size'      => (int)($r['file_size'] ?? 0),
            'created_at'     => (string)($r['created_at'] ?? ''),
          ];
        }

        return $out;
      }

      public function render(array $items): string {
        if (!$items) return '';

        $h = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

        $html  = "<section class=\"mk-attachments\">\n";
        $html .= "  <h3 class=\"mk-attachments__title\">Attachments</h3>\n";
        $html .= "  <ul class=\"mk-attachments__list\">\n";

        foreach ($items as $it) {
          $href  = (string)($it['href'] ?? '#');
          $label = (string)($it['label'] ?? 'Attachment');

          $meta = [];

          $kind = trim((string)($it['kind'] ?? ''));
          if ($kind !== '') $meta[] = $kind;

          $authors = trim((string)($it['authors'] ?? ''));
          if ($authors !== '') $meta[] = $authors;

          $pub_year = trim((string)($it['pub_year'] ?? ''));
          if ($pub_year !== '') $meta[] = $pub_year;

          $src = trim((string)($it['source_label'] ?? ''));
          if ($src !== '') $meta[] = $src;

          $metaStr = $meta ? ('<div class="mk-attachments__meta">'.$h(implode(' • ', $meta)).'</div>') : '';

          $html .= "    <li class=\"mk-attachments__item\">";
          $html .= "<a class=\"mk-attachments__link\" href=\"" . $h($href) . "\" target=\"_blank\" rel=\"noopener\">";
          $html .= $h($label) . "</a>";
          $html .= $metaStr;
          $html .= "</li>\n";
        }

        $html .= "  </ul>\n";
        $html .= "</section>\n";
        return $html;
      }

    };
  }
}

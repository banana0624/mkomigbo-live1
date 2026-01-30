<?php
declare(strict_types=1);

/**
 * /private/functions/sanitize.php
 *
 * Safe HTML allowlist sanitizer (DOM-based).
 * - Removes disallowed elements but preserves their text/children.
 * - Removes event handler attrs (on*) and style.
 * - Hardens <a href> to allow only:
 *     - relative paths starting with "/" (but not "//")
 *     - http(s) absolute URLs
 * - If target="_blank" is present, enforces rel="noopener noreferrer".
 *
 * Requires: ext-dom
 */

if (!function_exists('mk_sanitize_allowlist_html')) {

  function mk_sanitize_allowlist_html(string $html): string
  {
    $html = trim($html);
    if ($html === '') return '';

    $allowed_tags = [
      'p','br','hr',
      'strong','b','em','i','u',
      'ul','ol','li',
      'blockquote',
      'h2','h3','h4',
      'code','pre',
      'a',
      'span',
    ];

    $allowed_attrs = [
      'a'    => ['href','title','rel','target'],
      'span' => ['class'],
      '*'    => [],
    ];

    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);

    $wrapped = '<div>' . $html . '</div>';
    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $root = $dom->getElementsByTagName('div')->item(0);
    if (!$root instanceof DOMElement) return '';

    $remove_node_keep_children = static function (DOMNode $node): void {
      $parent = $node->parentNode;
      if (!$parent) return;

      while ($node->firstChild) {
        $parent->insertBefore($node->firstChild, $node);
      }
      $parent->removeChild($node);
    };

    $walk = function (DOMNode $node) use (
      &$walk,
      $allowed_tags,
      $allowed_attrs,
      $remove_node_keep_children
    ): void {

      if ($node->nodeType === XML_ELEMENT_NODE) {
        $tag = strtolower((string)$node->nodeName);

        if (!in_array($tag, $allowed_tags, true)) {
          $remove_node_keep_children($node);
          return;
        }

        if ($node instanceof DOMElement && $node->hasAttributes()) {
          $keep = $allowed_attrs[$tag] ?? ($allowed_attrs['*'] ?? []);
          $to_remove = [];

          foreach (iterator_to_array($node->attributes) as $attr) {
            /** @var DOMAttr $attr */
            $name = strtolower((string)$attr->name);

            if (str_starts_with($name, 'on') || $name === 'style') {
              $to_remove[] = (string)$attr->name;
              continue;
            }

            if (!in_array($name, $keep, true)) {
              $to_remove[] = (string)$attr->name;
              continue;
            }

            $val = (string)$attr->value;
            $val = preg_replace('/[\x00-\x1F\x7F]/', '', $val) ?? $val;
            $node->setAttribute($attr->name, $val);
          }

          foreach ($to_remove as $rm) {
            $node->removeAttribute($rm);
          }
        }

        if ($tag === 'a' && $node instanceof DOMElement) {
          $href = trim((string)$node->getAttribute('href'));
          $href = preg_replace('/[\x00-\x1F\x7F]/', '', $href) ?? $href;

          $ok = false;

          if ($href !== '') {
            if ($href[0] === '/') {
              $ok = !preg_match('~^//~', $href);
            } elseif (preg_match('~^https?://~i', $href)) {
              $ok = true;
            }
          }

          if (!$ok) {
            $node->removeAttribute('href');
            $node->removeAttribute('target');
            $node->removeAttribute('rel');
          } else {
            $target = strtolower(trim((string)$node->getAttribute('target')));
            if ($target === '_blank') {
              $node->setAttribute('rel', 'noopener noreferrer');
            }
          }
        }
      }

      $children = [];
      foreach ($node->childNodes as $c) $children[] = $c;
      foreach ($children as $c) $walk($c);
    };

    $walk($root);

    $out = '';
    foreach ($root->childNodes as $child) {
      $out .= (string)$dom->saveHTML($child);
    }

    return trim($out);
  }
}

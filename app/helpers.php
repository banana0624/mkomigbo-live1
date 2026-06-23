<?php

declare(strict_types=1);

function mk_render_page(array $page): void
{
    $title = htmlspecialchars((string)($page['title'] ?? 'Mkomigbo'), ENT_QUOTES, 'UTF-8');
    $body  = $page['body'] ?? '';
    $slug  = (string)($page['slug'] ?? '');

    // For the homepage, render the full landing page
    if ($slug === 'home' || $body === '') {
        mk_render_homepage($page);
        return;
    }

    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . $title . ' – Mkomigbo</title>';
    echo '<link rel="stylesheet" href="/assets/css/ui.css">';
    echo '<link rel="stylesheet" href="/assets/css/public.css">';
    echo '</head>';
    echo '<body>';
    echo '<header class="site-header"><div class="container"><a class="brand" href="/"><img src="/assets/images/logos/mk-logo.png" alt="Mkomigbo" width="32" height="32" style="border-radius:8px;"><span class="brand__text"><span class="brand__title">Mkomigbo</span><span class="brand__sub">Knowledge Platform</span></span></a><nav class="nav"><a href="/subjects/">Subjects</a><a href="/platforms/">Platforms</a><a href="/contributors/">Contributors</a><a href="/igbo-calendar/">Calendar</a></nav></div></header>';
    echo '<main class="site-main"><div class="container">';
    echo '<h1>' . $title . '</h1>';
    echo $body;
    echo '</div></main>';
    echo '<footer style="margin-top:48px;padding:24px 0;border-top:1px solid var(--border);text-align:center;color:var(--muted);font-size:.9rem;"><div class="container">© ' . date('Y') . ' Mkomigbo · <a href="/staff/">Staff</a></div></footer>';
    echo '</body></html>';
}

function mk_render_homepage(array $page): void {
    // Serve the professional homepage from public/home.php
    $home = __DIR__ . "/../public/home.php";
    if (is_file($home)) {
        require $home;
        return;
    }

    // Fallback if public/home.php is missing
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8");
    $nav_links = [
        ["/subjects/","Subjects"],
        ["/platforms/","Platforms"],
        ["/awag/","AWAG"],
        ["/igbo-calendar/","Igbo Calendar"],
        ["/contributors/","Contributors"],
    ];

    echo "<!DOCTYPE html><html lang=\"en\"><head>";
    echo "<meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">";
    echo "<title>Mkomigbo — Igbo Knowledge Platform</title>";
    echo "<meta name=\"description\" content=\"The open knowledge platform for Igbo history, culture, language, religion, and African heritage.\">";
    echo "<link rel=\"stylesheet\" href=\"/assets/css/ui.css\">";
    echo "<link rel=\"stylesheet\" href=\"/assets/css/public.css\">";
    echo "<link rel=\"stylesheet\" href=\"/assets/css/home.css\">";
    echo "</head><body>";

    echo "<header class=\"site-header\"><div class=\"container\" style=\"display:flex;align-items:center;justify-content:space-between;min-height:64px;gap:14px;\">";
    echo "<a href=\"/\" class=\"brand\" style=\"display:inline-flex;align-items:center;gap:10px;text-decoration:none;color:inherit;\">";
    echo "<img src=\"/assets/images/logos/mk-logo.png\" alt=\"Mkomigbo\" width=\"34\" height=\"34\" style=\"border-radius:9px;\">";
    echo "<span><span style=\"font-weight:900;font-size:1.05rem;display:block;\">Mkomigbo</span><span style=\"font-size:.78rem;color:#6b7280;\">Knowledge Platform</span></span></a>";
    echo "<nav style=\"display:flex;gap:4px;flex-wrap:wrap;\">";
    foreach ($nav_links as [$href, $label]) {
        echo "<a href=\"".$h($href)."\" style=\"padding:7px 13px;border-radius:10px;text-decoration:none;color:#374151;font-weight:600;font-size:.88rem;border:1px solid #e5e7eb;\">".$h($label)."</a>";
    }
    echo "</nav></div></header>";

    echo "<main style=\"max-width:1100px;margin:40px auto;padding:0 24px;\">";
    echo "<h1 style=\"font-size:2.5rem;font-weight:900;margin:0 0 16px;\">Mkomigbo</h1>";
    echo "<p style=\"font-size:1.1rem;color:#374151;max-width:60ch;line-height:1.75;margin:0 0 24px;\">The open knowledge platform for Igbo history, culture, language, religion, and African heritage.</p>";
    echo "<div style=\"display:flex;gap:12px;flex-wrap:wrap;\">";
    echo "<a href=\"/subjects/\" style=\"padding:12px 24px;background:#2d6a1f;color:#fff;border-radius:10px;font-weight:800;text-decoration:none;\">Explore Subjects</a>";
    echo "<a href=\"/awag/\" style=\"padding:12px 24px;background:#1a3a1a;color:#5de87a;border:1px solid #2d6a1f;border-radius:10px;font-weight:800;text-decoration:none;\">AWAG Calendar</a>";
    echo "<a href=\"/igbo-calendar/\" style=\"padding:12px 24px;background:#fff;color:#111;border:1px solid #e5e7eb;border-radius:10px;font-weight:800;text-decoration:none;\">Igbo Calendar</a>";
    echo "</div></main>";
    echo "<footer style=\"margin-top:48px;padding:24px;border-top:1px solid #e5e7eb;text-align:center;color:#9ca3af;font-size:.86rem;\">&copy; ".date("Y")." Mkomigbo</footer>";
    echo "</body></html>";
}


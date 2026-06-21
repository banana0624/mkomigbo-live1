<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">The Igbo do not have a history of struggle. They have a history of governance interrupted by force — and a continuous record of refusal to accept that force as final.</p>';
echo '<h2>Refusal as History</h2>';
echo '<p>The word "struggle" can domesticate what it names. It suggests effort against odds, the heroism of the underdog, a narrative arc that ends in either triumph or tragedy. What this subject documents is something more fundamental: a political tradition. The Igbo political tradition, rooted in decentralised governance, age-grade accountability, and the principle that authority is earned rather than inherited, was structurally incompatible with both British colonial administration and with the centralised Nigerian federal state that colonialism produced. What followed was not a series of struggles against external forces so much as a continuous defence of a way of organising political life that those external forces found inconvenient, threatening, or simply illegible.</p>';
echo '<p>This subject traces that defence across five centuries: from the resistance to the Atlantic slave trade, through the anti-colonial movements of the 19th and early 20th centuries, through the Biafra War, through the military dictatorship era, to the contemporary movements for self-determination and democratic accountability. It names the people who led that defence. It documents the costs they paid. And it refuses the narrative in which Igbo political agency is visible only in defeat.</p>';
echo '<h2>What This Subject Covers</h2>';
echo '<p>Five domains of struggle are documented here: anti-colonial resistance (from the Ekumeku movement to the Women\'s War of 1929); the Biafra War and its aftermath; resistance to military dictatorship (the pro-democracy movements of the 1980s and 1990s); environmental justice struggles in the Niger Delta; and contemporary movements including IPOB, ENDSARS, and the ongoing fight against federal marginalisation. Each domain has its own actors, its own tactics, and its own relationship to the broader question of what the Igbo people are owed by the Nigerian state and by history.</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/struggles/overview/','🗺️ Overview','Five centuries of Igbo resistance — from the slave trade to ENDSARS'],
  ['/subjects/struggles/topics/','🏛️ Five Sites of Struggle','Anti-colonial resistance, Biafra, military dictatorship, the Niger Delta, contemporary movements'],
  ['/subjects/struggles/people/','👤 People','Okonkwo Ekwueme, Margaret Ekpo, Odumegwu Ojukwu, Ken Saro-Wiwa, Nnamdi Kanu'],
  ['/subjects/struggles/sources/','📚 Sources','What to read — on Igbo resistance, Biafra, Niger Delta, and contemporary Nigerian politics'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Our Approach</h2>';
echo '<p>This subject is written from inside the experience it documents. It does not treat Igbo resistance as a curiosity or as a footnote to Nigerian national history. It treats it as the primary political tradition of a people who have been systematically excluded from the national narrative. Where that resistance has been violent, we document the violence without apology and without glorification. Where it has been cultural, legal, or literary, we document it with the same seriousness. This subject is cross-linked with <a href="/subjects/biafra/intro/">Biafra</a>, <a href="/subjects/resistance/intro/">Resistance</a>, and <a href="/subjects/nigeria/intro/">Nigeria</a>.</p>';
echo '</div>';
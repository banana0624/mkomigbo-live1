<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">Igbo history is not a marginal chapter of Nigerian history. It is one of the oldest, most complex, and most consequential histories in West Africa — a history of statecraft without kings, of trade without colonialism, of resistance without defeat.</p>';
echo '<h2>Why Igbo History Matters</h2>';
echo '<p>The Igbo are among the most studied and least understood peoples in Africa. They have been described — by colonial administrators, by rival Nigerian ethnic groups, and by their own romantic nationalists — in contradictory terms: as naturally democratic and as naturally anarchic; as commercially gifted and as untrustworthy traders; as educated and as parochial. These contradictions reflect not the Igbo but the frameworks through which outsiders have tried to comprehend a society that does not fit standard models of African political organisation.</p>';
echo '<p>The standard model — centralised kingdom, hereditary chief, ritual legitimacy of the crown — describes the Yoruba Oyo Empire, the Benin Kingdom, the Sokoto Caliphate. It does not describe the Igbo. Precolonial Igbo society organised itself through lineages, village assemblies, age-grade institutions, women\'s organisations, title societies, and the ritual authority of institutions like the Nri Kingdom — none of which required a king. This was not a failure to achieve statehood. It was a different and sophisticated solution to the problem of political organisation, one that distributed power rather than concentrating it, that made authority accountable rather than hereditary, and that proved extraordinarily resilient under colonial pressure.</p>';
echo '<p>Understanding this history matters because it explains the present. The Igbo experience of Nigeria — of the 1966 pogroms, the Biafra War, the post-war marginalisation, the contemporary self-determination movement — cannot be understood without understanding what the Igbo were before Nigeria existed: a people with a complex political tradition, deep trade networks, sophisticated religious institutions, and a strong sense of community identity that did not require external validation.</p>';
echo '<h2>What This Subject Covers</h2>';
echo '<p>Five domains are documented here: the origins and early society of the Igbo, including the Igbo-Ukwu archaeological evidence and the Nri Kingdom; the Atlantic slave trade and its specific consequences for Igboland; colonial transformation, missionary expansion, and anti-colonial resistance including the Women\'s War of 1929; the Nigeria-Biafra War and its aftermath; and the contemporary Igbo world — its diaspora, its political movements, and its cultural production. People and sources are documented separately with equal depth.</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/history/overview/','🗺️ Overview','From Igbo-Ukwu to the present — the broad sweep of Igbo historical development'],
  ['/subjects/history/topics/','🏛️ Key Topics','Nri Kingdom, the slave trade, the Women\'s War, Biafra, reconstruction, contemporary Igbo'],
  ['/subjects/history/people/','👤 People','Equiano, Azikiwe, Ojukwu, Achebe, Ekpo, Okigbo, Adichie'],
  ['/subjects/history/sources/','📚 Sources','What to read — archaeology, colonial history, the civil war, contemporary scholarship'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Our Approach</h2>';
echo '<p>This subject is written from inside the tradition it documents. It does not treat Igbo history as a problem to be explained by external frameworks. It treats it as a body of evidence — archaeological, linguistic, oral, archival, literary — that demands rigorous and honest engagement. Where the evidence is contested, we say so. Where the history involves violence, exploitation, and failure, we document it. Where it involves extraordinary achievement, we document that with equal seriousness. This subject is cross-linked with <a href="/subjects/culture/intro/">Culture</a>, <a href="/subjects/slavery/intro/">Slavery</a>, <a href="/subjects/biafra/intro/">Biafra</a>, and <a href="/subjects/struggles/intro/">Struggles</a>.</p>';
echo '</div>';
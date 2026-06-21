<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">Biafra is not a failed state. It is an unresolved argument — about what the Igbo are owed by Nigeria, about what states owe their citizens, and about what happens when a government decides that the starvation of children is an acceptable instrument of policy.</p>';
echo '<h2>What Biafra Was</h2>';
echo '<p>The Republic of Biafra was declared on 30 May 1967 by Lt. Colonel Odumegwu Ojukwu, Military Governor of Eastern Nigeria, following the collapse of the Aburi Accord and the federal government\'s announcement that it would divide the Eastern Region into three states — dissolving the Igbo majority\'s political coherence. It encompassed the predominantly Igbo Eastern Region along with significant Ibibio, Efik, Ijaw, and Ogoni populations. It survived for thirty months before Ojukwu\'s flight and Philip Effiong\'s surrender on 15 January 1970.</p>';
echo '<p>The war that followed the declaration killed between one and three million people — the overwhelming majority not from combat but from starvation, as the Nigerian federal government imposed a blockade that cut off food, medicine, and supplies to a civilian population of approximately 14 million people. The images of kwashiorkor-afflicted Biafran children — distended bellies, reddened hair, the characteristic wasting of severe protein malnutrition — shocked the world and triggered one of the first major international humanitarian media campaigns in history, leading directly to the founding of Médecins Sans Frontières.</p>';
echo '<h2>Why Biafra Happened</h2>';
echo '<p>Biafra did not begin with the declaration of 1967. Its roots lay in the structure of Nigeria itself — a colonial creation that amalgamated deeply different peoples under a single administrative framework that served British commercial interests rather than the interests of the peoples joined within it. The immediate cause was the 1966 pogroms: the mass killing of Igbo living in northern Nigeria, in which between 30,000 and 100,000 people were murdered, their property destroyed, their bodies mutilated, and over a million survivors fled south in the largest internal displacement in Nigerian history. The federal government\'s failure to protect its Igbo citizens, to prosecute the killers, or to acknowledge the scale of what had occurred made the case for a separate state not merely compelling but — for many Igbo — existential.</p>';
echo '<p>The Aburi Accord of January 1967 — negotiated between Gowon and Ojukwu in Ghana — produced an agreement for a loose confederation that would have kept Nigeria together while protecting Igbo safety within it. It was repudiated by the federal government on Gowon\'s return to Lagos, under pressure from northern officers, British advisers, and the civil service. The repudiation of Aburi is the moment at which the war became unavoidable.</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/biafra/overview/','🗺️ Overview','Causes, course, famine, end, and aftermath — the structured account'],
  ['/subjects/biafra/topics/','🏛️ Key Topics','The 1966 pogroms, the blockade, Britain\'s role, international response, memory and denial'],
  ['/subjects/biafra/people/','👤 People','Ojukwu, Gowon, Effiong, Awolowo, Achebe, Adichie, the children'],
  ['/subjects/biafra/sources/','📚 Sources','Primary accounts, academic histories, literature, film'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Our Approach</h2>';
echo '<p>This subject does not treat Biafra as a historical curiosity or a failed political project. It treats it as a human catastrophe with specific causes, specific actors, and specific consequences that have never been honestly addressed by the Nigerian state. It documents the 1966 pogroms as a root cause. It documents the blockade as a deliberate policy. It documents Britain\'s role without euphemism. And it documents the memory of Biafra — why it persists fifty years after the war\'s end, and what its persistence means for the future of Nigeria and the Igbo people. This subject is cross-linked with <a href="/subjects/nigeria/intro/">Nigeria</a>, <a href="/subjects/struggles/intro/">Struggles</a>, and <a href="/subjects/resistance/intro/">Resistance</a>.</p>';
echo '</div>';
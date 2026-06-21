<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">Africa is not the context for Igbo history. Africa is the argument. The case that Igbo history makes — about governance, about resistance, about what human beings are capable of — is an African case, and it cannot be made without the continent that produced it.</p>';
echo '<h2>Africa and the Igbo</h2>';
echo '<p>The Igbo are an African people — a statement so obvious it risks being overlooked. To say that Igbo experience is embedded in Africa is not to make a geographical observation. It is to make an epistemological one. The questions that Igbo history raises — about decentralised governance, about the relationship between religious authority and political power, about how communities maintain integrity under external pressure — are African questions. They have been raised, in different forms, by the Zulu and the Ashanti, by the Swahili traders of the East African coast and the scholars of Timbuktu, by the Kongolese who negotiated with Portuguese missionaries and the Ethiopians who defeated an Italian army.</p>';
echo '<p>The Africa subject on this platform is therefore not background. It is argument. It documents the pre-colonial civilisations that demonstrate African political and intellectual capacity before European contact. It traces the Atlantic and trans-Saharan slave trades that restructured African societies and removed millions of people. It follows the colonial partition that redrew African political geography without African consent. It tracks the independence movements that reclaimed formal sovereignty while inheriting colonial economic structures. And it asks what Africa — in all its diversity, its contradictions, its demographic dynamism, and its unresolved political crises — means for Igbo people in the 21st century.</p>';
echo '<h2>What This Subject Covers</h2>';
echo '<p>Five domains are documented here: the pre-colonial African civilisations and their intellectual and political achievements; the slave trades and their consequences; colonisation, the Berlin Conference, and the varieties of colonial administration; the independence movements and the first generation of African leadership; and contemporary Africa — its institutions, its challenges, and its connections to the Igbo diaspora worldwide. People and sources are documented separately with equal depth.</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/africa/overview/','🗺️ Overview','The full sweep — ancient civilisations, the slave trades, colonisation, independence, contemporary Africa'],
  ['/subjects/africa/topics/','🏛️ Five Sites of African History','Precolonial civilisations, the slave trades, the Berlin Conference, independence, pan-Africanism'],
  ['/subjects/africa/people/','👤 People','Mansa Musa, Queen Nzinga, Menelik II, Nkrumah, Lumumba, Fanon, Achebe, Sankara'],
  ['/subjects/africa/sources/','📚 Sources','What to read — African history, pre-colonial civilisations, colonialism, pan-Africanism'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Our Approach</h2>';
echo '<p>This subject does not treat Africa as a problem to be solved or a tragedy to be mourned. It treats Africa as a political and intellectual tradition — a set of experiences, arguments, and achievements that the world has consistently underestimated and that Igbo people, as Africans, share in and contribute to. Where African history has involved violence, exploitation, and failure, we document it. Where it has involved extraordinary governance, scholarship, resistance, and cultural creation, we document that with equal attention. This subject is cross-linked with <a href="/subjects/history/intro/">History</a>, <a href="/subjects/slavery/intro/">Slavery</a>, and <a href="/subjects/struggles/intro/">Struggles</a>.</p>';
echo '</div>';
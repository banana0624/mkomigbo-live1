<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">Nigeria is not a country that the Igbo chose. It is a container that British colonialism built and placed them in — along with 250 other peoples who had not previously shared a state. Understanding the Igbo experience requires understanding what Nigeria is, how it was made, and why it has never fully worked.</p>';
echo '<h2>What Nigeria Is</h2>';
echo '<p>Nigeria is the most populous country in Africa — home to over 220 million people, more than 250 ethnic groups, and two major religious traditions (Islam and Christianity) distributed in ways that roughly correspond to the colonial partition between the Muslim north and the predominantly Christian south. It was created by British colonialism: the 1914 amalgamation that Lord Lugard called "the marriage of the north and south" joined communities with profoundly different histories, religions, and political traditions under a single administrative framework that served British commercial and strategic interests. Independence came in 1960. Since then, Nigeria has experienced six successful military coups, a civil war that killed between one and three million people, and a democracy that has been repeatedly interrupted, manipulated, and compromised. It remains the world\'s largest Black nation and one of Africa\'s most consequential states — consequential in its failures as much as in its achievements.</p>';
echo '<h2>The Igbo in Nigeria</h2>';
echo '<p>The Igbo are one of Nigeria\'s three major ethnic groups alongside the Hausa-Fulani (dominant in the north) and the Yoruba (dominant in the southwest). Concentrated in southeastern Nigeria — Anambra, Imo, Abia, Enugu, and Ebonyi states, with significant populations in Rivers, Cross River, and Delta states — they number approximately 45 million in Nigeria alone, making them one of the largest ethnic groups in Africa. The Igbo are present in significant numbers in every Nigerian city and in major cities worldwide. Their commercial enterprise, educational achievement, and adaptability are consistent features of every context they inhabit.</p>';
echo '<p>The relationship between the Igbo and the Nigerian federal state has been defined since 1966 by three events: the military coups of January and July 1966; the pogroms against Igbo living in northern Nigeria in which between 30,000 and 100,000 people were killed; and the Biafra War (1967–1970), in which a federal military campaign — including a deliberate food blockade — killed between one and three million people, the majority Igbo. The post-war settlement, officially reconciliatory and practically punitive, left the Igbo structurally disadvantaged in federal appointments, military rank, oil revenue allocation, and infrastructure investment. This structural marginalisation is the context for every Igbo political grievance of the past fifty years.</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/nigeria/overview/','🗺️ Overview','From amalgamation to the present — colonial creation, independence, military rule, democracy'],
  ['/subjects/nigeria/topics/','🏛️ Key Topics','Ethnic federalism, oil and the resource curse, the Igbo question, Boko Haram, 2023 elections'],
  ['/subjects/nigeria/people/','👤 People','Azikiwe, Awolowo, Bello, Gowon, Abacha, Obasanjo, Buhari, Peter Obi'],
  ['/subjects/nigeria/sources/','📚 Sources','What to read on Nigerian history, politics, and the Igbo experience within the federation'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Our Approach</h2>';
echo '<p>This subject treats Nigeria as a political problem as much as a political entity — a state whose internal contradictions were built in at its creation and have never been honestly addressed. It documents Nigerian history from the Igbo perspective without reducing Nigeria to the Igbo experience. It acknowledges the legitimate political interests of Hausa-Fulani and Yoruba communities while being clear about how those interests have been exercised at Igbo expense. It is cross-linked with <a href="/subjects/biafra/intro/">Biafra</a>, <a href="/subjects/struggles/intro/">Struggles</a>, and <a href="/subjects/resistance/intro/">Resistance</a>.</p>';
echo '</div>';
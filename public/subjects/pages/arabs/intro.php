<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">The Arab world and Africa have never been separate. This subject traces a relationship older than Islam, more violent than acknowledged, and more consequential than contemporary politics admits.</p>';
echo '<h2>The Oldest Wound</h2>';
echo '<p>The Arab-African relationship predates Islam by millennia. North Africa was not Arab before the 7th century CE — it was Berber, Egyptian, Nubian, Phoenician, Roman. The Arab conquests of 639–711 CE transformed the demographic and linguistic landscape of the entire northern third of the African continent. This transformation was not peaceful. It involved military conquest, displacement of existing populations, suppression of indigenous languages and religions, and the imposition of Arabic as the prestige language of governance, scholarship, and faith. What is now called the Arab world in North Africa was, within living memory of the conquest, African in other languages and other traditions.</p>';
echo '<p>The trans-Saharan slave trade added a second layer of violence. Between 650 CE and 1900 CE, Arab and Arabised merchants transported an estimated 17 million Africans across the Sahara, the Red Sea, and the Indian Ocean into slavery in North Africa, the Arabian Peninsula, Persia, and the Ottoman Empire. This figure exceeds the Atlantic slave trade in duration if not in intensity, and it operated for twelve centuries before European abolition movements created the moral framework that eventually challenged it. It has never received equivalent historical attention.</p>';
echo '<p>Islam arrived in sub-Saharan Africa through these same trade routes — carried by merchants, scholars, and clerics who moved with the caravans. In northern Nigeria, Islam reached the Hausa states around the 11th century CE. The Sokoto Jihad of 1804–1808, led by Usman dan Fodio, established the Sokoto Caliphate — the largest state in Africa at the time — and consolidated Islamic authority across what became northern Nigeria under British colonial rule. The structures of the Sokoto Caliphate were deliberately preserved by British indirect rule and continue to shape northern Nigerian politics today.</p>';
echo '<p>For the Igbo, the Arab connection is therefore primarily mediated through Nigeria: through the Hausa-Fulani Muslim north whose political dominance of the Nigerian federal state has been the central fact of Igbo political experience since independence. The 1966 pogroms and the Biafra War (1967–1970) were in significant part a consequence of this fault line. To understand the Arabs subject on this platform is to understand how a civilisational encounter that began in the 7th century continues to shape the lives of Igbo people in the 21st.</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/arabs/overview/','🗺️ Overview','Five phases of the Arab-African relationship — conquest, trade, slavery, Islam, contemporary politics'],
  ['/subjects/arabs/topics/','🏛️ Five Sites of Encounter','The trans-Saharan slave trade, Islam in Africa, Arab anti-Blackness, Gulf investment, the Nigerian fault line'],
  ['/subjects/arabs/people/','👤 People','Ibn Battuta, Usman dan Fodio, Nasser, Gaddafi, Soyinka on Arab anti-Blackness'],
  ['/subjects/arabs/sources/','📚 Sources','What to read — on the trans-Saharan trade, Islam in West Africa, Arab-African relations'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Our Approach</h2>';
echo '<p>This subject does not treat the Arab world as monolithic or Islam as inherently problematic. It treats the Arab-African relationship as a historical reality with specific phases, specific actors, and specific consequences — some profoundly enriching and some profoundly destructive. The trans-Saharan slave trade is documented with the same rigour as the Atlantic trade. This subject is cross-linked with <a href="/subjects/religion/abrahamic/">Religion: Abrahamic</a>, <a href="/subjects/nigeria/intro/">Nigeria</a>, and <a href="/subjects/slavery/intro/">Slavery</a>.</p>';
echo '<div style="display:flex;gap:10px;flex-wrap:wrap;margin:28px 0 4px;">';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/slavery/overview/">→ Slavery</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/religion/overview/">→ Religion</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/africa/overview/">→ Africa</a>';
echo '</div>';
echo '</div>';
<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">Resistance is not the opposite of politics — it is politics by other means, when the normal means have been closed.</p>';
echo '<h2>What This Subject Is</h2>';
echo '<p>The Resistance subject is not a subject about IPOB. IPOB is the most visible contemporary expression of a tradition that is much older — a tradition of Igbo political assertion against structures of power that have consistently refused to accommodate Igbo interests on equitable terms. To reduce the subject to IPOB is to misunderstand both IPOB and the tradition it draws on.</p>';
echo '<p>This subject traces that tradition from its roots in the anti-colonial movements documented in Struggles, through the post-Biafra generation\'s navigation of military dictatorship, through the pro-democracy movements of the 1990s, through the emergence of IPOB and the self-determination movement, and into the contemporary moment in which Igbo political energy is divided between separatism, federal engagement, and the cross-ethnic youth movements represented by ENDSARS and the Labour Party\'s 2023 presidential campaign.</p>';
echo '<p>The central question this subject asks is not "should Biafra be independent?" — that is a political question for Igbo people to answer. The question this subject asks is: what forms has Igbo resistance taken, what has it achieved, what has it cost, and what does its persistence across five decades of post-war Nigerian politics tell us about the condition of the Igbo within the Nigerian state?</p>';
echo '<h2>Why IPOB Exists</h2>';
echo '<p>IPOB did not emerge in a vacuum. It is the product of accumulated grievances that the Nigerian state has refused to address: the unresolved wounds of the civil war; the abandoned property policy that dispossessed Igbo across Nigeria; the twenty-pound bank limit that wiped out Igbo savings; the infrastructure neglect that left southeastern roads, hospitals, and universities underfunded for decades; the underrepresentation of Igbo in the federal military command, the intelligence services, and senior civil service positions; and the failure of every Nigerian government since 1970 to acknowledge, let alone address, the injustices of the war and its aftermath.</p>';
echo '<p>IPOB\'s founder Nnamdi Kanu built Radio Biafra from London into the most listened-to Igbo political broadcast in the world, articulating these grievances in a language — Biafran nationalism — that gave them a political form the Nigerian state found threatening enough to respond to with military force. Operation Python Dance (2017), the proscription of IPOB as a terrorist organisation, and Kanu\'s extraordinary rendition from Kenya in 2021 represent the Nigerian state\'s answer to a political movement it has been unable to delegitimise through argument.</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/resistance/overview/','🗺️ Overview','The arc of post-war Igbo resistance — from 1970 to the present'],
  ['/subjects/resistance/topics/','🏛️ Five Sites of Resistance','IPOB, Ohanaeze, the 2023 Labour Party campaign, diaspora activism, cultural resistance'],
  ['/subjects/resistance/people/','👤 People','Nnamdi Kanu, Dim Ojukwu, Wole Soyinka, Peter Obi, Chimamanda Ngozi Adichie'],
  ['/subjects/resistance/sources/','📚 Sources','What to read on IPOB, self-determination, and contemporary Igbo politics'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Our Approach</h2>';
echo '<p>This subject does not take a position on Biafran independence. It documents the resistance tradition honestly — its achievements, its failures, its internal contradictions, and its costs. It treats IPOB with the same analytical seriousness it brings to Ohanaeze, to the Labour Party campaign, and to cultural resistance. It is cross-linked with <a href="/subjects/struggles/intro/">Struggles</a>, <a href="/subjects/biafra/intro/">Biafra</a>, and <a href="/subjects/nigeria/intro/">Nigeria</a>.</p>';
echo '</div>';
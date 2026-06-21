<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">The African presence in Europe is not immigration — it is return. This subject traces that presence from antiquity to the present, and names what Europe has consistently refused to name.</p>';
echo '<h2>The Return</h2>';
echo '<p>Europe did not discover Africa. Africa built Europe.</p>';
echo '<p>This is not provocation — it is periodisation. The Atlantic slave trade transferred an estimated 12.5 million Africans across the ocean, but it also transferred wealth: the capital that financed British industrialisation, French plantation empires, Dutch maritime dominance, Portuguese territorial expansion, and Spanish imperial administration. Eric Williams demonstrated in <em>Capitalism and Slavery</em> (1944) what European historiography spent the next eighty years attempting to qualify: that the profits of enslaved African labour were not incidental to European modernity but constitutive of it.</p>';
echo '<p>Before the Atlantic trade, African presence in Europe was already ancient. North African scholars, soldiers, and administrators shaped the Roman world — Septimius Severus, Emperor of Rome from 193 to 211 CE, was of North African origin. The Moors who entered the Iberian Peninsula in 711 CE brought with them a civilisational tradition that, over seven centuries, transformed European mathematics, medicine, philosophy, and architecture. Al-Andalus was not an interruption of European history — it was one of its most fertile periods. When the Reconquista expelled the Moors in 1492 — the same year Columbus sailed westward on Portuguese-mapped routes, using Arab navigational instruments — Europe did not shed an alien presence. It amputated a part of itself.</p>';
echo '<p>The post-WWII migration is therefore not the beginning of the African presence in Europe. It is the latest chapter of a story Europe has consistently narrated from the wrong starting point. Windrush, the <em>gastarbeiter</em> programmes, the Schengen-era arrivals, the Mediterranean crossings of the 2000s and 2010s — these are not the arrival of Africa in Europe. They are the continuation of a relationship that has never been symmetrical, never been voluntary on Africa\'s side, and never been honestly named on Europe\'s.</p>';
echo '<p>Mkomigbo\'s Europe subject begins from that refusal to misname. It traces the African presence in Europe across time, maps the communities that exist today, examines the second generation as a political and cultural formation, and attends to the lives of specific people who have inhabited, contested, and enlarged what it means to be African in Europe.</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/europe/overview/','🗺️ Overview','The long presence — from antiquity through the Atlantic rupture to the contemporary diaspora'],
  ['/subjects/europe/topics/','🏛️ Five Sites of Contestation','The Mediterranean as graveyard, the second generation, race and law, culture as claim, Igbo Europe'],
  ['/subjects/europe/people/','👤 People','Equiano, Fanon, Senghor, Evaristo, Dabiri — those who moved and those who were moved'],
  ['/subjects/europe/sources/','📚 Sources','What to read, and why — foundational, historical, contemporary, literary'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Our Approach</h2>';
echo '<p>This subject is written from the African side. It does not treat the African presence in Europe as a problem to be explained or a phenomenon to be managed. It treats it as a historical fact with deep roots, complex forms, and its own interior logic. Where Europe has caused harm, we document it. Where Africans in Europe have created beauty, built institutions, and enlarged what it means to be human, we document that too.</p>';
echo '<p>This subject is cross-linked with <a href="/subjects/struggles/intro/">Struggles</a> and <a href="/subjects/resistance/intro/">Resistance</a>.</p>';
echo '</div>';

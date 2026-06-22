<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">Igbo culture is not a collection of customs. It is a theory of the world — a set of answers to the questions that every human society must answer: how should authority be organised, how should the dead be honoured, how should the self be understood in relation to the community, and what does it mean to live well?</p>';
echo '<h2>What Igbo Culture Is</h2>';
echo '<p>The word "culture" risks making Igbo life sound like a museum exhibit — something preserved behind glass for study. Igbo culture is not that. It is the living medium through which Igbo people understand themselves, organise their communities, raise their children, negotiate their disputes, mark their transitions, and make meaning out of their experience. It includes the four-day market week that structures time; the <em>chi</em> that structures selfhood; the kola nut that structures hospitality; the masquerade that structures the boundary between the living and the dead; and the proverb that structures argument. These are not survivals from a pre-modern past. They are operating principles of contemporary Igbo life, in Owerri and Onitsha and London and Houston.</p>';
echo '<p>What makes Igbo culture particularly significant — and particularly difficult to understand within standard frameworks — is the absence of a single centralising authority. There was no Igbo king, no Igbo capital, no single religious institution that spoke for all Igbo. Cultural creativity was distributed: each community developed its own masquerades, its own festivals, its own artistic traditions, while sharing a common language, kinship system, moral framework, and cosmological vocabulary. This produced extraordinary diversity within unity — hundreds of distinct local traditions that are recognisably Igbo without being identical.</p>';
echo '<h2>Key Dimensions</h2>';
echo '<p>This subject documents eight dimensions of Igbo cultural life: language and orality (proverbs, storytelling, praise poetry); masquerades and performance (Mmanwu traditions); festivals and the agricultural cycle (New Yam Festival, Ofala, Iri Ji); visual arts (Uli, mbari, bronze casting, woodcarving); music and sound (ogene, ekwe, oja, udu, igba); food culture (yam as staple and symbol); dress and adornment (isi agu, george, coral beads, title dress); and kinship and family (umunna, ụmụada, compound life).</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/culture/overview/','🗺️ Overview','From the compound to the cosmos — the broad structure of Igbo cultural life'],
  ['/subjects/culture/topics/','🏛️ Key Topics','Kola nut, title systems, marriage, funerary culture, the chi, language, Nollywood'],
  ['/subjects/culture/people/','👤 People','Achebe, Nwapa, Adichie, Okigbo, Osadebe, Uche Okeke'],
  ['/subjects/culture/sources/','📚 Sources','What to read — ethnography, art history, literature, oral tradition scholarship'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Culture Under Pressure</h2>';
echo '<p>Igbo culture has survived enormous pressure — the Atlantic slave trade, Christian missionary activity, British colonisation, the civil war, and now the globalising forces of modernity and migration. Much has been lost. Much has adapted. Much endures. The masquerade that colonial missionaries called "pagan idol worship" is performed in diaspora communities in London and Atlanta. The Uli designs that colonial administrators dismissed as primitive decoration now appear in contemporary Nigerian art exhibitions in New York and Lagos. The four-day week that structures Igbo time has survived two centuries of colonial and post-colonial attempts to replace it with the seven-day European week. This subject documents the losses, the adaptations, and what remains vigorously alive. It is cross-linked with <a href="/subjects/tradition/intro/">Tradition</a>, <a href="/subjects/religion/intro/">Religion</a>, and <a href="/subjects/language1/intro/">Language</a>.</p>';
echo '<div style="display:flex;gap:10px;flex-wrap:wrap;margin:28px 0 4px;">';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/tradition/overview/">→ Tradition</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/history/overview/">→ History</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/religion/overview/">→ Religion</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/language1/overview/">→ Language</a>';
echo '</div>';
echo '</div>';
<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">The Igbo language has never been adequately written. The Roman alphabet — borrowed from a European tradition designed for entirely different sounds — captures perhaps 30% of what Igbo actually says. This subject documents the history of that inadequacy and the efforts to correct it.</p>';
echo '<h2>About the Igbo Language</h2>';
echo '<p>Igbo (<em>Asụsụ Igbo</em>) is a tonal language of the Niger-Congo family spoken by over 45 million people, primarily in southeastern Nigeria. It is one of Nigeria\'s three major languages alongside Hausa and Yoruba, and one of the most phonologically rich languages in West Africa. Its tonal system — with high tone, low tone, and downstep — means that the same sequence of consonants and vowels spoken at different pitches carries completely different meanings. Its consonant inventory includes sounds — labial-velar stops, nasalised fricatives, aspirated stops — that have no equivalent in European phonology and for which the Roman alphabet has no ready representation.</p>';
echo '<h2>The Writing Problem</h2>';
echo '<p>Igbo has been written in Roman script since the missionary period of the 19th century. The Önwu orthography of 1961 — still the standard — uses 24 of the 26 Roman letters (dropping Q and X) supplemented by eight additional characters (ị, ọ, ụ, ṅ) and several digraphs (gb, kp, nw, ny, gh, ch). This system represents a significant improvement over earlier missionary orthographies. It remains, however, fundamentally inadequate to the full phoneme inventory of the Igbo language.</p>';
echo '<p>The core problem is this: the Roman alphabet was designed to represent the sounds of Latin and its descendants. Igbo phonology is structurally different from any European language. Forcing Igbo into 24 Roman letters — even supplemented by digraphs and diacritics — produces a written form that cannot distinguish between sounds that native speakers hear as clearly different. The letter H is particularly significant: when it follows a consonant in the current orthography, it should signal a nasalised variant of that consonant — but this principle is inconsistently applied and not systematically recognised in the standard. The full range of Igbo phonemes, if properly represented, would require more than 70 distinct letters or letter combinations.</p>';
echo '<h2>What This Subject Covers</h2>';
echo '<p>Language1 covers the history of Igbo writing: Nsịbịdị (the pre-colonial sign system), Ndebe (the contemporary indigenous script), and the evolution of the Roman orthography from missionary transcriptions to the Önwu standard. It documents the phoneme gap — the systematic under-representation of Igbo sounds in the current writing system — and the scholarly and community efforts to address it. The digraph and trigraph question — the need for letter combinations that the current standard does not recognise — is documented here as an active area of linguistic work.</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/language1/overview/','🗺️ Overview','Linguistic classification, phonology, tones, grammar, and the writing systems'],
  ['/subjects/language1/topics/','🏛️ Key Topics','The phoneme gap, Nsịbịdị, Ndebe, the Önwu orthography, trigraphs, endangerment'],
  ['/subjects/language1/people/','👤 People','Ogbalu, Williamson, Emenanjo, Kamalu Uchenna, the Nsukka scholars'],
  ['/subjects/language1/sources/','📚 Sources','Dictionaries, grammars, orthography documents, learning resources'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Our Approach</h2>';
echo '<p>This subject is written from inside the language it documents. It treats the phoneme gap not as a technical curiosity but as a political and cultural problem: a writing system that cannot adequately represent a language is a writing system that silences the language\'s full expressiveness. The digraph and trigraph analysis documented in Topics represents original scholarship developed for this platform — work in progress that will be expanded as the research develops. This subject is cross-linked with <a href="/subjects/language2/intro/">Language2</a> (grammar and usage) and <a href="/subjects/culture/intro/">Culture</a>.</p>';
echo '</div>';
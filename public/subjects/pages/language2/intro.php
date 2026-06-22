<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">Language2 is the living complement to Language1. Where Language1 documents the history and theory of how Igbo is written, Language2 documents how Igbo is actually used — how sentences are built, how conversations work, how the language moves between formality and intimacy, and how a learner can begin to inhabit it.</p>';
echo '<h2>What This Track Covers</h2>';
echo '<p>Igbo is not a language that can be learned from grammar rules alone. It is a language of relationship — the appropriate greeting, the correct proverb for the occasion, the tonal distinction that separates a request from a demand, the register shift that signals respect to an elder or warmth to a peer. Grammar is the skeleton; usage is the flesh; interaction is the life. Language2 documents all three.</p>';
echo '<p>The grammar pages cover the foundational structures: nouns (which do not inflect for number or gender), pronouns (which carry tonal information), verbs (which mark aspect rather than tense), the serial verb construction (multiple verbs in sequence without conjunctions), and the tonal system as it operates in actual sentences. The usage pages document Igbo in context: greetings and their moral weight, the kola nut prayer as oral literature, names and their meanings, proverbs and their rhetorical function, and the patterns of code-switching between Igbo, Nigerian Pidgin, and English that characterise everyday speech in contemporary Igboland and the diaspora.</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/language2/overview/','🗺️ Overview','Grammar — nouns, pronouns, verbs, tones, serial verbs, sentence structure'],
  ['/subjects/language2/topics/','🏛️ Key Topics','Greetings, names, proverbs, kola nut prayer, code-switching, diaspora Igbo'],
  ['/subjects/language2/people/','👤 People','Ogbalu, Williamson, Emenanjo, Achebe on language, diaspora language communities'],
  ['/subjects/language2/sources/','📚 Sources','Grammars, dictionaries, learning platforms, audio resources'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Our Approach</h2>';
echo '<p>Language2 is written for learners and for native speakers who want to understand their language more analytically. It does not assume prior knowledge of linguistics. It uses Igbo examples throughout, with translations and explanations. Where the grammar is complex — particularly the tonal system and the aspect-marking verb system — it explains rather than assumes. This subject is cross-linked with <a href="/subjects/language1/intro/">Language1</a> (writing systems and phoneme history) and <a href="/subjects/culture/intro/">Culture</a> (the social contexts in which language operates).</p>';
echo '<div style="display:flex;gap:10px;flex-wrap:wrap;margin:28px 0 4px;">';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/language1/overview/">→ Language I</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/culture/overview/">→ Culture</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/history/overview/">→ History</a>';
echo '</div>';
echo '</div>';
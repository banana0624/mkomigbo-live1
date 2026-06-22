<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">Tradition in Igbo life is not the past preserved in amber. It is the accumulated moral intelligence of generations, kept alive through practice — and it is in continuous negotiation with the present.</p>';
echo '<h2>What Omenala Is</h2>';
echo '<p>In Igbo life, tradition — <em>omenala</em> or <em>ọdinala</em> — is not merely custom. It is the accumulated moral, social, and spiritual wisdom of generations, encoded in law, ritual, proverb, and communal practice. It governs how people relate to each other, to the land, to the ancestors, and to the forces of the spirit world. It is simultaneously descriptive (this is how things are), prescriptive (this is how things should be), and aspirational (this is the standard against which we measure ourselves).</p>';
echo '<p>The word <em>ọdinala</em> itself encodes this: <em>ọ dị n\'ala</em> — "it is in the ground," "it is on the land." Tradition is not something carried in the head or preserved in books; it is something rooted in the earth, in the specific land that a community inhabits and that their ancestors cultivated, suffered on, and are buried in. This is why diaspora communities find the maintenance of tradition so demanding: the land is absent, and much of what tradition means depends on the land.</p>';
echo '<p>Igbo tradition is not static. It has always been adaptive — absorbing new influences while maintaining core values. Christianity, colonialism, the civil war, urban migration, and diaspora dispersal have all transformed Igbo traditional practice without eliminating it. What makes it "tradition" is not its unchangingness but its rootedness: the reference back to what was established by the ancestors as the foundation for what is done today. This reference backward is not nostalgia; it is a method of ethical reasoning.</p>';
echo '<h2>The Architecture of Igbo Tradition</h2>';
echo '<p>Eight institutions structure Igbo traditional life: <em>Ọdinala</em> (the spiritual and cosmological framework); <em>Omenani</em> (social customs and norms); <em>Ọlụ oji</em> (the kola nut tradition); <em>Ichu ọfọ</em> (the title and authority system); <em>Ọzọ</em> and <em>Nze</em> (the title societies); <em>Ụmụnna</em> and <em>Ụmụada</em> (the patrilineage and daughters of the lineage); <em>Otu ọgbọ</em> (age grades); and <em>Mmanwu</em> (masquerade). Each of these is documented in depth in the pages that follow.</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/tradition/overview/','⚖️ Overview','The structure of Igbo traditional life — institutions, values, and organising principles'],
  ['/subjects/tradition/topics/','🥜 Key Traditions','Mmanwu, the New Yam Festival, kola nut, age grades, title systems, markets'],
  ['/subjects/tradition/people/','👤 Tradition Keepers','Elders, dibias, titled men and women, and cultural custodians'],
  ['/subjects/tradition/sources/','📚 Sources','Texts, oral literature, and references for Igbo tradition'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Tradition Under Pressure</h2>';
echo '<p>Igbo tradition has survived colonial suppression, missionary condemnation, civil war, urban migration, and diaspora dispersal. It survives not because it is rigid but because it is meaningful — because it answers questions that modernity cannot: how should authority be organised without kings? How should the dead be honoured? How should the community adjudicate disputes without courts? How should the young be initiated into adult responsibility? These are questions that every human community must answer, and Igbo tradition\'s answers are as sophisticated as any in the world. This subject is cross-linked with <a href="/subjects/culture/intro/">Culture</a>, <a href="/subjects/religion/african/">Religion: African</a>, and <a href="/subjects/esoterism/intro/">Esoterism</a>.</p>';
echo '<div style="display:flex;gap:10px;flex-wrap:wrap;margin:28px 0 4px;">';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/culture/overview/">→ Culture</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/history/overview/">→ History</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/religion/overview/">→ Religion</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/esoterism/overview/">→ Esoterism</a>';
echo '</div>';
echo '</div>';
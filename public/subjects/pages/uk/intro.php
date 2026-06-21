<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">The Igbo and Nigerian presence in Britain is not immigration. It is the return of people whose labour, land, and resources built British modernity — arriving now in the metropole that their ancestors\' exploitation made possible.</p>';
echo '<h2>Igbo in the United Kingdom</h2>';
echo '<p>The United Kingdom is home to one of the largest Igbo diaspora communities outside Nigeria. Estimates of the total Nigerian-origin population in Britain range from 500,000 to 700,000, with a significant proportion of Igbo descent — concentrated in London (particularly Peckham, Lewisham, Barking, and Tottenham), Birmingham, Manchester, and Leeds. Their presence spans over a century: from the first Nigerian students at British universities in the early 20th century, through the post-independence professional migration of the 1960s and 1970s, through the civil war refugees and the economic migrants of the 1980s and 1990s, to the contemporary generation born in Britain who are Nigerian in heritage and British in formation.</p>';
echo '<p>The relationship between Nigeria and Britain is not a relationship between equals. Britain colonised Nigeria. British companies extracted Nigerian palm oil, rubber, tin, and eventually oil for over a century. British administrators drew the borders that created the Nigerian state and created the structural imbalances that produced the Biafra War. British arms supplied the federal government during that war. British immigration law has periodically made the Nigerian presence in Britain unwelcome, hostile, or precarious. The Nigerian community in Britain inhabits this history whether it chooses to or not — and its achievements are made against it.</p>';
echo '<h2>Navigate the Subject</h2>';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin:16px 0;">';
$sections = [
  ['/subjects/uk/overview/','🗺️ Overview','Historical arc from early students to the contemporary diaspora'],
  ['/subjects/uk/topics/','🏛️ Key Topics','Race and racism, education and achievement, community organisation, identity, Biafra memory'],
  ['/subjects/uk/people/','👤 People','Equiano, Buchi Emecheta, Ben Okri, Bernardine Evaristo, contemporary figures'],
  ['/subjects/uk/sources/','📚 Sources','Literature, academic studies, journalism, community resources'],
];
foreach($sections as [$href,$title,$desc]) {
  echo '<a href="'.$href.'" style="display:block;padding:14px;border:1px solid #e5e7eb;border-radius:12px;text-decoration:none;color:inherit;background:#fff;transition:box-shadow .12s;" onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.09)\'" onmouseout="this.style.boxShadow=\'\'">';
  echo '<div style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:4px;">'.$title.'</div>';
  echo '<div style="font-size:.82rem;color:#6b7280;line-height:1.4;">'.$desc.'</div>';
  echo '</a>';
}
echo '</div>';
echo '<h2>Our Approach</h2>';
echo '<p>This subject is written from inside the community it documents. It does not treat the Nigerian and Igbo presence in Britain as a problem to be explained or a phenomenon to be managed. It treats it as a permanent feature of British society — one with deep historical roots, complex internal diversity, and its own political and cultural agency. This subject is cross-linked with <a href="/subjects/europe/intro/">Europe</a> and <a href="/subjects/struggles/intro/">Struggles</a>.</p>';
echo '</div>';
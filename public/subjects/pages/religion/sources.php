<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">Sacred texts, scholarly references, and further reading across all religious traditions.</p>';
echo '<h2>Sacred Texts by Tradition</h2>';
echo '<table style="width:100%;border-collapse:collapse;font-size:.88rem;">';
echo '<tr style="border-bottom:2px solid #e5e7eb;"><th style="text-align:left;padding:8px 10px;">Tradition</th><th style="text-align:left;padding:8px 10px;">Primary Scripture(s)</th><th style="text-align:left;padding:8px 10px;">Notes</th></tr>';
$texts = [
['Kemet (Egyptian)','Book of the Dead, Pyramid Texts, Coffin Texts, Amduat','Pyramid Texts (c. 2400 BCE) are the oldest religious texts in the world'],
['Mesopotamian','Epic of Gilgamesh, Enuma Elish, Descent of Inanna','Gilgamesh contains the oldest flood narrative, predating the biblical account'],
['Hinduism','Vedas, Upanishads, Bhagavad Gita, Mahabharata, Ramayana, Puranas','Over 100 texts; Bhagavad Gita is most widely read worldwide'],
['Buddhism','Pali Canon (Theravada), Mahayana Sutras, Tibetan Book of the Dead','Dhammapada is most accessible entry point'],
['Jainism','Agamas (canonical texts), Tattvartha Sutra','Largely preserved in Prakrit language'],
['Sikhism','Guru Granth Sahib','Treated as living Guru; 1,430 pages; in Gurmukhi script'],
['Confucianism','The Analects, Four Books, Five Classics','Analects are the most direct record of Confucius\'s teaching'],
['Taoism','Tao Te Ching, Zhuangzi, Liezi, Daozang','Tao Te Ching is one of the most translated books in history'],
['Shinto','Kojiki (712 CE), Nihon Shoki (720 CE)','No single canon; tradition is primarily ritual, not scriptural'],
['Zoroastrianism','The Avesta (Gathas, Yasna, Yashts, Vendidad)','Only ~25% of original Avesta survives'],
['Judaism','Torah, Tanakh, Talmud, Midrash, Zohar (Kabbalah)','Talmud (Babylonian) is the central text of Rabbinic Judaism'],
['Christianity','The Bible (Old and New Testaments)','66 books (Protestant), 73 (Catholic), more (Orthodox)'],
['Islam','The Quran, Hadith collections (Bukhari, Muslim, etc.)','Quran in Arabic considered the literal word of God; untranslatable in the strict sense'],
['Yoruba/Ifá','Ifá corpus (256 Odù, 800+ verses each)','Oral tradition; inscribed on UNESCO Intangible Heritage list'],
['Ọdinala (Igbo)','No written scripture; transmitted through ritual, oral literature, masquerade','Living oral tradition'],
['Baháʼí','Kitáb-i-Aqdas, Hidden Words, Seven Valleys, Writings of ʻAbdu\'l-Bahá','All written by Baháʼu\'lláh or authorized interpreters'],
['Mormonism (LDS)','Bible (KJV), Book of Mormon, Doctrine and Covenants, Pearl of Great Price','Four standard works accepted as scripture'],
['Rastafari','The Bible (especially Psalms, Revelation, Old Testament)','Read through Afrocentric lens; no separate scripture'],
['Cao Dai','The Divine Path to God (Thánh Ngôn Hiệp Tuyển)','Compiled from spirit communications received through mediums'],
['Eckankar','Shariyat-Ki-Sugmad, works of Paul Twitchell and Harold Klemp','Claim to ancient knowledge revealed through ECK Masters'],
['Spiritism','The Spirits\' Book, Mediums\' Book, Gospel According to Spiritism','Five core works of Allan Kardec'],
['Wicca','Book of Shadows (personal), Drawing Down the Moon','No central canon; Gardner\'s and Valiente\'s work most foundational'],
];
foreach($texts as [$trad,$texts_str,$notes]) {
  echo '<tr style="border-bottom:1px solid #f0f0f0;vertical-align:top;">';
  echo '<td style="padding:9px 10px;font-weight:700;color:#111;">'.$trad.'</td>';
  echo '<td style="padding:9px 10px;color:#374151;">'.$texts_str.'</td>';
  echo '<td style="padding:9px 10px;color:#6b7280;font-size:.82rem;">'.$notes.'</td>';
  echo '</tr>';
}
echo '</table>';
echo '<h2 style="margin-top:28px;">Key Academic Works</h2>';
echo '<ul>';
echo '<li><strong>Mircea Eliade</strong> — <em>The Sacred and the Profane</em> (1957); <em>A History of Religious Ideas</em> (3 vols). The foundational academic framework for comparative religion.</li>';
echo '<li><strong>Karen Armstrong</strong> — <em>A History of God</em> (1993); <em>The Battle for God</em> (2000). Accessible scholarly history of the Abrahamic traditions.</li>';
echo '<li><strong>Huston Smith</strong> — <em>The World\'s Religions</em> (1958, rev. 1991). The most widely used introductory text in comparative religion courses.</li>';
echo '<li><strong>Ninian Smart</strong> — <em>The World\'s Religions</em> (1989). Systematic framework using the "seven dimensions of religion."</li>';
echo '<li><strong>Joseph Campbell</strong> — <em>The Hero with a Thousand Faces</em> (1949). Comparative mythology; the monomyth across religious traditions.</li>';
echo '<li><strong>Rudolf Otto</strong> — <em>The Idea of the Holy</em> (1917). Phenomenology of religious experience; the concept of the "numinous."</li>';
echo '<li><strong>Max Weber</strong> — <em>The Sociology of Religion</em> (1920). Religious traditions analyzed as social and economic forces.</li>';
echo '<li><strong>Émile Durkheim</strong> — <em>The Elementary Forms of Religious Life</em> (1912). Religion as social phenomenon.</li>';
echo '</ul>';
echo '<h2>Direct Download Links — Sacred Texts</h2>';
echo '<p>All texts below are freely available. Click any link to read online or download.</p>';
echo '<table style="width:100%;border-collapse:collapse;font-size:.88rem;">';
echo '<tr style="border-bottom:2px solid #e5e7eb;"><th style="text-align:left;padding:8px 10px;">Text</th><th style="text-align:left;padding:8px 10px;">Tradition</th><th style="text-align:left;padding:8px 10px;">Free Link</th></tr>';
$download_texts = [
  ['Book of the Dead','Kemet (Egyptian)','<a href="https://www.sacred-texts.com/egy/ebod/index.htm" target="_blank">sacred-texts.com</a>'],
  ['Pyramid Texts','Kemet (Egyptian)','<a href="https://www.sacred-texts.com/egy/pyt/index.htm" target="_blank">sacred-texts.com</a>'],
  ['Epic of Gilgamesh','Mesopotamian','<a href="https://www.sacred-texts.com/ane/gilgamesh/index.htm" target="_blank">sacred-texts.com</a>'],
  ['Enuma Elish','Mesopotamian','<a href="https://www.sacred-texts.com/ane/enuma.htm" target="_blank">sacred-texts.com</a>'],
  ['The Vedas (4 vols)','Hinduism','<a href="https://www.sacred-texts.com/hin/index.htm" target="_blank">sacred-texts.com</a>'],
  ['Bhagavad Gita','Hinduism','<a href="https://www.sacred-texts.com/hin/gita/index.htm" target="_blank">sacred-texts.com</a> | <a href="https://www.globalgreyebooks.com/bhagavad-gita-ebook.html" target="_blank">Global Grey</a>'],
  ['Upanishads','Hinduism','<a href="https://www.sacred-texts.com/hin/upan/index.htm" target="_blank">sacred-texts.com</a>'],
  ['Dhammapada','Buddhism','<a href="https://www.sacred-texts.com/bud/dhp/index.htm" target="_blank">sacred-texts.com</a> | <a href="https://www.buddhanet.net/pdf_file/scrndhamma.pdf" target="_blank">BuddhaNet PDF</a>'],
  ['Tibetan Book of the Dead','Buddhism','<a href="https://www.sacred-texts.com/bud/bardo/index.htm" target="_blank">sacred-texts.com</a> | <a href="https://www.globalgreyebooks.com/tibetan-book-of-the-dead-ebook.html" target="_blank">Global Grey</a>'],
  ['Pali Canon (Theravada)','Buddhism','<a href="https://www.accesstoinsight.org" target="_blank">accesstoinsight.org</a> | <a href="https://www.buddhanet.net" target="_blank">BuddhaNet</a>'],
  ['Tao Te Ching','Taoism','<a href="https://www.sacred-texts.com/tao/taote.htm" target="_blank">sacred-texts.com</a> | <a href="https://www.globalgreyebooks.com/tao-te-ching-ebook.html" target="_blank">Global Grey</a>'],
  ['The Analects of Confucius','Confucianism','<a href="https://www.sacred-texts.com/cfu/index.htm" target="_blank">sacred-texts.com</a>'],
  ['Guru Granth Sahib','Sikhism','<a href="https://www.sacred-texts.com/skh/index.htm" target="_blank">sacred-texts.com</a> | <a href="https://www.srigranth.org" target="_blank">srigranth.org</a>'],
  ['The Avesta','Zoroastrianism','<a href="https://www.sacred-texts.com/zor/index.htm" target="_blank">sacred-texts.com</a>'],
  ['Torah and Tanakh','Judaism','<a href="https://www.sefaria.org" target="_blank">sefaria.org</a> — Hebrew and English'],
  ['Talmud (Babylonian)','Judaism','<a href="https://www.sefaria.org/texts/Talmud" target="_blank">sefaria.org</a>'],
  ['Zohar (Kabbalah)','Judaism','<a href="https://www.sacred-texts.com/jud/index.htm" target="_blank">sacred-texts.com</a> | <a href="https://www.sefaria.org/texts/Kabbalah" target="_blank">sefaria.org</a>'],
  ['The Bible (multiple translations)','Christianity','<a href="https://www.biblegateway.com" target="_blank">biblegateway.com</a> | <a href="https://www.sacred-texts.com/bib/index.htm" target="_blank">sacred-texts.com</a>'],
  ['New Testament Apocrypha','Christianity','<a href="https://www.sacred-texts.com/chr/apo/index.htm" target="_blank">sacred-texts.com</a>'],
  ['The Quran (multiple translations)','Islam','<a href="https://quran.com" target="_blank">quran.com</a> — audio and text | <a href="https://www.sacred-texts.com/isl/index.htm" target="_blank">sacred-texts.com</a>'],
  ['Ifá Corpus (selections)','Yoruba / Ifá','<a href="https://archive.org/search?query=ifa+corpus+yoruba" target="_blank">archive.org</a>'],
  ['Kitab-i-Aqdas','Baháʼí','<a href="https://www.bahai.org/library/authoritative-texts/bahaullah/kitab-i-aqdas/" target="_blank">bahai.org [FREE]</a>'],
  ['All Baháʼí writings','Baháʼí','<a href="https://www.bahai.org/library/" target="_blank">bahai.org [FREE — complete library]</a>'],
  ['The Spirits Book — Allan Kardec','Spiritism','<a href="https://www.sacred-texts.com/nth/sbook/index.htm" target="_blank">sacred-texts.com</a> | <a href="https://www.globalgreyebooks.com/spirits-book-ebook.html" target="_blank">Global Grey</a>'],
  ['Book of Mormon','Mormonism (LDS)','<a href="https://www.churchofjesuschrist.org/study/scriptures/bofm" target="_blank">churchofjesuschrist.org [FREE]</a>'],
  ['Divine Path to God (Cao Dai)','Cao Dai','<a href="https://archive.org/search?query=cao+dai+divine+path" target="_blank">archive.org</a>'],
];
foreach($download_texts as $r) {
  echo '<tr style="border-bottom:1px solid #f0f0f0;vertical-align:top;">';
  echo '<td style="padding:9px 10px;font-weight:700;color:#111;">'.$r[0].'</td>';
  echo '<td style="padding:9px 10px;color:#374151;">'.$r[1].'</td>';
  echo '<td style="padding:9px 10px;">'.$r[2].'</td>';
  echo '</tr>';
}
echo '</table>';

echo '<h2 style="margin-top:28px;">Free Digital Libraries — Religious and Sacred Texts</h2>';
echo '<ul>';
echo '<li><a href="https://www.sacred-texts.com" target="_blank"><strong>Internet Sacred Text Archive</strong></a> — the most comprehensive free library of sacred and religious texts online; all traditions, no registration required</li>';
echo '<li><a href="https://archive.org" target="_blank"><strong>Internet Archive</strong></a> — millions of free books including rare religious, theological, and spiritual works; download in PDF, ePub, Kindle</li>';
echo '<li><a href="https://www.globalgreyebooks.com" target="_blank"><strong>Global Grey Ebooks</strong></a> — beautifully formatted free PDF and ePub of classic spiritual texts including Bhagavad Gita, Tao Te Ching, Tibetan Book of the Dead, Spirits Book</li>';
echo '<li><a href="https://www.holybooks.com" target="_blank"><strong>HolyBooks.com</strong></a> — free PDF downloads of sacred texts from all world religions</li>';
echo '<li><a href="https://www.sefaria.org" target="_blank"><strong>Sefaria</strong></a> — Jewish texts in Hebrew and English; Torah, Talmud, Midrash, Kabbalah — all free</li>';
echo '<li><a href="https://quran.com" target="_blank"><strong>Quran.com</strong></a> — Quran with multiple translations, transliteration, and audio recitation</li>';
echo '<li><a href="https://www.buddhanet.net" target="_blank"><strong>BuddhaNet</strong></a> — Buddhist texts, sutras, and teachings across all traditions</li>';
echo '<li><a href="https://www.accesstoinsight.org" target="_blank"><strong>Access to Insight</strong></a> — Theravada Buddhist texts; the complete Pali Canon in English</li>';
echo '<li><a href="https://www.bahai.org/library/" target="_blank"><strong>Baháʼí Reference Library</strong></a> — complete Baháʼí writings free online</li>';
echo '<li><a href="https://gnosis.org/naghamm/nhl.html" target="_blank"><strong>Nag Hammadi Library</strong></a> — complete Gnostic gospels and texts free at gnosis.org</li>';
echo '<li><a href="https://www.gutenberg.org" target="_blank"><strong>Project Gutenberg</strong></a> — public domain religious and theological texts</li>';
echo '<li><a href="https://www.wisdomlib.org" target="_blank"><strong>Wisdom Library</strong></a> — Hindu, Buddhist, and Jain texts with scholarly annotation</li>';
echo '<li><a href="https://www.britannica.com/topic/religion" target="_blank"><strong>Encyclopaedia Britannica — Religion</strong></a> — reliable reference for all religious traditions</li>';
echo '</ul>';

echo '<h2 style="margin-top:28px;">Online Resources</h2>';
echo '<ul>';
echo '<li><strong>sacred-texts.com</strong> — free online library of sacred texts from all traditions</li>';
echo '<li><strong>Encyclopaedia Britannica</strong> — britannica.com — reliable reference for all religious traditions</li>';
echo '<li><strong>World Religion Database</strong> — academic subscription database</li>';
echo '<li><strong>BuddhaNet</strong> — buddhanet.net — Buddhist texts and resources</li>';
echo '<li><strong>Sefaria</strong> — sefaria.org — Jewish texts online in Hebrew and English</li>';
echo '<li><strong>Quran.com</strong> — Quran with multiple translations and audio</li>';
echo '</ul>';
echo '<div style="display:flex;gap:10px;flex-wrap:wrap;margin:24px 0 4px;">';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/religion/overview/">Full Map</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/religion/people/">Founders</a>';
echo '</div></div>';

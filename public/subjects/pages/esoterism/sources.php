<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">Primary texts of the esoteric traditions — with free download links where available.</p>';
echo '<p>All texts marked <strong>[FREE]</strong> are available as free downloads at the linked source. Sacred-texts.com is the most comprehensive free library of esoteric and religious texts in the world — entirely free, no registration required.</p>';

echo '<h2>Western Esoteric Texts</h2>';
echo '<table style="width:100%;border-collapse:collapse;font-size:.88rem;">';
echo '<tr style="border-bottom:2px solid #e5e7eb;"><th style="text-align:left;padding:8px 10px;">Text</th><th style="text-align:left;padding:8px 10px;">Tradition</th><th style="text-align:left;padding:8px 10px;">Date</th><th style="text-align:left;padding:8px 10px;">Download</th></tr>';
$western_texts = [
  ['Corpus Hermeticum','Hermeticism','c. 100–300 CE','<a href="https://sacred-texts.com/egy/herm/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Emerald Tablet (Tabula Smaragdina)','Hermeticism/Alchemy','c. 8th century CE','<a href="https://sacred-texts.com/alc/emerald.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Kybalion','Hermeticism','1908 CE','<a href="https://sacred-texts.com/eso/kyb/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Gospel of Thomas','Gnosticism','1st–2nd century CE','<a href="https://sacred-texts.com/chr/thomas.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Pistis Sophia','Gnosticism','3rd–4th century CE','<a href="https://sacred-texts.com/chr/ps/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Apocryphon of John','Gnosticism (Sethian)','2nd century CE','<a href="https://gnosis.org/naghamm/apocjn.html" target="_blank" rel="noopener">gnosis.org [FREE]</a>'],
  ['Nag Hammadi Library (complete)','Gnosticism','2nd–4th century CE','<a href="https://gnosis.org/naghamm/nhl.html" target="_blank" rel="noopener">gnosis.org [FREE]</a>'],
  ['The Zohar (selections)','Kabbalah','c. 1280 CE','<a href="https://sacred-texts.com/jud/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Sefer Yetzirah (Book of Formation)','Kabbalah','c. 2nd–6th century CE','<a href="https://sacred-texts.com/jud/sy/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Enneads by Plotinus','Neoplatonism','c. 270 CE','<a href="https://sacred-texts.com/cla/plotenn/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Paracelsus — Selected Works','Alchemy','16th century CE','<a href="https://sacred-texts.com/alc/paracel/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Chymical Wedding of Christian Rosenkreutz','Rosicrucianism','1616 CE','<a href="https://sacred-texts.com/sro/rcia/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Fama Fraternitatis','Rosicrucianism','1614 CE','<a href="https://sacred-texts.com/sro/rcc/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Morals and Dogma — Albert Pike','Freemasonry','1871 CE','<a href="https://sacred-texts.com/mas/md/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Secret Doctrine — H.P. Blavatsky','Theosophy','1888 CE','<a href="https://sacred-texts.com/the/sd/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Isis Unveiled — H.P. Blavatsky','Theosophy','1877 CE','<a href="https://sacred-texts.com/the/iu/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Key to Theosophy — Blavatsky','Theosophy','1889 CE','<a href="https://sacred-texts.com/the/kt/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Voice of the Silence — Blavatsky','Theosophy','1889 CE','<a href="https://sacred-texts.com/the/vs/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Book of the Law (Liber AL) — Crowley','Thelema','1904 CE','<a href="https://sacred-texts.com/oto/engccxx.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Book of Lies — Crowley','Thelema/Golden Dawn','1913 CE','<a href="https://sacred-texts.com/oto/bl/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['777 and Other Qabalistic Writings — Crowley','Golden Dawn/Thelema','1909 CE','<a href="https://archive.org/search?query=crowley+777" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['Three Books of Occult Philosophy — Agrippa','Western Occultism','1531 CE','<a href="https://sacred-texts.com/eso/agrippa/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Golden Dawn — Israel Regardie','Golden Dawn','1937 CE','<a href="https://archive.org/search?query=regardie+golden+dawn" target="_blank" rel="noopener">archive.org [FREE]</a>'],
];
foreach($western_texts as $r) {
  echo '<tr style="border-bottom:1px solid #f0f0f0;vertical-align:top;">';
  echo '<td style="padding:8px 10px;font-weight:700;">'.$r[0].'</td>';
  echo '<td style="padding:8px 10px;color:#553c9a;">'.$r[1].'</td>';
  echo '<td style="padding:8px 10px;color:#888;">'.$r[2].'</td>';
  echo '<td style="padding:8px 10px;">'.$r[3].'</td>';
  echo '</tr>';
}
echo '</table>';

echo '<h2 style="margin-top:28px;">Eastern Mystical Texts</h2>';
echo '<table style="width:100%;border-collapse:collapse;font-size:.88rem;">';
echo '<tr style="border-bottom:2px solid #e5e7eb;"><th style="text-align:left;padding:8px 10px;">Text</th><th style="text-align:left;padding:8px 10px;">Tradition</th><th style="text-align:left;padding:8px 10px;">Date</th><th style="text-align:left;padding:8px 10px;">Download</th></tr>';
$eastern_texts = [
  ['Masnavi-ye Ma\'navi — Rumi','Sufism','13th century CE','<a href="https://sacred-texts.com/isl/masnavi/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Kitab al-Tawasin — Al-Hallaj','Sufism','10th century CE','<a href="https://sacred-texts.com/isl/hallaj/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Ihya Ulum al-Din — Al-Ghazali','Sufism/Islamic Mysticism','11th century CE','<a href="https://archive.org/search?query=ihya+ulum+al-din+ghazali" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['Fusus al-Hikam — Ibn Arabi','Sufism','13th century CE','<a href="https://archive.org/search?query=fusus+al-hikam+ibn+arabi" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['The Bhagavad Gita','Hinduism/Vedanta','c. 200 BCE','<a href="https://sacred-texts.com/hin/gita/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Upanishads (principal 13)','Hinduism/Vedanta','c. 800–200 BCE','<a href="https://sacred-texts.com/hin/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Shiva Sutras','Kashmir Shaivism','c. 9th century CE','<a href="https://sacred-texts.com/hin/shivasutra.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Vijnanabhairava Tantra','Tantra/Kashmir Shaivism','c. 7th century CE','<a href="https://archive.org/search?query=vijnanabhairava+tantra" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['Dhammapada','Buddhism','c. 3rd century BCE','<a href="https://sacred-texts.com/bud/dhp.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Tibetan Book of the Dead (Bardo Thodol)','Vajrayana Buddhism/Bön','8th century CE','<a href="https://sacred-texts.com/bud/tib/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Heart Sutra','Mahayana Buddhism','1st–2nd century CE','<a href="https://sacred-texts.com/bud/tib/heartsut.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Diamond Sutra','Mahayana Buddhism','c. 4th century CE','<a href="https://sacred-texts.com/bud/tib/diam.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Tao Te Ching — Laozi','Taoism','c. 400 BCE','<a href="https://sacred-texts.com/tao/taote.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Zhuangzi','Taoism','c. 4th century BCE','<a href="https://sacred-texts.com/tao/creed.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Analects — Confucius','Confucianism','c. 5th century BCE','<a href="https://sacred-texts.com/cfu/conf1.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Guru Granth Sahib (English translation)','Sikhism','1604 CE (final 1708)','<a href="https://sacred-texts.com/skh/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Avesta (Gathas of Zarathustra)','Zoroastrianism','c. 1500–600 BCE','<a href="https://sacred-texts.com/zor/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
];
foreach($eastern_texts as $r) {
  echo '<tr style="border-bottom:1px solid #f0f0f0;vertical-align:top;">';
  echo '<td style="padding:8px 10px;font-weight:700;">'.$r[0].'</td>';
  echo '<td style="padding:8px 10px;color:#276749;">'.$r[1].'</td>';
  echo '<td style="padding:8px 10px;color:#888;">'.$r[2].'</td>';
  echo '<td style="padding:8px 10px;">'.$r[3].'</td>';
  echo '</tr>';
}
echo '</table>';

echo '<h2 style="margin-top:28px;">African Spiritual Texts</h2>';
echo '<table style="width:100%;border-collapse:collapse;font-size:.88rem;">';
echo '<tr style="border-bottom:2px solid #e5e7eb;"><th style="text-align:left;padding:8px 10px;">Text / Source</th><th style="text-align:left;padding:8px 10px;">Tradition</th><th style="text-align:left;padding:8px 10px;">Access</th></tr>';
$african_texts = [
  ['The Book of the Dead (Papyrus of Ani)','Kemet','<a href="https://sacred-texts.com/egy/ebod/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Pyramid Texts','Kemet','<a href="https://sacred-texts.com/egy/pyt/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['On Isis and Osiris — Plutarch','Kemet (Greek account)','<a href="https://sacred-texts.com/cla/plu/mor/mor360.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Popol Vuh (Maya creation narrative)','Maya Religion','<a href="https://sacred-texts.com/nam/maya/pvgm/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Ifá: An Exposition — Wande Abimbola','Yoruba Ifá','Academic library / purchase — no free download available'],
  ['Ifá Will Mend Our Broken World — Abimbola','Yoruba Ifá','Purchase — Aim Books'],
  ['Kongo: Power and Majesty — catalogue','Kongo religion','Metropolitan Museum of Art publication'],
  ['Things Fall Apart — Chinua Achebe','Ọdinala (Igbo)','Purchase — primary literary source on Igbo traditional life'],
  ['Arrow of God — Chinua Achebe','Ọdinala (Igbo)','Purchase — deepest literary treatment of Igbo religion'],
  ['Victor Uchendu — The Igbo of Southeast Nigeria','Ọdinala (Igbo)','Academic library'],
];
foreach($african_texts as $r) {
  echo '<tr style="border-bottom:1px solid #f0f0f0;vertical-align:top;">';
  echo '<td style="padding:8px 10px;font-weight:700;">'.$r[0].'</td>';
  echo '<td style="padding:8px 10px;color:#2d6a1f;">'.$r[1].'</td>';
  echo '<td style="padding:8px 10px;">'.$r[2].'</td>';
  echo '</tr>';
}
echo '</table>';

echo '<h2 style="margin-top:28px;">Ancient Religious Texts</h2>';
echo '<table style="width:100%;border-collapse:collapse;font-size:.88rem;">';
echo '<tr style="border-bottom:2px solid #e5e7eb;"><th style="text-align:left;padding:8px 10px;">Text</th><th style="text-align:left;padding:8px 10px;">Tradition</th><th style="text-align:left;padding:8px 10px;">Download</th></tr>';
$ancient_texts = [
  ['The Epic of Gilgamesh','Mesopotamian','<a href="https://sacred-texts.com/ane/eog/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Enuma Elish (Babylonian Creation Epic)','Mesopotamian','<a href="https://sacred-texts.com/ane/enuma.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Descent of Inanna','Sumerian','<a href="https://sacred-texts.com/ane/inanna.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Hesiod — Theogony (Greek Gods)','Greek Religion','<a href="https://sacred-texts.com/cla/hesiod/theogony.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Homeric Hymns','Greek Religion','<a href="https://sacred-texts.com/cla/homer/hymns.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Prose Edda — Snorri Sturluson','Norse Religion','<a href="https://sacred-texts.com/neu/pre/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Poetic Edda','Norse Religion','<a href="https://sacred-texts.com/neu/poe/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Popol Vuh','Maya Religion','<a href="https://sacred-texts.com/nam/maya/pvgm/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
];
foreach($ancient_texts as $r) {
  echo '<tr style="border-bottom:1px solid #f0f0f0;vertical-align:top;">';
  echo '<td style="padding:8px 10px;font-weight:700;">'.$r[0].'</td>';
  echo '<td style="padding:8px 10px;color:#b7791f;">'.$r[1].'</td>';
  echo '<td style="padding:8px 10px;">'.$r[2].'</td>';
  echo '</tr>';
}
echo '</table>';

echo '<h2 style="margin-top:28px;">Abrahamic Sacred Texts</h2>';
echo '<table style="width:100%;border-collapse:collapse;font-size:.88rem;">';
echo '<tr style="border-bottom:2px solid #e5e7eb;"><th style="text-align:left;padding:8px 10px;">Text</th><th style="text-align:left;padding:8px 10px;">Tradition</th><th style="text-align:left;padding:8px 10px;">Download</th></tr>';
$abrahamic_texts = [
  ['The Torah / Hebrew Bible (Tanakh)','Judaism','<a href="https://sacred-texts.com/jud/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Talmud (Babylonian, selections)','Judaism','<a href="https://sacred-texts.com/jud/t01/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Zohar (selections)','Kabbalah/Judaism','<a href="https://sacred-texts.com/jud/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Bible (King James Version)','Christianity','<a href="https://sacred-texts.com/bib/kjv/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['New Testament (multiple translations)','Christianity','<a href="https://biblegateway.com" target="_blank" rel="noopener">biblegateway.com [FREE]</a>'],
  ['The Quran (Yusuf Ali translation)','Islam','<a href="https://sacred-texts.com/isl/quran/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Quran (multiple translations + audio)','Islam','<a href="https://quran.com" target="_blank" rel="noopener">quran.com [FREE]</a>'],
  ['Sahih Bukhari (Hadith)','Islam','<a href="https://sacred-texts.com/isl/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Book of Mormon','Mormonism','<a href="https://churchofjesuschrist.org/study/scriptures/bofm" target="_blank" rel="noopener">churchofjesuschrist.org [FREE]</a>'],
  ['The Kitab-i-Aqdas — Baha\'u\'llah','Baháʼí Faith','<a href="https://bahai.org/library/authoritative-texts" target="_blank" rel="noopener">bahai.org [FREE]</a>'],
  ['The Hidden Words — Baha\'u\'llah','Baháʼí Faith','<a href="https://sacred-texts.com/bhi/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Spirits\' Book — Allan Kardec','Spiritism','<a href="https://sacred-texts.com/spi/spirits/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
];
foreach($abrahamic_texts as $r) {
  echo '<tr style="border-bottom:1px solid #f0f0f0;vertical-align:top;">';
  echo '<td style="padding:8px 10px;font-weight:700;">'.$r[0].'</td>';
  echo '<td style="padding:8px 10px;color:#2b6cb0;">'.$r[1].'</td>';
  echo '<td style="padding:8px 10px;">'.$r[2].'</td>';
  echo '</tr>';
}
echo '</table>';

echo '<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:16px;margin:24px 0;">';
echo '<h3 style="margin-top:0;">Primary Free Libraries</h3>';
echo '<ul style="margin:0;">';
echo '<li><a href="https://sacred-texts.com" target="_blank" rel="noopener"><strong>sacred-texts.com</strong></a> — the most comprehensive free library of religious and esoteric texts on the internet. No registration. No cost. Thousands of texts.</li>';

echo '<h2 style="margin-top:28px;">Sant Mat — Path of the Masters</h2>';
echo '<p>Sant Mat is the esoteric tradition of the divine Sound Current (Shabd/Naam) transmitted through a living master. Most Sant Mat texts are freely available by tradition — Kirpal Singh declared all his works copyright-free.</p>';
echo '<table style="width:100%;border-collapse:collapse;font-size:.88rem;">';
echo '<tr style="border-bottom:2px solid #e5e7eb;"><th style="text-align:left;padding:8px 10px;">Text</th><th style="text-align:left;padding:8px 10px;">Author</th><th style="text-align:left;padding:8px 10px;">Date</th><th style="text-align:left;padding:8px 10px;">Download</th></tr>';
$sant_texts = [
  ['Songs of Kabir','Kabir (trans. Tagore)','c. 1500 CE','<a href="https://www.sacred-texts.com/hin/sbk/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Bijak of Kabir','Kabir','c. 1500 CE','<a href="https://archive.org/details/bijak00kabi" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['Japji Sahib','Guru Nanak','1539 CE','<a href="https://www.sacred-texts.com/skh/granth/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Sar Bachan (Poetry and Prose)','Swami Ji Maharaj','1878 CE','<a href="https://archive.org/details/sarbachan00swam" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['Spiritual Letters','Jaimal Singh','1896-1903 CE','<a href="https://archive.org/details/spiritualletters00sing" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['Spiritual Gems','Sawan Singh','1948 CE','<a href="https://archive.org/details/spiritualgems00sing" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['Philosophy of the Masters (5 vols)','Sawan Singh','1963 CE','<a href="https://archive.org/details/philosophyofmast00sing" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['Path of the Masters','Julian Johnson','1939 CE','<a href="https://archive.org/details/pathofmasters00john" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['With a Great Master in India','Julian Johnson','1934 CE','<a href="https://archive.org/details/withgreatmaster00john" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['Naam or Word','Kirpal Singh','1942 CE','<a href="https://archive.org/details/namorword00sing" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['Crown of Life','Kirpal Singh','1961 CE','<a href="https://archive.org/details/crownoflife00sing" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['Spiritual Elixir','Kirpal Singh','1967 CE','<a href="https://www.ruhanisatsangusa.org/books.htm" target="_blank" rel="noopener">ruhanisatsangusa.org [FREE]</a>'],
  ['All Kirpal Singh books','Kirpal Singh','1942-1974 CE','<a href="https://www.ruhanisatsangusa.org/books.htm" target="_blank" rel="noopener">ruhanisatsangusa.org [FREE — all books]</a>'],
];
foreach($sant_texts as $r) {
  echo '<tr style="border-bottom:1px solid #f0f0f0;vertical-align:top;">';
  echo '<td style="padding:9px 10px;font-weight:700;color:#111;">'.$r[0].'</td>';
  echo '<td style="padding:9px 10px;color:#374151;">'.$r[1].'</td>';
  echo '<td style="padding:9px 10px;color:#888;white-space:nowrap;">'.$r[2].'</td>';
  echo '<td style="padding:9px 10px;">'.$r[3].'</td>';
  echo '</tr>';
}
echo '</table>';

echo '<h2 style="margin-top:28px;">New Thought / Mental Science</h2>';
echo '<p>The New Thought and Mental Science tradition teaches that mind is the primary substance of the universe and that aligning individual mind with Universal Mind produces healing, abundance, and peace.</p>';
echo '<table style="width:100%;border-collapse:collapse;font-size:.88rem;">';
echo '<tr style="border-bottom:2px solid #e5e7eb;"><th style="text-align:left;padding:8px 10px;">Text</th><th style="text-align:left;padding:8px 10px;">Author</th><th style="text-align:left;padding:8px 10px;">Date</th><th style="text-align:left;padding:8px 10px;">Download</th></tr>';
$new_thought_texts = [
  ['Edinburgh Lectures on Mental Science','Thomas Troward','1904 CE','<a href="https://www.sacred-texts.com/nth/elm/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['Dore Lectures on Mental Science','Thomas Troward','1909 CE','<a href="https://archive.org/details/dorelectureson00trouiala" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['The Hidden Power','Thomas Troward','1921 CE','<a href="https://archive.org/details/hiddenpower00trow" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['The Creative Process in the Individual','Thomas Troward','1910 CE','<a href="https://archive.org/details/creativeprocessi00trou" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['In Tune with the Infinite','Ralph Waldo Trine','1897 CE','<a href="https://archive.org/details/intunewithinfinit00trin" target="_blank" rel="noopener">archive.org [FREE]</a> | <a href="https://www.globalgreyebooks.com/in-tune-with-the-infinite-ebook.html" target="_blank" rel="noopener">Global Grey [FREE]</a>'],
  ['Science of Mind','Ernest Holmes','1926 CE','<a href="https://archive.org/details/scienceofmind00holm" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['The Power of Your Subconscious Mind','Joseph Murphy','1963 CE','<a href="https://archive.org/details/powerofyoursubco00murp" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['As a Man Thinketh','James Allen','1903 CE','<a href="https://www.sacred-texts.com/sro/amt/index.htm" target="_blank" rel="noopener">sacred-texts.com [FREE]</a>'],
  ['The Master Key System','Charles Haanel','1912 CE','<a href="https://archive.org/details/masterkeySystem00haan" target="_blank" rel="noopener">archive.org [FREE]</a>'],
  ['All Alice Bailey books (24 vols)','Alice Bailey','1919-1960 CE','<a href="https://www.lucistrust.org/online_books" target="_blank" rel="noopener">lucistrust.org [FREE — all 24 books]</a>'],
];
foreach($new_thought_texts as $r) {
  echo '<tr style="border-bottom:1px solid #f0f0f0;vertical-align:top;">';
  echo '<td style="padding:9px 10px;font-weight:700;color:#111;">'.$r[0].'</td>';
  echo '<td style="padding:9px 10px;color:#374151;">'.$r[1].'</td>';
  echo '<td style="padding:9px 10px;color:#888;white-space:nowrap;">'.$r[2].'</td>';
  echo '<td style="padding:9px 10px;">'.$r[3].'</td>';
  echo '</tr>';
}
echo '</table>';


echo '<h2 style="margin-top:28px;">Free Digital Libraries — Complete Directory</h2>';
echo '<ul>';
echo '<li><a href="https://www.sacred-texts.com" target="_blank" rel="noopener"><strong>Internet Sacred Text Archive</strong></a> — the most comprehensive free library of sacred and esoteric texts online; all traditions, no registration</li>';
echo '<li><a href="https://archive.org" target="_blank" rel="noopener"><strong>Internet Archive</strong></a> — millions of free books; search any author or title; download in PDF, ePub, Kindle</li>';
echo '<li><a href="https://www.globalgreyebooks.com" target="_blank" rel="noopener"><strong>Global Grey Ebooks</strong></a> — beautifully formatted free PDF and ePub of classic spiritual and philosophical texts</li>';
echo '<li><a href="https://www.holybooks.com" target="_blank" rel="noopener"><strong>HolyBooks.com</strong></a> — free PDF downloads of sacred texts from all world religions and traditions</li>';
echo '<li><a href="https://www.gutenberg.org" target="_blank" rel="noopener"><strong>Project Gutenberg</strong></a> — free public domain books including many classic esoteric and mystical works</li>';
echo '<li><a href="https://www.wisdomlib.org" target="_blank" rel="noopener"><strong>Wisdom Library</strong></a> — Hindu, Buddhist, and Jain texts with scholarly annotation</li>';
echo '<li><a href="https://www.hermetics.org" target="_blank" rel="noopener"><strong>Hermetics.org</strong></a> — Western esoteric and Hermetic texts</li>';
echo '<li><a href="https://gnosis.org" target="_blank" rel="noopener"><strong>Gnosis.org</strong></a> — Nag Hammadi library, Gnostic texts, and Western esoteric tradition</li>';
echo '<li><a href="https://www.lucistrust.org/online_books" target="_blank" rel="noopener"><strong>Lucis Trust</strong></a> — all 24 Alice Bailey books free online</li>';
echo '<li><a href="https://rsarchive.org" target="_blank" rel="noopener"><strong>Rudolf Steiner Archive</strong></a> — complete Rudolf Steiner works online</li>';
echo '<li><a href="https://www.ruhanisatsangusa.org/books.htm" target="_blank" rel="noopener"><strong>Ruhani Satsang USA</strong></a> — all Kirpal Singh books free</li>';
echo '<li><a href="https://sefaria.org" target="_blank" rel="noopener"><strong>Sefaria</strong></a> — Jewish texts including Kabbalah, Talmud, Torah in Hebrew and English</li>';
echo '<li><a href="https://www.buddhanet.net" target="_blank" rel="noopener"><strong>BuddhaNet</strong></a> — Buddhist texts, sutras, and teachings</li>';
echo '</ul>';

echo '<li><a href="https://archive.org" target="_blank" rel="noopener"><strong>archive.org (Internet Archive)</strong></a> — millions of free books including rare esoteric and religious texts.</li>';
echo '<li><a href="https://gnosis.org" target="_blank" rel="noopener"><strong>gnosis.org</strong></a> — the Gnostic Society Library; Gnostic texts, Neoplatonic texts, Hermetic texts.</li>';
echo '<li><a href="https://sefaria.org" target="_blank" rel="noopener"><strong>sefaria.org</strong></a> — complete Jewish library: Torah, Talmud, Midrash, Kabbalah, in Hebrew and English.</li>';
echo '<li><a href="https://quran.com" target="_blank" rel="noopener"><strong>quran.com</strong></a> — Quran with multiple translations and audio recitation.</li>';
echo '<li><a href="https://accesstoinsight.org" target="_blank" rel="noopener"><strong>accesstoinsight.org</strong></a> — Theravada Buddhist texts (Pali Canon) in English.</li>';
echo '<li><a href="https://buddhanet.net" target="_blank" rel="noopener"><strong>buddhanet.net</strong></a> — Buddhist texts, audio, and education across all traditions.</li>';
echo '</ul>';
echo '</div>';

echo '<div style="display:flex;gap:10px;flex-wrap:wrap;margin:24px 0 4px;">';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/esoterism/intro/">Introduction</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/esoterism/topics/">Western Esoteric</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/esoterism/eastern/">Eastern Mysticism</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/esoterism/african/">African Esoteric</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/religion/sources/">→ Religion Sources</a>';
echo '</div></div>';

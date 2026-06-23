<?php
declare(strict_types=1);
echo '<div class="mk-prose">';
echo '<p class="mk-muted" style="margin-top:0;">Masters, initiates, teachers, and key figures across all esoteric traditions — with links to their works.</p>';

echo '<h2>Free Libraries — Esoteric Texts Online</h2>';
echo '<p>The following free digital libraries provide access to thousands of esoteric, spiritual, and religious texts:</p>';
echo '<ul>';
echo '<li><strong><a href="https://www.sacred-texts.com" target="_blank">Internet Sacred Text Archive</a></strong> — the most comprehensive free library of sacred and esoteric texts online; Hermeticism, Kabbalah, Sufism, Hinduism, Buddhism, and hundreds more traditions</li>';
echo '<li><strong><a href="https://archive.org" target="_blank">Internet Archive</a></strong> — millions of free books including rare and out-of-print esoteric works; search by author or title</li>';
echo '<li><strong><a href="https://www.globalgreyebooks.com" target="_blank">Global Grey Ebooks</a></strong> — free PDF and ePub downloads of classic spiritual and philosophical texts; excellent formatting and selection</li>';
echo '<li><strong><a href="https://www.holybooks.com" target="_blank">HolyBooks.com</a></strong> — free PDF downloads of sacred texts from all world religions and esoteric traditions</li>';
echo '<li><strong><a href="https://www.gutenberg.org" target="_blank">Project Gutenberg</a></strong> — free public domain books including many classic esoteric and mystical works</li>';
echo '<li><strong><a href="https://www.wisdomlib.org" target="_blank">Wisdom Library</a></strong> — specialized library of Hindu, Buddhist, and Jain texts with scholarly annotation</li>';
echo '<li><strong><a href="https://www.hermetics.org" target="_blank">Hermetics.org</a></strong> — dedicated library of Hermetic, Rosicrucian, and Western esoteric texts</li>';
echo '</ul>';

$people = [
  // Ancient
  ['c. 1–300 CE','Hermes Trismegistus','Hermeticism','Legendary synthesis of Hermes and Thoth. The Corpus Hermeticum is attributed to him. May represent a tradition rather than a single person. The foundational figure of Western esoterism. Free: <a href="https://www.sacred-texts.com/chr/herm/index.htm">Corpus Hermeticum</a> at sacred-texts.com.'],
  ['c. 205–270 CE','Plotinus','Neoplatonism','The greatest philosopher of late antiquity. His Enneads describe the structure of divine reality (The One, Nous, Psyche) and the soul\'s path of return. Free: <a href="https://www.sacred-texts.com/cla/plotenn/index.htm">The Enneads</a> at sacred-texts.com.'],
  ['c. 233–305 CE','Porphyry','Neoplatonism','Student and biographer of Plotinus. Edited and published the Enneads. His Life of Plotinus is the primary source on Plotinus. Free: <a href="https://archive.org/details/lifeofplotinus00plot">Life of Plotinus</a> at archive.org.'],
  ['c. 245–325 CE','Iamblichus','Neoplatonism / Theurgy','Syrian Neoplatonist who developed theurgy. His De Mysteriis is the foundational text of Neoplatonic ritual practice. Free: <a href="https://archive.org/details/thyurgiaofiambli00holm">De Mysteriis</a> at archive.org.'],
  ['c. 412–485 CE','Proclus','Neoplatonism','The last great systematic Neoplatonist. His Elements of Theology is the most rigorous logical account of Neoplatonic metaphysics. Free: <a href="https://archive.org/details/elementsoftheolo00proc">Elements of Theology</a> at archive.org.'],
  ['c. 500 CE','Pseudo-Dionysius','Christian Mysticism / Neoplatonism','Anonymous author of foundational Christian mystical texts. The Mystical Theology is the foundational text of Christian apophatic theology. Free: <a href="https://www.sacred-texts.com/chr/dion/index.htm">Works</a> at sacred-texts.com.'],

  // Sufi masters
  ['c. 717–801 CE','Rabia al-Adawiyya','Sufism','The first great female Sufi saint. Introduced divine love (mahabbah) as the path to God. Free: <a href="https://archive.org/details/rabi-ah-the-mystic-and-her-fellow-saints-in-islam">Rabia the Mystic</a> at archive.org.'],
  ['857–922 CE','Al-Hallaj','Sufism','Persian Sufi mystic executed for declaring "Ana\'l-Haqq" (I am the Truth). Free: <a href="https://archive.org/details/kitabal-tawasin">Kitab al-Tawasin</a> at archive.org.'],
  ['1058–1111 CE','Al-Ghazali','Islamic Mysticism','The most important figure in the reconciliation of Sufism with orthodox Islam. Free: <a href="https://www.sacred-texts.com/isl/mishkat/index.htm">Mishkat al-Anwar</a> at sacred-texts.com.'],
  ['1165–1240 CE','Ibn Arabi','Sufism','The "Greatest Master." His Wahdat al-Wujud (Unity of Being) is the most sophisticated metaphysical formulation in Islamic thought. Free: <a href="https://archive.org/details/bezelsofwisdom00arabi">Fusus al-Hikam</a> at archive.org.'],
  ['1207–1273 CE','Jalal ad-Din Rumi','Sufism','The greatest mystical poet of the Islamic world. The Masnavi (25,000 verses) is the supreme work of Persian Sufi literature. Free: <a href="https://www.sacred-texts.com/isl/masnavi/index.htm">Masnavi Book I</a> at sacred-texts.com.'],

  // Kabbalah
  ['c. 1240–1305 CE','Moses de León','Kabbalah','Primary author/compiler of the Zohar — the central text of Kabbalah. Free: <a href="https://www.sacred-texts.com/jud/zdm/index.htm">Zohar selections</a> at sacred-texts.com.'],

  // Renaissance
  ['1445–1510 CE','Marsilio Ficino','Renaissance Hermeticism','Florentine philosopher who translated the Corpus Hermeticum into Latin (1463). Free: <a href="https://archive.org/details/three-books-on-life-ficino">Three Books on Life</a> at archive.org.'],
  ['1463–1494 CE','Giovanni Pico della Mirandola','Renaissance Kabbalah','First systematically combined Kabbalah with Christian theology. Free: <a href="https://archive.org/details/oration-on-the-dignity-of-man">Oration on the Dignity of Man</a> at archive.org.'],
  ['1493–1541 CE','Paracelsus','Alchemy / Medicine','Swiss-German physician and alchemist who revolutionized medicine and alchemy. Free: <a href="https://www.sacred-texts.com/alc/paracel.htm">Selected Works</a> at sacred-texts.com.'],
  ['1534–1600 CE','Giordano Bruno','Hermeticism / Cosmology','Burned at the stake by the Roman Inquisition in 1600. A martyr of both science and esoterism. Free: <a href="https://archive.org/details/heroicfrenzies00brun">Heroic Frenzies</a> at archive.org.'],
  ['1575–1624 CE','Jakob Böhme','Christian Mysticism / Theosophy','German shoemaker whose mystical illumination produced profound texts on God, creation, good and evil. Free: <a href="https://www.sacred-texts.com/chr/bohme/index.htm">Works</a> at sacred-texts.com.'],

  // Modern Western
  ['1724–1804 CE','Immanuel Swedenborg','Christian Mysticism','Swedish scientist who claimed to visit heaven and hell in visionary states. Free: <a href="https://www.sacred-texts.com/swd/index.htm">Heaven and Hell and other works</a> at sacred-texts.com.'],
  ['1831–1891 CE','Helena Petrovna Blavatsky','Theosophy','Co-founder of the Theosophical Society. The most influential single figure in modern Western esoterism. Free: <a href="https://www.sacred-texts.com/the/sd/index.htm">The Secret Doctrine</a>, <a href="https://www.sacred-texts.com/the/ivu/index.htm">Isis Unveiled</a> at sacred-texts.com.'],
  ['1861–1925 CE','Rudolf Steiner','Anthroposophy','Founder of Anthroposophy with applications in Waldorf education, biodynamic agriculture, and medicine. Free: <a href="https://rsarchive.org">Rudolf Steiner Archive</a> — complete works online.'],
  ['1875–1947 CE','Aleister Crowley','Thelema / Golden Dawn','The most controversial figure in modern Western occultism. Free: <a href="https://www.sacred-texts.com/oto/index.htm">Works including Book of the Law</a> at sacred-texts.com.'],
  ['1880–1949 CE','Alice Bailey','Theosophy / New Age','Claimed to receive teachings from a Tibetan Master. All 24 books freely available at <a href="https://www.lucistrust.org/online_books">lucistrust.org</a>.'],
  ['1907–1985 CE','Israel Regardie','Golden Dawn','Published the complete Golden Dawn system (1937–1940). Free: <a href="https://archive.org/details/golden-dawn-regardie">The Golden Dawn</a> at archive.org.'],

  // Sant Mat lineage
  ['c. 1440–1518 CE','Kabir','Sant Mat / Bhakti','Weaver-poet saint of Varanasi whose verses bridge Hindu and Islamic mysticism. His dohas teach the inner path through the divine Sound Current. The foundational voice of the Sant tradition. Free: <a href="https://www.sacred-texts.com/hin/sbk/index.htm">Songs of Kabir</a> (trans. Tagore) at sacred-texts.com; <a href="https://archive.org/details/bijak00kabi">The Bijak</a> at archive.org.'],
  ['1469–1539 CE','Guru Nanak','Sant Mat / Sikhism','Founder of Sikhism. His Japji Sahib is one of the most concentrated expressions of Sant Mat philosophy. Free: <a href="https://www.sacred-texts.com/skh/granth/index.htm">Guru Granth Sahib</a> at sacred-texts.com.'],
  ['1763–1843 CE','Tulsi Sahib','Sant Mat','Saint of Hathras; reviver of the Sant Mat tradition in the 19th century. His Ghat Ramayana and Shabdavali describe the inner path through the Sound Current. Free: <a href="https://archive.org/details/ghataramayana00tuls">Ghat Ramayana</a> at archive.org.'],
  ['1818–1878 CE','Swami Ji Maharaj','Sant Mat / Radhasoami','Founded the Radhasoami Satsang at Agra (1861). His Sar Bachan is the most complete written account of the inner regions traversed in Surat Shabd Yoga. Free: <a href="https://archive.org/details/sarbachan00swam">Sar Bachan</a> at archive.org.'],
  ['1839–1903 CE','Jaimal Singh','Sant Mat / Beas','Disciple of Swami Ji Maharaj; founded Radhasoami Satsang Beas. Initiated Sawan Singh. A soldier-saint. Free: <a href="https://archive.org/details/spiritualletters00sing">Spiritual Letters</a> at archive.org.'],
  ['1858–1948 CE','Sawan Singh','Sant Mat / Beas','"The Great Master" of Beas. Initiated both Julian Johnson and Kirpal Singh. Free: <a href="https://archive.org/details/spiritualgems00sing">Spiritual Gems</a>, <a href="https://archive.org/details/philosophyofmast00sing">Philosophy of the Masters</a> (5 vols) at archive.org.'],
  ['1873–1939 CE','Julian Johnson','Sant Mat / New Thought','American physician and Baptist minister; disciple of Sawan Singh at Beas. Made Sant Mat accessible to English-speaking seekers. Free: <a href="https://archive.org/details/pathofmasters00john">Path of the Masters</a> (1939), <a href="https://archive.org/details/withgreatmaster00john">With a Great Master in India</a> (1934) at archive.org.'],
  ['1894–1974 CE','Kirpal Singh','Sant Mat / Ruhani Satsang','Disciple of Sawan Singh; founded Ruhani Satsang. The most internationally known Sant Mat master of the 20th century. Declared all his works free from copyright. Free: <a href="https://www.ruhanisatsangusa.org/books.htm">All books</a> at ruhanisatsangusa.org; <a href="https://archive.org/details/crownoflife00sing">Crown of Life</a>, <a href="https://archive.org/details/namorword00sing">Naam or Word</a> at archive.org.'],

  // Mental Science / New Thought
  ['1847–1916 CE','Thomas Troward','Mental Science / New Thought','British judge in India whose Edinburgh Lectures on Mental Science (1904) are the most philosophically rigorous texts of the New Thought movement. Argued that mind is the primary substance of the universe. Free: <a href="https://www.sacred-texts.com/nth/elm/index.htm">Edinburgh Lectures</a> at sacred-texts.com; <a href="https://archive.org/details/dorelectureson00trouiala">Dore Lectures</a>, <a href="https://archive.org/details/hiddenpower00trow">The Hidden Power</a> at archive.org.'],
  ['1862–1932 CE','Ralph Waldo Trine','New Thought','Author of In Tune with the Infinite (1897) — one of the bestselling books of its era. Free: <a href="https://archive.org/details/intunewithinfinit00trin">In Tune with the Infinite</a> at archive.org; also at <a href="https://www.globalgreyebooks.com/in-tune-with-the-infinite-ebook.html">Global Grey Ebooks</a>.'],
  ['1887–1960 CE','Ernest Holmes','Religious Science / Science of Mind','Founder of Religious Science. His Science of Mind (1926) synthesizes New Thought, Emerson, Troward, and Eastern philosophy. Free: <a href="https://archive.org/details/scienceofmind00holm">Science of Mind</a> at archive.org.'],

  // Martinism
  ['1743–1803 CE','Louis-Claude de Saint-Martin','Martinism','"The Unknown Philosopher." Transformed Martinism from a ritual into a purely inner path. Free: <a href="https://archive.org/details/oferrorsandtruth00sain">Of Errors and Truth</a> at archive.org.'],

  // Kashmir Shaivism
  ['950–1020 CE','Abhinavagupta','Kashmir Shaivism','The greatest philosopher of Kashmir Shaivism. His Tantraloka is the most comprehensive treatment of Tantric philosophy. Free: <a href="https://archive.org/details/pratyabhijnahrida00abhi">Pratyabhijnahridayam</a> at archive.org.'],

  // Spiritism
  ['1804–1869 CE','Allan Kardec','Spiritism','French educator who compiled spirit communications into a systematic philosophy of reincarnation. ~15 million followers, primarily in Brazil. Free: <a href="https://www.sacred-texts.com/nth/sbook/index.htm">The Spirits Book</a> at sacred-texts.com; all five works at <a href="https://www.globalgreyebooks.com">Global Grey Ebooks</a>.'],

  // Classical attribution
  ['c. 800 BCE','Homer (attributed)','Greek Mystery Religion','The Iliad and Odyssey encode, according to esoteric interpreters, teachings about the soul\'s descent and return. Free: <a href="https://www.sacred-texts.com/cla/homer/index.htm">Complete works</a> at sacred-texts.com.'],
];

echo '<table style="width:100%;border-collapse:collapse;font-size:.87rem;">';
echo '<tr style="border-bottom:2px solid #e5e7eb;"><th style="text-align:left;padding:8px 10px;">Period</th><th style="text-align:left;padding:8px 10px;">Name</th><th style="text-align:left;padding:8px 10px;">Tradition</th><th style="text-align:left;padding:8px 10px;">Significance &amp; Works</th></tr>';
foreach($people as $r) {
  echo '<tr style="border-bottom:1px solid #f0f0f0;vertical-align:top;">';
  echo '<td style="padding:9px 10px;color:#888;white-space:nowrap;font-size:.8rem;">'.$r[0].'</td>';
  echo '<td style="padding:9px 10px;font-weight:800;color:#111;white-space:nowrap;">'.$r[1].'</td>';
  echo '<td style="padding:9px 10px;color:#553c9a;font-size:.82rem;">'.$r[2].'</td>';
  echo '<td style="padding:9px 10px;color:#374151;line-height:1.6;">'.$r[3].'</td>';
  echo '</tr>';
}
echo '</table>';

echo '<h2 style="margin-top:28px;">Additional Free Libraries</h2>';
echo '<ul>';
echo '<li><a href="https://www.holybooks.com" target="_blank">HolyBooks.com</a> — free PDF downloads across all traditions</li>';
echo '<li><a href="https://www.globalgreyebooks.com" target="_blank">Global Grey Ebooks</a> — beautifully formatted free spiritual classics in PDF and ePub</li>';
echo '<li><a href="https://archive.org" target="_blank">Internet Archive</a> — millions of free books; search any author or title</li>';
echo '<li><a href="https://www.sacred-texts.com" target="_blank">Internet Sacred Text Archive</a> — the definitive free library of sacred and esoteric texts</li>';
echo '<li><a href="https://www.gutenberg.org" target="_blank">Project Gutenberg</a> — public domain texts including many classic esoteric works</li>';
echo '<li><a href="https://www.wisdomlib.org" target="_blank">Wisdom Library</a> — Hindu, Buddhist, and Jain texts with annotation</li>';
echo '<li><a href="https://www.hermetics.org" target="_blank">Hermetics.org</a> — Western esoteric and Hermetic texts</li>';
echo '<li><a href="https://www.lucistrust.org/online_books" target="_blank">Lucis Trust</a> — all Alice Bailey books free online</li>';
echo '<li><a href="https://rsarchive.org" target="_blank">Rudolf Steiner Archive</a> — complete Rudolf Steiner works online</li>';
echo '<li><a href="https://www.ruhanisatsangusa.org/books.htm" target="_blank">Ruhani Satsang USA</a> — all Kirpal Singh books free</li>';
echo '</ul>';

echo '<div style="display:flex;gap:10px;flex-wrap:wrap;margin:24px 0 4px;">';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/esoterism/topics/">Western Esoteric</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/esoterism/eastern/">Eastern Mysticism</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/esoterism/african/">African Esoteric</a>';
echo '<a class="mk-btn mk-btn--ghost" href="/subjects/esoterism/sources/">Texts &amp; Downloads</a>';
echo '</div></div>';
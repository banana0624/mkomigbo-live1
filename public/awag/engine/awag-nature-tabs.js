/* awag-nature-tabs.js v1
 * Tabbed nature panel: Moon, Tides, Winds, Rainfall, Calendar
 * Replaces stacked extended modules with a clean tab interface
 * Region-aware: detects skin from URL
 */
(function () {
  'use strict';

  /* ── Region detection ───────────────────────────────────────── */
  function getRegion() {
    var path = window.location.pathname;
    if (/\/(swahili|malagasy|comorian|mijikenda)\//.test(path)) return 'indian-ocean';
    if (/\/(maasai|kikuyu|luo|oromo|somali|amhara)\//.test(path)) return 'east-africa';
    if (/\/(hausa|wolof|fulani|tuareg|zarma|mandinka|tiv|nupe|igala|idoma|jukun|igbira|berom)\//.test(path)) return 'sahel';
    if (/\/(ijaw|urhobo|isoko|itsekiri|kalabari|nembe|bonny)\//.test(path)) return 'west-africa';
    return 'west-africa';
  }

  /* ── Moon calculator ────────────────────────────────────────── */
  function getMoonPhase(date) {
    var d = date || new Date();
    var y = d.getUTCFullYear(), m = d.getUTCMonth()+1, day = d.getUTCDate();
    if (m < 3) { y--; m += 12; }
    var A = Math.floor(y/100), B = 2-A+Math.floor(A/4);
    var JD = Math.floor(365.25*(y+4716)) + Math.floor(30.6001*(m+1)) + day + B - 1524.5;
    var days = (JD - 2451549.5) % 29.53058867;
    if (days < 0) days += 29.53058867;
    var pct = days / 29.53058867;
    var phase, emoji, name, detail, spiritual;
    if      (pct < 0.03||pct>0.97) { phase=0;emoji='🌑';name='New Moon';      detail='New beginnings. Plant root crops. Rest and plan. Dark sky for stargazing.';spiritual='Islamic month start. Jewish Rosh Chodesh. Igbo month marker. Maasai ceremony timing.';}
    else if (pct < 0.22)           { phase=1;emoji='🌒';name='Waxing Crescent';detail='Energy rising. Plant above-ground crops. Begin new projects and journeys.';spiritual='Good time for starting ventures. Yoruba Ogun offerings. Healing herb collection.';}
    else if (pct < 0.28)           { phase=2;emoji='🌓';name='First Quarter'; detail='Take decisive action. Harvest healing herbs. Good for fishing at night.';spiritual='Hausa market day timing. Efik Ekpe assembly. Community meetings.';}
    else if (pct < 0.47)           { phase=3;emoji='🌔';name='Waxing Gibbous';detail='Crops grow rapidly. Excellent night fishing. Fish rise toward surface.';spiritual='Swahili dhow departure timing. Maasai cattle blessing. Healing ceremony preparations.';}
    else if (pct < 0.53)           { phase=4;emoji='🌕';name='Full Moon';      detail='Peak energy. Highest tides. Best fishing of month. Community ceremonies.';spiritual='Igbo Eke market emphasis. Islamic full moon prayers. Yoruba Oya festival. Oron canoe racing.';}
    else if (pct < 0.72)           { phase=5;emoji='🌖';name='Waning Gibbous';detail='Harvest and store. Dry fish and medicines. Reduce planting activity.';spiritual='Efik Ndem water offering. Wolof Mouride gathering timing. Good for harvest.';}
    else if (pct < 0.78)           { phase=6;emoji='🌗';name='Last Quarter';  detail='Clear land. Cut timber. Cleansing ceremonies. Root medicines most potent.';spiritual='Amhara church fasting day. Maasai elders meeting. Community dispute resolution.';}
    else                           { phase=7;emoji='🌘';name='Waning Crescent';detail='Rest and reflect. Plant root crops. Prepare for new cycle. Medicine store check.';spiritual='Yoruba Ifa consultation. Igbo Ọfọ renewal. Swahili pre-Ramadan preparation.';}
    var illum = Math.round(Math.abs(Math.cos(2*Math.PI*pct))*100);
    return {phase,emoji,name,detail,spiritual,pct:Math.round(pct*100),illumination:illum,daysSince:Math.round(days),daysLeft:Math.round((1-pct)*29.53)};
  }

  function nextEvent(targetPhase) {
    var d = new Date();
    for (var i=1;i<=32;i++) {
      d = new Date(d.getTime() + 86400000);
      if (getMoonPhase(d).phase===targetPhase) return d.toLocaleDateString('en-GB',{weekday:'short',day:'numeric',month:'short'});
    }
    return 'Soon';
  }

  /* ── Tide data by region ────────────────────────────────────── */
  function getTides(region) {
    var d = new Date(), hour = d.getUTCHours();
    var moon = getMoonPhase(d);
    var spring = moon.phase===4||moon.phase===0;
    var neap   = moon.phase===2||moon.phase===6;
    if (region==='sahel') return {icon:'🏞',title:'Inland Rivers',current:'No ocean tides',type:'Seasonal river pulses',detail:'Niger, Senegal, Volta and Hadejia rivers are seasonal. Flooding June–September. Low water December–April. Fish traps most effective at low water.',advisory:'Monitor river levels not tides. Flash floods possible during Sahel storms.'};
    if (region==='east-africa') return {icon:'🏔',title:'Rift Valley Lakes',current:'No ocean tides',type:spring?'Lake levels rising':'Lake levels stable',detail:'Lake Victoria, Turkana, Naivasha, Baringo: no tidal movement. Fish in early morning calm. Winds affect lake fishing more than tides.',advisory:'Wind direction matters more than tides on highland lakes. Afternoon winds make fishing rough.'};
    var ang = (hour/24)*2*Math.PI*2;
    var lm  = 1+(spring?0.35:0);
    var th  = Math.sin(ang)*lm;
    var isH = th>0.3, isL = th<-0.3;
    var nxt = ''; for(var h=1;h<=12;h++){if(Math.sin(((hour+h)/24)*2*Math.PI*2)>0.3){nxt=h+'h';break;}}
    if (region==='indian-ocean') {
      var mo = d.getMonth()+1;
      var kaskazi = mo>=11||mo<=3, kusi = mo>=6&&mo<=9;
      return {icon:isH?'🌊':isL?'🏖':'〰',title:'Indian Ocean Tides',current:isH?'High tide':isL?'Low tide':'Mid tide',type:spring?'Spring tide — large range':neap?'Neap tide — small range':'Normal tide',monsoon:kaskazi?'Kaskazi NE monsoon — dhow season':kusi?'Kusi SE monsoon — cool, excellent fishing':'Inter-monsoon period',nextHigh:nxt||'Now',detail:isH?'Reef fish feeding inshore. Best for casting nets and reef fishing. Mangrove channels accessible.':'Fish move to deeper water. Dhow fishing more productive. Good for long-line fishing.',advisory:spring?'Spring tides: highest highs and lowest lows. Strong reef currents. Take care wading.':'Normal Indian Ocean tidal range. Good conditions for all fishing methods.'};
    }
    return {icon:isH?'🌊':isL?'🏖':'〰',title:'Atlantic / Gulf Tides',current:isH?'High tide':isL?'Low tide':'Mid tide',type:spring?'Spring tide — large range':neap?'Neap tide — small range':'Normal tide',nextHigh:nxt||'Now',detail:isH?'Fish move inshore. Cast nets near coast, estuary and rivermouth. Canoe fishing excellent.':'Fish move offshore. Best for deep-water fishing and trap fishing.',advisory:spring?'Spring tide — highest highs, lowest lows. Excellent fishing. Strong estuarine currents.':'Normal tidal range. Standard fishing conditions for West Africa coast.'};
  }

  /* ── Wind data ──────────────────────────────────────────────── */
  var WINDS = {
    'west-africa':[
      {m:1,n:'NE Harmattan',dir:'Northeast',str:'Moderate–Strong',icon:'🌬',dust:'High',adv:'Dry dusty winds from Sahara. Protect lungs and skin. Good for drying fish, crops, medicines.'},
      {m:2,n:'Harmattan easing',dir:'Variable NE–SW',str:'Light–Moderate',icon:'💨',dust:'Moderate',adv:'Harmattan easing. Humidity rising. First sea breeze returns to coast.'},
      {m:3,n:'SW Monsoon begins',dir:'Southwest',str:'Light',icon:'🌀',dust:'Low',adv:'Warm moist Atlantic air arrives. First clouds and showers. Sailing conditions improving.'},
      {m:4,n:'SW Monsoon',dir:'Southwest',str:'Moderate',icon:'🌀',dust:'Very Low',adv:'SW monsoon brings rains. Rough coastal waters. Avoid open sea fishing.'},
      {m:5,n:'Peak SW Monsoon',dir:'Southwest',str:'Strong',icon:'⛈',dust:'None',adv:'Strongest winds of rainy season. Rough seas. Secure boats. Stay off open water.'},
      {m:6,n:'SW Monsoon',dir:'Southwest',str:'Moderate–Strong',icon:'🌧',dust:'None',adv:'Continued SW monsoon. Occasional squalls. Coastal fishers watch for sudden storms.'},
      {m:7,n:'August Break',dir:'Variable',str:'Light',icon:'🌥',dust:'Low',adv:'Brief lull in winds. Calm period. Excellent for coastal travel and open-sea fishing.'},
      {m:8,n:'Returning SW',dir:'Southwest',str:'Moderate',icon:'🌦',dust:'Low',adv:'SW monsoon returns briefly. Second rains. Good harvest conditions.'},
      {m:9,n:'Transitional',dir:'Variable SW–NE',str:'Light',icon:'🍃',dust:'Low',adv:'Wind shifting NE. Dry season approaching. Good travel and long-distance trading.'},
      {m:10,n:'NE Trade Wind',dir:'Northeast',str:'Light–Moderate',icon:'💨',dust:'Low–Moderate',adv:'NE trade winds strengthen. Dry season. Good for drying crops, fish and medicines.'},
      {m:11,n:'Harmattan',dir:'Northeast',str:'Moderate–Strong',icon:'🌬',dust:'High',adv:'Harmattan arrives from Sahara. Dry and dusty. Protect respiratory health.'},
      {m:12,n:'Peak Harmattan',dir:'Northeast',str:'Strong',icon:'🌬',dust:'Very High',adv:'Peak harmattan. Very cold nights. Strong dusty winds. Protect all living things.'},
      {m:13,n:'Harmattan easing',dir:'NE variable',str:'Light–Moderate',icon:'💨',dust:'Moderate',adv:'Harmattan beginning to ease. Temperatures rising toward new year.'},
    ],
    'indian-ocean':[
      {m:1,n:'Kaskazi NE Monsoon',dir:'Northeast',str:'Moderate–Strong',icon:'⛵',dust:'Low',adv:'NE monsoon (Kaskazi). Reliable dhow sailing winds. Excellent tuna and sailfish season.'},
      {m:2,n:'Peak Kaskazi',dir:'Northeast',str:'Strong',icon:'💨',dust:'Low',adv:'Peak NE monsoon. Best traditional dhow sailing season. Tuna and kingfish peak.'},
      {m:3,n:'Kaskazi ending',dir:'NE to SW',str:'Variable',icon:'🌦',dust:'Low',adv:'NE monsoon ending. Masika rains approaching. Seas becoming rougher.'},
      {m:4,n:'Masika winds',dir:'Southwest',str:'Light–Moderate',icon:'🌧',dust:'None',adv:'Long rains season. Seas rough. Experienced fishers only. Dhows stay in harbour.'},
      {m:5,n:'Pre-Kusi',dir:'Transitional',str:'Light',icon:'🌤',dust:'Low',adv:'Masika rains ending. Seas calming. Octopus and squid season beginning.'},
      {m:6,n:'Kusi SE Monsoon',dir:'Southeast',str:'Moderate',icon:'⛵',dust:'Low',adv:'SE monsoon (Kusi) begins. Cool pleasant winds. Excellent sailing and kingfish season.'},
      {m:7,n:'Peak Kusi',dir:'Southeast',str:'Strong',icon:'💨',dust:'Low',adv:'Peak SE monsoon. Best visibility. Excellent deep-sea fishing. Marlin and sailfish.'},
      {m:8,n:'Kusi',dir:'Southeast',str:'Moderate–Strong',icon:'💨',dust:'Low',adv:'Kusi continues. Cool and clear. Best weather of the Indian Ocean year.'},
      {m:9,n:'Kusi easing',dir:'SE to NE',str:'Light–Moderate',icon:'🌤',dust:'Low',adv:'SE monsoon easing. Lobster season begins. Pre-Vuli transitional period.'},
      {m:10,n:'Pre-Kaskazi',dir:'Variable NE',str:'Light',icon:'🌦',dust:'Low',adv:'Vuli rains beginning. NE monsoon building. Reef fishing excellent before rains.'},
      {m:11,n:'Vuli / NE building',dir:'Northeast',str:'Light–Moderate',icon:'🌧',dust:'Low',adv:'Short Vuli rains. NE monsoon building. Mangrove and reef fishing.'},
      {m:12,n:'Kaskazi arriving',dir:'Northeast',str:'Moderate',icon:'🌬',dust:'Low',adv:'Kaskazi NE monsoon arriving. Dhow season beginning. Warming up after Vuli rains.'},
    ],
    'east-africa':[
      {m:1,n:'SE Trade Wind',dir:'Southeast',str:'Moderate',icon:'💨',dust:'Low',adv:'Short dry season. SE trade winds. Pleasant conditions. Good travel weather.'},
      {m:2,n:'SE Trade Wind',dir:'Southeast',str:'Moderate–Strong',icon:'💨',dust:'Low',adv:'Driest period. SE winds strong. Cool highland nights. Long distances to water.'},
      {m:3,n:'NE Monsoon begins',dir:'Northeast',str:'Light',icon:'🌦',dust:'Low',adv:'Long rains beginning. NE monsoon brings cool moist air from Indian Ocean.'},
      {m:4,n:'Peak Long Rains',dir:'Northeast',str:'Moderate',icon:'⛈',dust:'None',adv:'Peak long rains. Heavy rainfall. Rift Valley cold and wet. Roads muddy.'},
      {m:5,n:'Long Rains easing',dir:'Transitional',str:'Light',icon:'🌧',dust:'Low',adv:'Long rains tapering. Cool pleasant weather returning. Good travel resuming.'},
      {m:6,n:'Cool SE Winds',dir:'Southeast',str:'Moderate',icon:'🌤',dust:'Low',adv:'Cool dry season. SE trade winds from Indian Ocean. Clear skies. Good travel.'},
      {m:7,n:'Cold SE Winds',dir:'Southeast',str:'Strong',icon:'❄️',dust:'Low',adv:'Coldest period. Strong cold SE winds on highlands. Frost possible. Protect livestock.'},
      {m:8,n:'SE Trade Wind',dir:'Southeast',str:'Moderate',icon:'☀️',dust:'Low',adv:'Dry season warming. Good SE winds. Fire risk increasing on dry grassland.'},
      {m:9,n:'Transitional NE',dir:'NE shifting',str:'Light',icon:'🌦',dust:'Low',adv:'Short rains approaching. Winds shifting NE. Humidity increasing.'},
      {m:10,n:'Short Rains NE',dir:'Northeast',str:'Light–Moderate',icon:'🌧',dust:'None',adv:'Short rains. NE monsoon brings moisture from Indian Ocean.'},
      {m:11,n:'Peak Short Rains',dir:'Northeast',str:'Moderate',icon:'⛈',dust:'None',adv:'Peak short rains. Good grass growth for cattle. Roads muddy in valleys.'},
      {m:12,n:'SE Trade returning',dir:'SE shifting',str:'Light',icon:'🌦',dust:'Low',adv:'Short rains ending. SE trade winds returning. Year-end travel improving.'},
    ],
    'sahel':[
      {m:1,n:'Peak Harmattan',dir:'Northeast',str:'Strong',icon:'🌬',dust:'Very High',adv:'Peak harmattan from Sahara. Very cold nights, hot days. Extreme dust. Protect health.'},
      {m:2,n:'Harmattan',dir:'Northeast',str:'Moderate–Strong',icon:'🌬',dust:'High',adv:'Harmattan continues. Still very dry. Cold nights easing slightly.'},
      {m:3,n:'Hot dry winds',dir:'NE to SW',str:'Light',icon:'🔥',dust:'Moderate',adv:'Hottest month. Pre-rain period. First southwesterly sea breeze approaching.'},
      {m:4,n:'Pre-rain squalls',dir:'SW building',str:'Variable',icon:'⛈',dust:'Low',adv:'First violent thunderstorms. Haboob dust storms possible. Temperature relief coming.'},
      {m:5,n:'SW Monsoon begins',dir:'Southwest',str:'Light–Moderate',icon:'🌧',dust:'Low',adv:'Monsoon begins in south. North still dry. First reliable rains.'},
      {m:6,n:'SW Monsoon',dir:'Southwest',str:'Moderate',icon:'🌧',dust:'None',adv:'Rains established. Good farming winds. Locust watch period.'},
      {m:7,n:'Peak SW Monsoon',dir:'Southwest',str:'Moderate–Strong',icon:'⛈',dust:'None',adv:'Peak rains. Strong SW monsoon. Flash floods in wadis. Good crop growth.'},
      {m:8,n:'SW Monsoon easing',dir:'Southwest',str:'Moderate',icon:'🌦',dust:'Low',adv:'Rains tapering. Harvest approaching. Good conditions for open-air work.'},
      {m:9,n:'Transitional',dir:'SW to NE',str:'Light',icon:'🌤',dust:'Low',adv:'Dry season returning. Good travel. Long-distance trading resumes.'},
      {m:10,n:'NE Trade Wind',dir:'Northeast',str:'Light–Moderate',icon:'💨',dust:'Low–Moderate',adv:'Dry season winds. Good drying weather for crops and fish.'},
      {m:11,n:'Early Harmattan',dir:'Northeast',str:'Moderate',icon:'💨',dust:'Moderate–High',adv:'Harmattan building. Increasing dust. Cold nights beginning.'},
      {m:12,n:'Peak Harmattan',dir:'Northeast',str:'Strong',icon:'🌬',dust:'Very High',adv:'Full harmattan. Extreme dryness. Very cold nights. Protect livestock and elderly.'},
    ],
  };

  /* ── Rainfall data by region ────────────────────────────────── */
  var RAINFALL = {
    'west-africa':[
      {m:1,pat:'Deep dry',mm:'0–10',days:0,icon:'☀️',sign:'None',adv:'Deep dry season. No rain expected. Rely on stored water. Rivers at lowest.'},
      {m:2,pat:'Late dry',mm:'10–40',days:2,icon:'🌤',sign:'Isolated thunderstorms',adv:'Occasional showers possible. First rains approaching. Prepare land.'},
      {m:3,pat:'Early rains',mm:'60–120',days:8,icon:'🌦',sign:'Afternoon thunderstorms',adv:'First rains arrive but unreliable. Plant drought-tolerant crops first.'},
      {m:4,pat:'Rainy season',mm:'120–200',days:14,icon:'🌧',sign:'Morning mist, afternoon rain',adv:'Reliable rains. Good planting window. Flash floods possible.'},
      {m:5,pat:'Peak rains',mm:'200–300',days:18,icon:'⛈',sign:'Daily heavy storms',adv:'Heaviest rains. Flooding risk high. Excellent for crops. Avoid river crossings.'},
      {m:6,pat:'Peak rains',mm:'180–280',days:16,icon:'⛈',sign:'Continuous cloud cover',adv:'Continued heavy rains. Waterlogging risk. Hill farms safer than valley.'},
      {m:7,pat:'August break',mm:'60–100',days:8,icon:'🌥',sign:'Clear spells',adv:'Brief dry spell. Good harvesting window. Do not mistake for end of rains.'},
      {m:8,pat:'Second rains',mm:'100–180',days:12,icon:'🌦',sign:'Afternoon storms return',adv:'Second rainy season. Moderate rainfall. Good for late-season crops.'},
      {m:9,pat:'Late rains',mm:'60–120',days:8,icon:'🌦',sign:'Rains becoming irregular',adv:'Rains tapering. Last chance to plant short-season crops.'},
      {m:10,pat:'Dry onset',mm:'10–40',days:3,icon:'🌤',sign:'Isolated showers only',adv:'Dry season returning. Good harvesting and drying weather.'},
      {m:11,pat:'Dry',mm:'0–10',days:1,icon:'☀️',sign:'None',adv:'Dry season. No significant rain. Rivers falling.'},
      {m:12,pat:'Deep dry',mm:'0–5',days:0,icon:'☀️',sign:'None',adv:'Driest month. Harmattan dominates. Protect water sources from evaporation.'},
      {m:13,pat:'Dry',mm:'0–10',days:0,icon:'☀️',sign:'None',adv:'Still dry. Pre-new-year. Begin land preparation. Watch for early rain signs.'},
    ],
    'indian-ocean':[
      {m:1,pat:'Kaskazi dry',mm:'20–40',days:3,icon:'⛵',sign:'Light NE sea breeze',adv:'NE monsoon season. Light showers possible. Good sailing and fishing.'},
      {m:2,pat:'Kaskazi peak',mm:'10–30',days:2,icon:'💨',sign:'Strong NE winds',adv:'Driest Kaskazi month. Best ocean fishing. Dhow trade at peak.'},
      {m:3,pat:'Masika begins',mm:'60–120',days:8,icon:'🌦',sign:'Increasing clouds',adv:'Long rains (Masika) arriving. Seas roughening. Prepare farms.'},
      {m:4,pat:'Peak Masika',mm:'160–240',days:16,icon:'⛈',sign:'Daily heavy rain',adv:'Heaviest rains of year. Flooding possible. Stay near home.'},
      {m:5,pat:'Late Masika',mm:'100–160',days:12,icon:'🌧',sign:'Rains tapering',adv:'Masika rains tapering. Weather improving. Harvest approaching.'},
      {m:6,pat:'Kusi dry',mm:'20–40',days:3,icon:'💨',sign:'SE trade winds',adv:'SE monsoon (Kusi) begins. Cool and dry. Best weather of the year.'},
      {m:7,pat:'Peak Kusi dry',mm:'10–20',days:1,icon:'🌤',sign:'Strong SE winds',adv:'Driest month. Cool and clear. Excellent fishing and sailing.'},
      {m:8,pat:'Kusi dry',mm:'10–20',days:1,icon:'🌤',sign:'SE winds',adv:'Kusi continues. Good weather. Best visibility for reef fishing.'},
      {m:9,pat:'Kusi easing',mm:'20–40',days:3,icon:'🌦',sign:'Winds shifting',adv:'Kusi easing. Pre-Vuli period. Lobster season. Good conditions.'},
      {m:10,pat:'Vuli begins',mm:'60–100',days:8,icon:'🌧',sign:'NE clouds building',adv:'Short rains (Vuli) begin. Good for crops. Reef fishing before rains.'},
      {m:11,pat:'Peak Vuli',mm:'100–160',days:12,icon:'⛈',sign:'Daily afternoon rain',adv:'Peak Vuli rains. Flooding coastal areas. Rough seas at times.'},
      {m:12,pat:'Vuli ending',mm:'40–80',days:6,icon:'🌦',sign:'Rains tapering',adv:'Vuli ending. NE monsoon arriving. Dhow season beginning again.'},
    ],
    'east-africa':[
      {m:1,pat:'Short dry',mm:'20–40',days:3,icon:'☀️',sign:'Light showers possible',adv:'Short dry season. Some showers. Good travel and trading weather.'},
      {m:2,pat:'Dry peak',mm:'10–20',days:1,icon:'🌤',sign:'None',adv:'Driest period. Water stress on cattle. Long distances to water.'},
      {m:3,pat:'Long rains begin',mm:'60–100',days:8,icon:'🌦',sign:'Afternoon thunderstorms',adv:'Long rains arriving. Cool relief. Excellent for grass and crops.'},
      {m:4,pat:'Peak long rains',mm:'160–220',days:16,icon:'⛈',sign:'Daily heavy storms',adv:'Heaviest rains. Flooding in valleys. Rift Valley cold and wet.'},
      {m:5,pat:'Long rains end',mm:'100–160',days:12,icon:'🌧',sign:'Rains tapering',adv:'Rains tapering. Cool pleasant weather. Good travel resuming.'},
      {m:6,pat:'Cool dry',mm:'20–40',days:3,icon:'🌤',sign:'SE trade winds',adv:'Cool dry season. Clear skies. Good travel and long-distance trade.'},
      {m:7,pat:'Cold dry',mm:'10–20',days:1,icon:'❄️',sign:'None',adv:'Coldest month. Frost possible on highlands. Protect crops and livestock.'},
      {m:8,pat:'Dry season',mm:'15–30',days:2,icon:'☀️',sign:'None',adv:'Dry season. Warming up. Fire risk on dry grassland.'},
      {m:9,pat:'Pre-rains',mm:'20–40',days:3,icon:'🌦',sign:'Increasing humidity',adv:'Short rains approaching. Humidity rising. Cattle restless.'},
      {m:10,pat:'Short rains',mm:'60–100',days:8,icon:'🌧',sign:'Afternoon thunderstorms',adv:'Short rains. Good grass growth. Cattle recover condition.'},
      {m:11,pat:'Peak short rains',mm:'100–140',days:12,icon:'⛈',sign:'Daily rain',adv:'Peak short rains. Excellent conditions. Roads muddy in valleys.'},
      {m:12,pat:'Rains ending',mm:'40–80',days:6,icon:'🌦',sign:'Rains tapering',adv:'Short rains ending. Warming up. Year-end harvest and celebrations.'},
    ],
    'sahel':[
      {m:1,pat:'Peak dry',mm:'0mm',days:0,icon:'🌬',sign:'None',adv:'Peak harmattan. No rain. Very cold nights. Protect livestock.'},
      {m:2,pat:'Dry',mm:'0–5mm',days:0,icon:'🌤',sign:'None',adv:'Still dry and dusty. Harmattan easing. Pre-heat season.'},
      {m:3,pat:'Hot dry',mm:'5–20mm',days:1,icon:'🔥',sign:'None',adv:'Hottest month. Pre-rain period. Heat stress on humans and animals.'},
      {m:4,pat:'Pre-rains',mm:'20–60mm',days:3,icon:'🌦',sign:'Violent thunderstorms',adv:'First rains arrive. Violent storms. Temperature relief beginning.'},
      {m:5,pat:'Early rains',mm:'60–100mm',days:6,icon:'🌧',sign:'Regular afternoon rains',adv:'Rains established in south. North getting first rains. Good planting.'},
      {m:6,pat:'Rainy season',mm:'100–160mm',days:10,icon:'🌧',sign:'Daily rains',adv:'Good rains. Farming in full swing. Flash floods in wadis.'},
      {m:7,pat:'Peak rains',mm:'160–220mm',days:14,icon:'⛈',sign:'Daily heavy storms',adv:'Peak rainfall. Flooding risk. Rivers high. Excellent crop growth.'},
      {m:8,pat:'Rains tapering',mm:'100–160mm',days:10,icon:'🌦',sign:'Rains easing',adv:'Rains easing. Harvest approaching. Good weather for open-air work.'},
      {m:9,pat:'Late rains',mm:'40–80mm',days:5,icon:'🌤',sign:'Rains becoming irregular',adv:'Dry season returning. Good travel. Long-distance trading resumes.'},
      {m:10,pat:'Dry onset',mm:'10–30mm',days:2,icon:'☀️',sign:'Isolated showers',adv:'Dry season established. Good travel and trade weather.'},
      {m:11,pat:'Early harmattan',mm:'0–5mm',days:0,icon:'💨',sign:'Dusty winds beginning',adv:'Harmattan building. Increasing dust and dryness.'},
      {m:12,pat:'Peak harmattan',mm:'0mm',days:0,icon:'🌬',sign:'None',adv:'Peak harmattan. Extreme dryness. Very cold nights. Protect all living things.'},
    ],
  };

  /* ── Render tab content ─────────────────────────────────────── */
  function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  function row(label, val) {
    return '<div class="nt-row"><span class="nt-row__label">'+esc(label)+'</span><span class="nt-row__val">'+esc(String(val))+'</span></div>';
  }

  function renderMoon() {
    var m = getMoonPhase();
    var nf = nextEvent(4), nn = nextEvent(0);
    // Moon phase visual bar
    var bar = '<div class="nt-moon-bar">';
    var phases = ['🌑','🌒','🌓','🌔','🌕','🌖','🌗','🌘'];
    phases.forEach(function(p,i){
      bar += '<span class="nt-moon-pip'+(i===m.phase?' nt-moon-pip--active':'')+'">'+p+'</span>';
    });
    bar += '</div>';
    return '<div class="nt-panel" id="nt-panel-moon">'
      +'<div class="nt-big-icon">'+m.emoji+'</div>'
      +'<div class="nt-big-label">'+esc(m.name)+'</div>'
      +bar
      +'<div class="nt-rows">'
      +row('Illumination', m.illumination+'%')
      +row('Days since new moon', m.daysSince+' days')
      +row('Days in cycle', m.daysSince+' / 29.5')
      +row('Next full moon', nf)
      +row('Next new moon', nn)
      +'</div>'
      +'<div class="nt-detail">'+esc(m.detail)+'</div>'
      +'<div class="nt-spiritual"><span class="nt-spiritual__label">Spiritual & Traditional</span>'+esc(m.spiritual)+'</div>'
      +'</div>';
  }

  function renderTides(region) {
    var t = getTides(region);
    var html = '<div class="nt-panel" id="nt-panel-tides">'
      +'<div class="nt-big-icon">'+t.icon+'</div>'
      +'<div class="nt-big-label">'+esc(t.current||t.title)+'</div>'
      +'<div class="nt-rows">'
      +row('Type', t.type);
    if (t.monsoon)   html += row('Monsoon season', t.monsoon);
    if (t.nextHigh && t.nextHigh!=='N/A') html += row('Next high tide', t.nextHigh);
    html += '</div>'
      +'<div class="nt-detail">'+esc(t.detail)+'</div>'
      +'<div class="nt-spiritual"><span class="nt-spiritual__label">Advisory</span>'+esc(t.advisory)+'</div>'
      +'</div>';
    return html;
  }

  function renderWinds(region, monthNo) {
    var data = WINDS[region]||WINDS['west-africa'];
    var w = data[Math.max(0,Math.min(data.length-1,monthNo-1))];
    if (!w) return '';
    return '<div class="nt-panel" id="nt-panel-winds">'
      +'<div class="nt-big-icon">'+w.icon+'</div>'
      +'<div class="nt-big-label">'+esc(w.n)+'</div>'
      +'<div class="nt-rows">'
      +row('Direction', w.dir)
      +row('Strength', w.str)
      +row('Dust / Humidity', w.dust)
      +'</div>'
      +'<div class="nt-detail">'+esc(w.adv)+'</div>'
      +'</div>';
  }

  function renderRainfall(region, monthNo) {
    var data = RAINFALL[region]||RAINFALL['west-africa'];
    var r = data[Math.max(0,Math.min(data.length-1,monthNo-1))];
    if (!r) return '';
    // Visual rain bar
    var maxDays = 18, filled = Math.round((r.days/maxDays)*10);
    var bar = '<div class="nt-rain-bar">';
    for (var i=0;i<10;i++) bar += '<div class="nt-rain-pip'+(i<filled?' nt-rain-pip--on':'')+'"></div>';
    bar += '<span class="nt-rain-bar__label">~'+r.days+' rain days/month</span></div>';
    return '<div class="nt-panel" id="nt-panel-rain">'
      +'<div class="nt-big-icon">'+r.icon+'</div>'
      +'<div class="nt-big-label">'+esc(r.pat)+'</div>'
      +bar
      +'<div class="nt-rows">'
      +row('Expected rainfall', r.mm+' mm')
      +row('Early warning signs', r.sign)
      +'</div>'
      +'<div class="nt-detail">'+esc(r.adv)+'</div>'
      +'</div>';
  }

  /* ── CSS injection ──────────────────────────────────────────── */
  function injectCSS() {
    var css = `
.nt-section{margin:32px 0 48px}
.nt-section__title{font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(245,217,122,.35);margin-bottom:14px}
.nt-tabs{display:flex;gap:0;border-bottom:1px solid rgba(255,255,255,.10);margin-bottom:0;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none}
.nt-tabs::-webkit-scrollbar{display:none}
.nt-tab{padding:8px 9px;font-size:.80rem;font-weight:700;color:rgba(232,224,208,.5);cursor:pointer;border-bottom:2px solid transparent;white-space:nowrap;transition:color .12s,border-color .12s;background:none;border-top:none;border-left:none;border-right:none;font-family:inherit}
.nt-tab:hover{color:rgba(245,217,122,.8)}
.nt-tab--active{color:#f5d97a;border-bottom-color:#f5d97a}
.nt-body{background:rgba(245,217,122,.03);border:1px solid rgba(245,217,122,.10);border-top:none;border-radius:0 0 16px 16px;padding:20px}
.nt-panel{display:none}
.nt-panel--active{display:block}
.nt-big-icon{font-size:2.4rem;line-height:1;margin-bottom:6px}
.nt-big-label{font-size:1.2rem;font-weight:900;color:#f5d97a;margin-bottom:14px}
.nt-moon-bar{display:flex;gap:8px;margin:10px 0 16px;flex-wrap:wrap}
.nt-moon-pip{font-size:1.3rem;opacity:.3;cursor:default}
.nt-moon-pip--active{opacity:1;filter:drop-shadow(0 0 6px rgba(245,217,122,.8))}
.nt-rows{display:flex;flex-direction:column;gap:8px;margin-bottom:14px}
.nt-row{display:flex;gap:10px;flex-wrap:wrap}
.nt-row__label{font-size:.72rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:rgba(232,224,208,.38);min-width:120px}
.nt-row__val{font-size:.9rem;color:rgba(232,224,208,.85)}
.nt-detail{font-size:.88rem;color:rgba(232,224,208,.7);line-height:1.7;padding:12px 0;border-top:1px solid rgba(255,255,255,.07);margin-top:4px}
.nt-spiritual{font-size:.82rem;color:rgba(245,217,122,.6);line-height:1.6;padding:10px 0 0;border-top:1px solid rgba(245,217,122,.08);margin-top:4px}
.nt-spiritual__label{display:block;font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(245,217,122,.35);margin-bottom:4px}
.nt-rain-bar{display:flex;align-items:center;gap:4px;margin:10px 0 16px;flex-wrap:wrap}
.nt-rain-pip{width:20px;height:28px;border-radius:4px;background:rgba(96,216,160,.15);border:1px solid rgba(96,216,160,.2);transition:background .2s}
.nt-rain-pip--on{background:rgba(96,216,160,.7);border-color:rgba(96,216,160,.9)}
.nt-rain-bar__label{font-size:.78rem;color:rgba(232,224,208,.5);margin-left:6px}
@media(max-width:500px){.nt-tab{padding:8px 8px;font-size:.78rem}.nt-big-icon{font-size:2rem}.nt-big-label{font-size:1.05rem}}
`;
    var s = document.createElement('style');
    s.textContent = css;
    document.head.appendChild(s);
  }

  /* ── Init ────────────────────────────────────────────────────── */
  function init() {
    var mount = document.getElementById('awag-extended-mount');
    if (!mount) return;

    var region = getRegion();
    var monthEl = document.querySelector('[data-awag-month]');
    var monthNo = monthEl ? parseInt(monthEl.dataset.awagMonth, 10) : 1;
    if (!monthNo||monthNo<1) monthNo=1;

    injectCSS();

    var tabs = [
      {id:'moon',  icon:'🌙', label:'Moon'},
      {id:'tides', icon:'🌊', label:'Tides'},
      {id:'winds', icon:'💨', label:'Winds'},
      {id:'rain',  icon:'🌧', label:'Rainfall'},
    ];

    var tabHTML = tabs.map(function(t,i){
      return '<button class="nt-tab'+(i===0?' nt-tab--active':'')+'" data-tab="'+t.id+'">'+t.icon+' '+t.label+'</button>';
    }).join('');

    var bodyHTML = '<div class="nt-body">'
      +'<div class="nt-panel nt-panel--active" id="nt-panel-moon">'+renderMoon().replace(/<div class="nt-panel" id="nt-panel-moon">/,'').replace(/<\/div>$/,'')+'</div>'
      +'<div class="nt-panel" id="nt-panel-tides">'+renderTides(region).replace(/<div class="nt-panel" id="nt-panel-tides">/,'').replace(/<\/div>$/,'')+'</div>'
      +'<div class="nt-panel" id="nt-panel-winds">'+renderWinds(region,monthNo).replace(/<div class="nt-panel" id="nt-panel-winds">/,'').replace(/<\/div>$/,'')+'</div>'
      +'<div class="nt-panel" id="nt-panel-rain">'+renderRainfall(region,monthNo).replace(/<div class="nt-panel" id="nt-panel-rain">/,'').replace(/<\/div>$/,'')+'</div>'
      +'</div>';

    mount.innerHTML = '<div class="nt-section">'
      +'<div class="nt-section__title">Nature &amp; Environment — Live</div>'
      +'<div class="nt-tabs">'+tabHTML+'</div>'
      +bodyHTML
      +'</div>';

    // Tab switching
    mount.querySelectorAll('.nt-tab').forEach(function(btn){
      btn.addEventListener('click', function(){
        mount.querySelectorAll('.nt-tab').forEach(function(b){b.classList.remove('nt-tab--active');});
        mount.querySelectorAll('.nt-panel').forEach(function(p){p.classList.remove('nt-panel--active');});
        btn.classList.add('nt-tab--active');
        var panel = document.getElementById('nt-panel-'+btn.dataset.tab);
        if (panel) panel.classList.add('nt-panel--active');
      });
    });

    // Live update every 60s for moon and tides
    setInterval(function(){
      var moonP = document.getElementById('nt-panel-moon');
      var tideP = document.getElementById('nt-panel-tides');
      if (moonP) {
        var active = moonP.classList.contains('nt-panel--active');
        var tmp = document.createElement('div');
        tmp.innerHTML = renderMoon();
        var inner = tmp.querySelector('#nt-panel-moon');
        if (inner) { moonP.innerHTML = inner.innerHTML; }
        if (active) moonP.classList.add('nt-panel--active');
      }
      if (tideP) {
        var active2 = tideP.classList.contains('nt-panel--active');
        var tmp2 = document.createElement('div');
        tmp2.innerHTML = renderTides(region);
        var inner2 = tmp2.querySelector('#nt-panel-tides');
        if (inner2) { tideP.innerHTML = inner2.innerHTML; }
        if (active2) tideP.classList.add('nt-panel--active');
      }
    }, 60000);
  }

  if (document.readyState==='loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
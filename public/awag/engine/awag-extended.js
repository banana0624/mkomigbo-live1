/* awag-extended.js v2 — Moon, Tides, Winds, Rainfall
 * Region-aware: detects skin from URL and serves correct data
 * Regions: west-africa (default), east-africa-rift, indian-ocean-coast, sahel
 */
(function () {
  'use strict';

  /* ── Detect region from URL ─────────────────────────────────── */
  function getRegion() {
    var path = window.location.pathname;
    if (/\/(swahili|malagasy|comorian|mijikenda)\//.test(path)) return 'indian-ocean';
    if (/\/(maasai|kikuyu|luo|oromo|somali|amhara)\//.test(path)) return 'east-africa';
    if (/\/(hausa|wolof|fulani|tuareg|zarma|mandinka)\//.test(path)) return 'sahel';
    return 'west-africa'; // default: Igbo, Yoruba, Ibibio, Akan etc.
  }

  /* ── Moon phase calculator (works for all regions) ─────────── */
  function getMoonPhase(date) {
    var d = date || new Date();
    var year = d.getUTCFullYear(), month = d.getUTCMonth()+1, day = d.getUTCDate();
    if (month < 3) { year--; month += 12; }
    var A = Math.floor(year/100), B = 2-A+Math.floor(A/4);
    var JD = Math.floor(365.25*(year+4716)) + Math.floor(30.6001*(month+1)) + day + B - 1524.5;
    var days = (JD - 2451549.5) % 29.53058867;
    if (days < 0) days += 29.53058867;
    var pct = days / 29.53058867;
    var phase, emoji, name, detail;
    if (pct < 0.03 || pct > 0.97)  { phase=0; emoji='🌑'; name='New Moon';        detail='Dark sky. Plant root crops. New beginnings. Rest period.'; }
    else if (pct < 0.22)            { phase=1; emoji='🌒'; name='Waxing Crescent'; detail='Energy rising. Plant above-ground crops. Begin new projects.'; }
    else if (pct < 0.28)            { phase=2; emoji='🌓'; name='First Quarter';   detail='Take action. Good for harvesting healing herbs.'; }
    else if (pct < 0.47)            { phase=3; emoji='🌔'; name='Waxing Gibbous';  detail='Crops grow fast. Good fishing — fish rise at night.'; }
    else if (pct < 0.53)            { phase=4; emoji='🌕'; name='Full Moon';        detail='Peak energy. Full tides. Best fishing. Healing ceremonies.'; }
    else if (pct < 0.72)            { phase=5; emoji='🌖'; name='Waning Gibbous';  detail='Harvest and store. Good for drying fish and medicines.'; }
    else if (pct < 0.78)            { phase=6; emoji='🌗'; name='Last Quarter';    detail='Clear land. Cut timber. Cleansing rituals.'; }
    else                            { phase=7; emoji='🌘'; name='Waning Crescent'; detail='Rest and plan. Plant root crops. Prepare for new cycle.'; }
    var illumination = Math.round(Math.abs(Math.cos(2*Math.PI*pct))*100);
    return { phase, emoji, name, detail, pct: Math.round(pct*100), illumination, daysSinceNew: Math.round(days) };
  }

  function getNextMoonEvent(targetPhase) {
    var d = new Date();
    for (var i = 1; i <= 32; i++) {
      d.setDate(d.getDate()+1);
      if (getMoonPhase(d).phase === targetPhase) return d.toLocaleDateString('en-GB',{day:'numeric',month:'short'});
    }
    return 'Soon';
  }

  /* ── Tides by region ────────────────────────────────────────── */
  function getTideInfo(region) {
    var d = new Date();
    var hour = d.getUTCHours();
    var moon = getMoonPhase(d);
    var springTide = moon.phase === 4 || moon.phase === 0;
    var neapTide   = moon.phase === 2 || moon.phase === 6;

    // Indian Ocean: semidiurnal but stronger monsoon influence
    // East Africa Rift: mostly lake tides + river levels (no ocean)
    // West Africa: Gulf of Guinea semidiurnal
    // Sahel: inland — river levels only, no tides

    if (region === 'sahel') {
      return {
        emoji: '🏞',
        current: 'Inland — no ocean tides',
        type: springTide ? 'River in flood season' : (neapTide ? 'River at moderate level' : 'River level normal'),
        nextHigh: 'N/A',
        fishing: 'Fish in rivers and lakes. No tidal influence. Focus on seasonal river levels.',
        advisory: 'No coastal tides. Monitor seasonal river flooding — Niger, Senegal, and Volta rivers have seasonal pulses.',
      };
    }

    if (region === 'east-africa') {
      return {
        emoji: '🏔',
        current: 'Highland lakes — no ocean tides',
        type: springTide ? 'Lake levels rising (rainy season effect)' : 'Lake levels stable',
        nextHigh: 'N/A',
        fishing: 'Lake Victoria, Turkana, Naivasha: no tides. Fish in early morning. Best on calm days.',
        advisory: 'No ocean tides in Rift Valley. Lake levels influenced by seasonal rains, not tides.',
      };
    }

    // Ocean tides (West Africa + Indian Ocean)
    var tideAngle = (hour / 24) * 2 * Math.PI * 2;
    var lunarMod = 1 + (springTide ? 0.35 : 0);
    var tideHeight = Math.sin(tideAngle) * lunarMod;
    var isHigh = tideHeight > 0.3, isLow = tideHeight < -0.3;
    var nextHighIn = '';
    for (var h = 1; h <= 12; h++) {
      if (Math.sin(((hour+h)/24)*2*Math.PI*2) > 0.3) { nextHighIn = h+'h'; break; }
    }

    if (region === 'indian-ocean') {
      var month = d.getMonth() + 1;
      var kaskazi = (month >= 11 || month <= 3); // NE monsoon Nov-Mar
      var kusi    = (month >= 6 && month <= 9);  // SE monsoon Jun-Sep
      return {
        emoji: isHigh ? '🌊' : (isLow ? '🏖' : '〰'),
        current: isHigh ? 'High tide' : (isLow ? 'Low tide' : 'Mid tide'),
        type: springTide ? 'Spring tide — large range' : (neapTide ? 'Neap tide — small range' : 'Normal tide'),
        monsoon: kaskazi ? 'Kaskazi (NE monsoon) — dhow season, calm Indian Ocean' : (kusi ? 'Kusi (SE monsoon) — cool, excellent fishing' : 'Inter-monsoon transitional period'),
        nextHigh: nextHighIn || 'Now',
        fishing: isHigh ? 'Fish move inshore and onto reef. Best for reef fishing and net casting.' : 'Fish move to deeper water. Dhow fishing more productive.',
        advisory: springTide ? 'Spring tides — highest highs, lowest lows. Strong currents on reef. Take care.' : 'Normal Indian Ocean tidal range. Good conditions for coastal fishing.',
      };
    }

    // West Africa default
    return {
      emoji: isHigh ? '🌊' : (isLow ? '🏖' : '〰'),
      current: isHigh ? 'High tide' : (isLow ? 'Low tide' : 'Mid tide'),
      type: springTide ? 'Spring tide — large range' : (neapTide ? 'Neap tide — small range' : 'Normal tide'),
      nextHigh: nextHighIn || 'Now',
      fishing: isHigh ? 'Fish move inshore. Cast nets near coast and rivermouth.' : 'Fish move offshore. Best for deep-water fishing and traps.',
      advisory: springTide ? 'Spring tide — highest high, lowest low. Excellent fishing. Strong currents.' : 'Normal tidal range. Standard fishing conditions.',
    };
  }

  /* ── Wind data by region + month ───────────────────────────── */
  var WINDS = {
    'west-africa': [
      {month:1,  name:'NE Harmattan',       direction:'Northeast',    strength:'Moderate–Strong', icon:'🌬', dust:'High',      advisory:'Dry dusty winds from Sahara. Protect lungs. Good for drying fish and crops.'},
      {month:2,  name:'Harmattan easing',   direction:'Variable NE–SW',strength:'Light–Moderate', icon:'💨', dust:'Moderate',  advisory:'Harmattan easing. Humidity rising. First sea breeze returns.'},
      {month:3,  name:'SW Monsoon begins',  direction:'Southwest',    strength:'Light',           icon:'🌀', dust:'Low',       advisory:'Warm moist Atlantic air arrives. First clouds and showers.'},
      {month:4,  name:'SW Monsoon',         direction:'Southwest',    strength:'Moderate',        icon:'🌀', dust:'Very Low',  advisory:'SW monsoon brings rains. Rough coastal waters. Avoid open sea.'},
      {month:5,  name:'Peak SW Monsoon',    direction:'Southwest',    strength:'Strong',          icon:'⛈', dust:'None',      advisory:'Strongest winds of rainy season. Rough seas. Secure boats.'},
      {month:6,  name:'SW Monsoon',         direction:'Southwest',    strength:'Moderate–Strong', icon:'🌧', dust:'None',      advisory:'Continued SW monsoon. Occasional squalls. Watch for storms.'},
      {month:7,  name:'August Break',       direction:'Variable',     strength:'Light',           icon:'🌥', dust:'Low',       advisory:'Brief lull in winds. Calm period. Good for coastal travel and fishing.'},
      {month:8,  name:'Returning SW',       direction:'Southwest',    strength:'Moderate',        icon:'🌦', dust:'Low',       advisory:'SW monsoon returns briefly. Second rains. Harvest conditions.'},
      {month:9,  name:'Transitional',       direction:'Variable SW–NE',strength:'Light',          icon:'🍃', dust:'Low',       advisory:'Wind shifting NE. Dry season approaching. Good travel weather.'},
      {month:10, name:'NE Trade Wind',      direction:'Northeast',    strength:'Light–Moderate',  icon:'💨', dust:'Low–Moderate',advisory:'NE trade winds strengthen. Good for drying crops and fish.'},
      {month:11, name:'Harmattan',          direction:'Northeast',    strength:'Moderate–Strong', icon:'🌬', dust:'High',      advisory:'Harmattan arrives from Sahara. Dry and dusty. Protect health.'},
      {month:12, name:'Peak Harmattan',     direction:'Northeast',    strength:'Strong',          icon:'🌬', dust:'Very High', advisory:'Peak harmattan. Very cold nights. Strong dusty winds.'},
      {month:13, name:'Harmattan easing',   direction:'NE variable',  strength:'Light–Moderate',  icon:'💨', dust:'Moderate',  advisory:'Harmattan easing. Temperatures rising toward new year.'},
    ],
    'indian-ocean': [
      {month:1,  name:'Kaskazi (NE Monsoon)',direction:'Northeast',   strength:'Moderate–Strong', icon:'⛵', dust:'Low',       advisory:'NE monsoon (Kaskazi). Reliable dhow sailing winds. Warm and humid. Excellent fishing season.'},
      {month:2,  name:'Peak Kaskazi',        direction:'Northeast',   strength:'Strong',          icon:'💨', dust:'Low',       advisory:'Peak NE monsoon. Best traditional dhow sailing season. Tuna and sailfish peak.'},
      {month:3,  name:'Kaskazi ending',      direction:'NE to SW shift',strength:'Variable',      icon:'🌦', dust:'Low',       advisory:'NE monsoon ending. Masika rains approaching. Seas becoming rougher.'},
      {month:4,  name:'Masika winds',        direction:'Southwest',   strength:'Light–Moderate',  icon:'🌧', dust:'None',      advisory:'Long rains season. Seas rough. Experienced fishers only. Dhows stay in harbour.'},
      {month:5,  name:'Pre-Kusi',            direction:'Transitional',strength:'Light',           icon:'🌤', dust:'Low',       advisory:'Masika rains ending. Seas calming. Octopus and squid season beginning.'},
      {month:6,  name:'Kusi (SE Monsoon)',   direction:'Southeast',   strength:'Moderate',        icon:'⛵', dust:'Low',       advisory:'SE monsoon (Kusi) begins. Cool pleasant winds. Excellent sailing and kingfish season.'},
      {month:7,  name:'Peak Kusi',           direction:'Southeast',   strength:'Strong',          icon:'💨', dust:'Low',       advisory:'Peak SE monsoon. Best visibility. Excellent deep-sea fishing. Marlin and sailfish.'},
      {month:8,  name:'Kusi',                direction:'Southeast',   strength:'Moderate–Strong', icon:'💨', dust:'Low',       advisory:'Kusi continues. Cool and clear. Best weather of Indian Ocean year.'},
      {month:9,  name:'Kusi easing',         direction:'SE to NE',    strength:'Light–Moderate',  icon:'🌤', dust:'Low',       advisory:'SE monsoon easing. Lobster season begins. Pre-Vuli transitional period.'},
      {month:10, name:'Pre-Kaskazi',         direction:'Variable NE', strength:'Light',           icon:'🌦', dust:'Low',       advisory:'Vuli rains beginning. NE monsoon building. Reef fishing excellent before rains.'},
      {month:11, name:'Vuli / NE building',  direction:'Northeast',   strength:'Light–Moderate',  icon:'🌧', dust:'Low',       advisory:'Short Vuli rains. NE monsoon building. Rough at times. Mangrove and reef fishing.'},
      {month:12, name:'Kaskazi arriving',    direction:'Northeast',   strength:'Moderate',        icon:'🌬', dust:'Low',       advisory:'Kaskazi NE monsoon arriving. Dhow season beginning. Warming up after Vuli rains.'},
    ],
    'east-africa': [
      {month:1,  name:'SE Trade Wind',       direction:'Southeast',   strength:'Moderate',        icon:'💨', dust:'Low',       advisory:'Short dry season. SE trade winds. Pleasant conditions. Good travel weather.'},
      {month:2,  name:'SE Trade Wind',       direction:'Southeast',   strength:'Moderate–Strong', icon:'💨', dust:'Low',       advisory:'Driest period. SE winds strong. Cool highland nights. Long distances to water.'},
      {month:3,  name:'NE Monsoon (Long Rains)',direction:'Northeast',strength:'Light',           icon:'🌦', dust:'Low',       advisory:'Long rains beginning. NE monsoon brings cool moist air from Indian Ocean.'},
      {month:4,  name:'Peak Long Rains',     direction:'Northeast',   strength:'Moderate',        icon:'⛈', dust:'None',      advisory:'Peak long rains. Heavy rainfall. Rift Valley cold and wet. Roads muddy.'},
      {month:5,  name:'Long Rains easing',   direction:'Transitional',strength:'Light',           icon:'🌧', dust:'Low',       advisory:'Long rains tapering. Cool pleasant weather returning. Good travel resuming.'},
      {month:6,  name:'SE Trade Wind',       direction:'Southeast',   strength:'Moderate',        icon:'🌤', dust:'Low',       advisory:'Cool dry season. SE trade winds from Indian Ocean. Clear skies. Good travel.'},
      {month:7,  name:'Cold SE Winds',       direction:'Southeast',   strength:'Strong',          icon:'❄️', dust:'Low',       advisory:'Coldest period. Strong cold SE winds on highlands. Frost possible. Protect livestock.'},
      {month:8,  name:'SE Trade Wind',       direction:'Southeast',   strength:'Moderate',        icon:'☀️', dust:'Low',       advisory:'Dry season warming. Good SE winds. Fire risk increasing on dry grassland.'},
      {month:9,  name:'Transitional NE',     direction:'NE shifting', strength:'Light',           icon:'🌦', dust:'Low',       advisory:'Short rains approaching. Winds shifting NE. Humidity increasing.'},
      {month:10, name:'Short Rains (NE)',    direction:'Northeast',   strength:'Light–Moderate',  icon:'🌧', dust:'None',      advisory:'Short rains (Vuli equivalent). NE monsoon brings moisture from Indian Ocean.'},
      {month:11, name:'Peak Short Rains',    direction:'Northeast',   strength:'Moderate',        icon:'⛈', dust:'None',      advisory:'Peak short rains. Good grass growth for cattle. Roads muddy in valleys.'},
      {month:12, name:'SE Trade returning',  direction:'SE shifting', strength:'Light',           icon:'🌦', dust:'Low',       advisory:'Short rains ending. SE trade winds returning. Year-end travel improving.'},
    ],
    'sahel': [
      {month:1,  name:'Peak Harmattan',      direction:'Northeast',   strength:'Strong',          icon:'🌬', dust:'Very High', advisory:'Peak harmattan from Sahara. Very cold nights, hot days. Extreme dust. Protect health.'},
      {month:2,  name:'Harmattan',           direction:'Northeast',   strength:'Moderate–Strong', icon:'🌬', dust:'High',      advisory:'Harmattan continues. Still very dry. Cold nights easing slightly.'},
      {month:3,  name:'Hot dry winds',       direction:'NE to SW',    strength:'Light',           icon:'🔥', dust:'Moderate',  advisory:'Hottest month. Pre-rain period. First southwesterly sea breeze approaching.'},
      {month:4,  name:'Pre-rain squalls',    direction:'SW building', strength:'Variable',        icon:'⛈', dust:'Low',       advisory:'First violent thunderstorms. Haboob dust storms possible. Temperature relief coming.'},
      {month:5,  name:'SW Monsoon begins',   direction:'Southwest',   strength:'Light–Moderate',  icon:'🌧', dust:'Low',       advisory:'Monsoon begins in south. North still dry. First reliable rains.'},
      {month:6,  name:'SW Monsoon',          direction:'Southwest',   strength:'Moderate',        icon:'🌧', dust:'None',      advisory:'Rains established. Good farming winds. Locust watch period.'},
      {month:7,  name:'Peak SW Monsoon',     direction:'Southwest',   strength:'Moderate–Strong', icon:'⛈', dust:'None',      advisory:'Peak rains. Strong SW monsoon. Flash floods in wadis. Good crop growth.'},
      {month:8,  name:'SW Monsoon easing',   direction:'Southwest',   strength:'Moderate',        icon:'🌦', dust:'Low',       advisory:'Rains tapering. Harvest approaching. Good conditions for open-air work.'},
      {month:9,  name:'Transitional',        direction:'SW to NE',    strength:'Light',           icon:'🌤', dust:'Low',       advisory:'Dry season returning. Good travel. Long-distance trading resumes.'},
      {month:10, name:'NE Trade Wind',       direction:'Northeast',   strength:'Light–Moderate',  icon:'💨', dust:'Low–Moderate',advisory:'Dry season winds. Good drying weather for crops and fish.'},
      {month:11, name:'Early Harmattan',     direction:'Northeast',   strength:'Moderate',        icon:'💨', dust:'Moderate–High',advisory:'Harmattan building. Increasing dust. Cold nights beginning.'},
      {month:12, name:'Peak Harmattan',      direction:'Northeast',   strength:'Strong',          icon:'🌬', dust:'Very High', advisory:'Full harmattan. Extreme dryness. Very cold nights. Protect livestock and elderly.'},
    ],
  };

  /* ── Rainfall data by region + month ───────────────────────── */
  var RAINFALL = {
    'west-africa': [
      {month:1,  pattern:'Deep dry',      mm:'0–10',    days:0,  icon:'☀️', early_sign:'None',                      advisory:'Deep dry season. No rain expected. Rely on stored water.'},
      {month:2,  pattern:'Late dry',      mm:'10–40',   days:2,  icon:'🌤', early_sign:'Isolated thunderstorms',     advisory:'Occasional showers possible. First rains approaching.'},
      {month:3,  pattern:'Early rains',   mm:'60–120',  days:8,  icon:'🌦', early_sign:'Afternoon thunderstorms',    advisory:'First rains arrive but unreliable. Plant drought-tolerant crops first.'},
      {month:4,  pattern:'Rainy season',  mm:'120–200', days:14, icon:'🌧', early_sign:'Morning mist, afternoon rain',advisory:'Reliable rains. Good planting window. Flash floods possible.'},
      {month:5,  pattern:'Peak rains',    mm:'200–300', days:18, icon:'⛈', early_sign:'Daily heavy storms',         advisory:'Heaviest rains. Flooding risk high. Excellent for crops.'},
      {month:6,  pattern:'Peak rains',    mm:'180–280', days:16, icon:'⛈', early_sign:'Continuous cloud cover',     advisory:'Continued heavy rains. Waterlogging risk. Hill farms safer.'},
      {month:7,  pattern:'August break',  mm:'60–100',  days:8,  icon:'🌥', early_sign:'Clear spells',               advisory:'Brief dry spell. Good harvesting window. Do not mistake for end of rains.'},
      {month:8,  pattern:'Second rains',  mm:'100–180', days:12, icon:'🌦', early_sign:'Afternoon storms return',    advisory:'Second rainy season. Moderate rainfall. Good for late-season crops.'},
      {month:9,  pattern:'Late rains',    mm:'60–120',  days:8,  icon:'🌦', early_sign:'Rains becoming irregular',   advisory:'Rains tapering. Last chance to plant short-season crops.'},
      {month:10, pattern:'Dry onset',     mm:'10–40',   days:3,  icon:'🌤', early_sign:'Isolated showers only',      advisory:'Dry season returning. Good harvesting and drying weather.'},
      {month:11, pattern:'Dry',           mm:'0–10',    days:1,  icon:'☀️', early_sign:'None',                      advisory:'Dry season. No significant rain. Rivers falling.'},
      {month:12, pattern:'Deep dry',      mm:'0–5',     days:0,  icon:'☀️', early_sign:'None',                      advisory:'Driest month. Harmattan dominates. Protect water sources.'},
      {month:13, pattern:'Dry',           mm:'0–10',    days:0,  icon:'☀️', early_sign:'None',                      advisory:'Still dry. Pre-new-year. Begin land preparation.'},
    ],
    'indian-ocean': [
      {month:1,  pattern:'Kaskazi dry',   mm:'20–40',   days:3,  icon:'⛵', early_sign:'Light NE sea breeze',        advisory:'NE monsoon season. Light showers possible. Good sailing and fishing.'},
      {month:2,  pattern:'Kaskazi peak',  mm:'10–30',   days:2,  icon:'💨', early_sign:'Strong NE winds',            advisory:'Driest Kaskazi month. Best ocean fishing. Dhow trade at peak.'},
      {month:3,  pattern:'Masika begins', mm:'60–120',  days:8,  icon:'🌦', early_sign:'Increasing clouds',          advisory:'Long rains (Masika) arriving. Seas roughening. Prepare farms.'},
      {month:4,  pattern:'Peak Masika',   mm:'160–240', days:16, icon:'⛈', early_sign:'Daily heavy rain',            advisory:'Heaviest rains of year. Flooding possible. Stay near home.'},
      {month:5,  pattern:'Late Masika',   mm:'100–160', days:12, icon:'🌧', early_sign:'Rains tapering',              advisory:'Masika rains tapering. Weather improving. Harvest approaching.'},
      {month:6,  pattern:'Kusi dry',      mm:'20–40',   days:3,  icon:'💨', early_sign:'SE trade winds',             advisory:'SE monsoon (Kusi) begins. Cool and dry. Best weather of the year.'},
      {month:7,  pattern:'Peak Kusi dry', mm:'10–20',   days:1,  icon:'🌤', early_sign:'Strong SE winds',            advisory:'Driest month. Cool and clear. Excellent fishing and sailing.'},
      {month:8,  pattern:'Kusi dry',      mm:'10–20',   days:1,  icon:'🌤', early_sign:'SE winds',                   advisory:'Kusi continues. Good weather. Best visibility for reef fishing.'},
      {month:9,  pattern:'Kusi easing',   mm:'20–40',   days:3,  icon:'🌦', early_sign:'Winds shifting',             advisory:'Kusi easing. Pre-Vuli period. Lobster season. Good conditions.'},
      {month:10, pattern:'Vuli begins',   mm:'60–100',  days:8,  icon:'🌧', early_sign:'NE clouds building',          advisory:'Short rains (Vuli) begin. Good for crops. Reef fishing before rains.'},
      {month:11, pattern:'Peak Vuli',     mm:'100–160', days:12, icon:'⛈', early_sign:'Daily afternoon rain',        advisory:'Peak Vuli rains. Flooding coastal areas. Rough seas at times.'},
      {month:12, pattern:'Vuli ending',   mm:'40–80',   days:6,  icon:'🌦', early_sign:'Rains tapering',              advisory:'Vuli ending. NE monsoon arriving. Dhow season beginning again.'},
    ],
    'east-africa': [
      {month:1,  pattern:'Short dry',     mm:'20–40',   days:3,  icon:'☀️', early_sign:'Light showers possible',     advisory:'Short dry season. Some showers. Good travel and trading weather.'},
      {month:2,  pattern:'Dry peak',      mm:'10–20',   days:1,  icon:'🌤', early_sign:'None',                       advisory:'Driest period. Water stress on cattle. Long distances to water.'},
      {month:3,  pattern:'Long rains begin',mm:'60–100',days:8,  icon:'🌦', early_sign:'Afternoon thunderstorms',    advisory:'Long rains (Masika equivalent) arriving. Cool relief. Excellent for grass.'},
      {month:4,  pattern:'Peak long rains',mm:'160–220',days:16, icon:'⛈', early_sign:'Daily heavy storms',          advisory:'Heaviest rains. Flooding in valleys. Rift Valley cold and wet.'},
      {month:5,  pattern:'Long rains end', mm:'100–160',days:12, icon:'🌧', early_sign:'Rains tapering',              advisory:'Rains tapering. Cool pleasant weather. Good travel resuming.'},
      {month:6,  pattern:'Cool dry',      mm:'20–40',   days:3,  icon:'🌤', early_sign:'SE trade winds',             advisory:'Cool dry season. Clear skies. Good travel and long-distance trade.'},
      {month:7,  pattern:'Cold dry',      mm:'10–20',   days:1,  icon:'❄️', early_sign:'None',                       advisory:'Coldest month. Frost possible on highlands. Protect crops and livestock.'},
      {month:8,  pattern:'Dry season',    mm:'15–30',   days:2,  icon:'☀️', early_sign:'None',                       advisory:'Dry season. Warming up. Fire risk on dry grassland.'},
      {month:9,  pattern:'Pre-rains',     mm:'20–40',   days:3,  icon:'🌦', early_sign:'Increasing humidity',         advisory:'Short rains approaching. Humidity rising. Cattle restless.'},
      {month:10, pattern:'Short rains',   mm:'60–100',  days:8,  icon:'🌧', early_sign:'Afternoon thunderstorms',    advisory:'Short rains (October rains). Good grass growth. Cattle recover condition.'},
      {month:11, pattern:'Peak short rains',mm:'100–140',days:12,icon:'⛈', early_sign:'Daily rain',                  advisory:'Peak short rains. Excellent conditions. Roads muddy in valleys.'},
      {month:12, pattern:'Rains ending',  mm:'40–80',   days:6,  icon:'🌦', early_sign:'Rains tapering',              advisory:'Short rains ending. Warming up. Year-end harvest and celebrations.'},
    ],
    'sahel': [
      {month:1,  pattern:'Peak dry',      mm:'0mm',     days:0,  icon:'🌬', early_sign:'None',                       advisory:'Peak harmattan. No rain. Very cold nights. Protect livestock.'},
      {month:2,  pattern:'Dry',           mm:'0–5mm',   days:0,  icon:'🌤', early_sign:'None',                       advisory:'Still dry and dusty. Harmattan easing. Pre-heat season.'},
      {month:3,  pattern:'Hot dry',       mm:'5–20mm',  days:1,  icon:'🔥', early_sign:'None',                       advisory:'Hottest month. Pre-rain period. Heat stress on humans and animals.'},
      {month:4,  pattern:'Pre-rains',     mm:'20–60mm', days:3,  icon:'🌦', early_sign:'Violent thunderstorms',      advisory:'First rains arrive. Violent storms. Temperature relief beginning.'},
      {month:5,  pattern:'Early rains',   mm:'60–100mm',days:6,  icon:'🌧', early_sign:'Regular afternoon rains',    advisory:'Rains established in south. North getting first rains. Good planting.'},
      {month:6,  pattern:'Rainy season',  mm:'100–160mm',days:10,icon:'🌧', early_sign:'Daily rains',                advisory:'Good rains. Farming in full swing. Flash floods in wadis.'},
      {month:7,  pattern:'Peak rains',    mm:'160–220mm',days:14,icon:'⛈', early_sign:'Daily heavy storms',          advisory:'Peak rainfall. Flooding risk. Rivers high. Excellent crop growth.'},
      {month:8,  pattern:'Rains tapering',mm:'100–160mm',days:10,icon:'🌦', early_sign:'Rains easing',               advisory:'Rains easing. Harvest approaching. Good weather for open-air work.'},
      {month:9,  pattern:'Late rains',    mm:'40–80mm', days:5,  icon:'🌤', early_sign:'Rains becoming irregular',   advisory:'Dry season returning. Good travel. Long-distance trading resumes.'},
      {month:10, pattern:'Dry onset',     mm:'10–30mm', days:2,  icon:'☀️', early_sign:'Isolated showers',           advisory:'Dry season established. Good travel and trade weather.'},
      {month:11, pattern:'Early harmattan',mm:'0–5mm',  days:0,  icon:'💨', early_sign:'Dusty winds beginning',      advisory:'Harmattan building. Increasing dust and dryness.'},
      {month:12, pattern:'Peak harmattan',mm:'0mm',     days:0,  icon:'🌬', early_sign:'None',                       advisory:'Peak harmattan. Extreme dryness. Very cold nights. Protect all living things.'},
    ],
  };

  /* ── Render functions ───────────────────────────────────────── */
  function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  function field(label, val) {
    return '<div><span class="field__label">'+esc(label)+'</span><span class="field__val">'+esc(String(val))+'</span></div>';
  }

  function renderMoon() {
    var moon = getMoonPhase();
    var nextFull = getNextMoonEvent(4);
    var nextNew  = getNextMoonEvent(0);
    return '<div class="mod" id="awag-moon-mod">'
      +'<div class="mod__head" style="border-left:3px solid #c8b8ff;color:#c8b8ff;">'
      +'<span class="mod__icon">'+moon.emoji+'</span><span class="mod__title">Moon Phase</span></div>'
      +'<div class="mod__body">'
      +field('Today', moon.emoji+' '+moon.name)
      +field('Illumination', moon.illumination+'%')
      +field('Days since new moon', moon.daysSinceNew+' days')
      +field('Next full moon', nextFull)
      +field('Next new moon', nextNew)
      +field('Traditional guide', moon.detail)
      +'</div></div>';
  }

  function renderTides(region) {
    var tide = getTideInfo(region);
    var fields = '<div class="mod__body">'
      +field('Current', tide.current)
      +field('Tide type', tide.type);
    if (tide.monsoon)   fields += field('Monsoon season', tide.monsoon);
    if (tide.nextHigh && tide.nextHigh !== 'N/A') fields += field('Next high tide', tide.nextHigh);
    fields += field('Fishing guide', tide.fishing)
      +field('Advisory', tide.advisory)
      +'</div>';
    return '<div class="mod" id="awag-tide-mod">'
      +'<div class="mod__head" style="border-left:3px solid #40c0ff;color:#40c0ff;">'
      +'<span class="mod__icon">'+tide.emoji+'</span><span class="mod__title">Tides</span></div>'
      +fields+'</div>';
  }

  function renderWinds(region, monthNo) {
    var data = WINDS[region] || WINDS['west-africa'];
    var idx = Math.max(0, Math.min(data.length-1, monthNo-1));
    var w = data[idx];
    if (!w) return '';
    return '<div class="mod" id="awag-wind-mod">'
      +'<div class="mod__head" style="border-left:3px solid #80e0ff;color:#80e0ff;">'
      +'<span class="mod__icon">'+w.icon+'</span><span class="mod__title">Winds</span></div>'
      +'<div class="mod__body">'
      +field('Wind', w.name)
      +field('Direction', w.direction)
      +field('Strength', w.strength)
      +field('Dust / Humidity', w.dust)
      +field('Advisory', w.advisory)
      +'</div></div>';
  }

  function renderRainfall(region, monthNo) {
    var data = RAINFALL[region] || RAINFALL['west-africa'];
    var idx = Math.max(0, Math.min(data.length-1, monthNo-1));
    var r = data[idx];
    if (!r) return '';
    return '<div class="mod" id="awag-rain-mod">'
      +'<div class="mod__head" style="border-left:3px solid #60d8a0;color:#60d8a0;">'
      +'<span class="mod__icon">'+r.icon+'</span><span class="mod__title">Rainfall</span></div>'
      +'<div class="mod__body">'
      +field('Pattern', r.pattern)
      +field('Expected', r.mm+' mm')
      +field('Rain days', '~'+r.days+' days/month')
      +field('Early signs', r.early_sign)
      +field('Advisory', r.advisory)
      +'</div></div>';
  }

  /* ── Init ────────────────────────────────────────────────────── */
  function init() {
    var mount = document.getElementById('awag-extended-mount');
    if (!mount) return;
    var region = getRegion();
    var monthEl = document.querySelector('[data-awag-month]');
    var monthNo = monthEl ? parseInt(monthEl.dataset.awagMonth, 10) : 1;
    if (!monthNo || monthNo < 1) monthNo = 1;
    mount.innerHTML = renderMoon() + renderTides(region) + renderWinds(region, monthNo) + renderRainfall(region, monthNo);
    setInterval(function() {
      var mm = document.getElementById('awag-moon-mod');
      var tm = document.getElementById('awag-tide-mod');
      if (mm) mm.outerHTML = renderMoon();
      if (tm) tm.outerHTML = renderTides(region);
    }, 60000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
/* awag-modules.js — AWAG Activity Modules for Igbo Skin
 * Farming, Fishing, Trading, Herding, Healing, Weather
 * All data hardcoded for offline-first use.
 * Integrates with igbo-calendar via igbo month number (1-13).
 */
(function () {
  'use strict';

  /* ── Igbo seasonal data by month (1-13) ────────────────────── */
  var FARMING = [
    { month: 1,  name: 'Ọnwa Mbụ',       activity: 'Land preparation begins. Clear bush, burn old stalks. Plant early yam seedlings in mounds.', crops: ['Yam', 'Cocoyam'], icon: '🌱' },
    { month: 2,  name: 'Ọnwa Abụọ',      activity: 'Main yam planting season. Mound-making continues. First rains expected. Plant maize.', crops: ['Yam', 'Maize'], icon: '🌽' },
    { month: 3,  name: 'Ọnwa Ife Eke',   activity: 'Weeding first round. Plant cassava cuttings. Tend yam mounds. Intercrop vegetables.', crops: ['Cassava', 'Vegetables'], icon: '🌿' },
    { month: 4,  name: 'Ọnwa Anọ',       activity: 'Heavy rains. Second weeding. Monitor yam staking. Plant groundnuts and beans.', crops: ['Groundnut', 'Beans'], icon: '🫘' },
    { month: 5,  name: 'Ọnwa Agwụ',      activity: 'Harvest early maize. Continue yam tending. Plant second-season vegetables.', crops: ['Maize (harvest)', 'Pepper'], icon: '🌶' },
    { month: 6,  name: 'Ọnwa Ifejiọkụ',  activity: 'New Yam Festival preparation. Spiritual protection of farms. First yam harvest begins.', crops: ['Yam (first harvest)'], icon: '🎋' },
    { month: 7,  name: 'Ọnwa Asaa',      activity: 'Main yam harvest. Ikeji festival. Store yams in barns. Harvest cocoyam.', crops: ['Yam (main harvest)', 'Cocoyam'], icon: '🏺' },
    { month: 8,  name: 'Ọnwa Asatọ',     activity: 'Harvest cassava and groundnuts. Dry season begins in north. Plant dry-season crops.', crops: ['Cassava (harvest)', 'Groundnut'], icon: '🌾' },
    { month: 9,  name: 'Ọnwa Itewe',     activity: 'Clear land for next season. Harvest remaining crops. Plant palm seedlings.', crops: ['Palm', 'Vegetables'], icon: '🌴' },
    { month: 10, name: 'Ọnwa Anọ Ụfọ',  activity: 'Dry season farming. Irrigate where possible. Harvest dry-season beans and cowpeas.', crops: ['Cowpea', 'Sorghum'], icon: '☀️' },
    { month: 11, name: 'Ọnwa Ajana',     activity: 'Harmattan. Rest period. Repair tools. Prepare new farmland. Plant early-season crops.', crops: ['Tool repair', 'Land clearing'], icon: '🔧' },
    { month: 12, name: 'Ọnwa Ụzo Alụsị', activity: 'Ritual farm blessings. Plant early yam seedlings. Prepare mounds for new year.', crops: ['Yam seedlings'], icon: '🙏' },
    { month: 13, name: 'Ọnwa Ọ Njị',    activity: 'Intercalary month. Complete all preparations. Community farm planning meetings.', crops: ['Planning', 'Community work'], icon: '🤝' },
  ];

  var FISHING = [
    { month: 1,  activity: 'Rivers low and clear. Excellent fishing with traps and nets. Best for catfish and tilapia.', fish: ['Catfish', 'Tilapia'], conditions: 'Excellent', icon: '🎣' },
    { month: 2,  activity: 'Pre-rain season. Fish active and feeding heavily. Good for line fishing.', fish: ['Catfish', 'Mudfish'], conditions: 'Very Good', icon: '🎣' },
    { month: 3,  activity: 'First rains. Fish begin spawning. Reduce fishing near spawning grounds.', fish: ['Mixed species'], conditions: 'Good', icon: '🎣' },
    { month: 4,  activity: 'Flood season begins. Fish disperse into floodplains. Use cast nets widely.', fish: ['Tilapia', 'Carp'], conditions: 'Moderate', icon: '🌊' },
    { month: 5,  activity: 'Peak flood. Fish plentiful in floodplains. Excellent community fishing season.', fish: ['Tilapia', 'Catfish', 'Carp'], conditions: 'Excellent', icon: '🎣' },
    { month: 6,  activity: 'Flood receding. Fish concentrate in channels. Use weir traps and basket traps.', fish: ['Catfish', 'Mudfish'], conditions: 'Very Good', icon: '🎣' },
    { month: 7,  activity: 'Post-flood. Fish trapped in pools. Community pond fishing. Dry smoked fish.', fish: ['Mixed species'], conditions: 'Good', icon: '🏺' },
    { month: 8,  activity: 'Rivers falling. Fish concentrated. Excellent line fishing. Smoke and store fish.', fish: ['Catfish', 'Tilapia'], conditions: 'Very Good', icon: '🎣' },
    { month: 9,  activity: 'Rivers low. Best trap fishing. Night fishing for catfish. Coastal: bonga season.', fish: ['Catfish', 'Bonga'], conditions: 'Excellent', icon: '🎣' },
    { month: 10, activity: 'Dry season. Rivers clear. Excellent visibility. Spear and line fishing.', fish: ['Tilapia', 'Catfish'], conditions: 'Very Good', icon: '🎣' },
    { month: 11, activity: 'Harmattan. Fish sluggish in cool water. Trap fishing more productive than line.', fish: ['Mudfish', 'Catfish'], conditions: 'Moderate', icon: '🎣' },
    { month: 12, activity: 'Rivers at lowest. Fish concentrated in deep pools. Communal fishing events.', fish: ['Mixed species'], conditions: 'Good', icon: '🤝' },
    { month: 13, activity: 'Intercalary. Prepare nets and traps for new season. Repair canoes.', fish: ['Preparation'], conditions: 'Preparation', icon: '🔧' },
  ];

  var TRADING = [
    { month: 1,  activity: 'New Year trading surge. Yam markets very active. Buy seed yams. Livestock trading peaks.', goods: ['Yam', 'Livestock', 'Palm oil'], icon: '🛒' },
    { month: 2,  activity: 'Farm input trading. Buy seedlings, tools, fertiliser. Cloth and crafts markets active.', goods: ['Farm inputs', 'Tools', 'Cloth'], icon: '⚒️' },
    { month: 3,  activity: 'Quiet trading period. Focus on farm work. Local markets steady.', goods: ['Food', 'Vegetables'], icon: '🥬' },
    { month: 4,  activity: 'Moderate trading. Rain disrupts travel. Local markets only. Dried fish trading.', goods: ['Dried fish', 'Palm oil'], icon: '🛖' },
    { month: 5,  activity: 'First maize harvest. Grain markets open. Vegetables abundant and cheap.', goods: ['Maize', 'Vegetables'], icon: '🌽' },
    { month: 6,  activity: 'New Yam Festival. Major trading season. Textiles, crafts, and livestock surge.', goods: ['Yam', 'Textiles', 'Craft'], icon: '🎊' },
    { month: 7,  activity: 'Peak trading season. Yam harvest surplus. Inter-community trading. Export surplus.', goods: ['Yam', 'Cassava', 'Palm oil'], icon: '📦' },
    { month: 8,  activity: 'Cassava and groundnut markets active. Livestock fattened for sale. School fees season.', goods: ['Cassava', 'Groundnut', 'Livestock'], icon: '🛒' },
    { month: 9,  activity: 'Long-distance trading resumes. Roads accessible. Bulk grain trading.', goods: ['Grains', 'Palm produce'], icon: '🚛' },
    { month: 10, activity: 'Dry season markets. Steady trading. Craft production peaks. Weaving season.', goods: ['Crafts', 'Textiles', 'Pottery'], icon: '🏺' },
    { month: 11, activity: 'Harmattan trading. Long-distance traders active. Cattle from north arrive.', goods: ['Cattle', 'Grains', 'Dried goods'], icon: '🐄' },
    { month: 12, activity: 'Year-end trading. Christmas/festival preparations. Major livestock markets.', goods: ['Livestock', 'Food', 'Gifts'], icon: '🎁' },
    { month: 13, activity: 'Intercalary. Settle debts. Plan next season trades. Community market reviews.', goods: ['Debt settlement', 'Planning'], icon: '📋' },
  ];

  var HERDING = [
    { month: 1,  activity: 'Dry season grazing. Move cattle to river valleys. Water sources reducing. Supplement feed.', conditions: 'Dry', icon: '🐄' },
    { month: 2,  activity: 'Pre-rain period. Cattle restless. Prepare for movement to wet season pastures.', conditions: 'Transitional', icon: '🐄' },
    { month: 3,  activity: 'First rains. New grass growth. Cattle thrive. Move to upland pastures.', conditions: 'Improving', icon: '🌱' },
    { month: 4,  activity: 'Peak rains. Excellent grazing. Watch for flooding of low pastures. Tick control.', conditions: 'Excellent', icon: '🌿' },
    { month: 5,  activity: 'Lush grazing. Cattle gain weight rapidly. Milk production peaks. Calving season.', conditions: 'Excellent', icon: '🥛' },
    { month: 6,  activity: 'Good grazing continues. Begin drying excess milk. Prepare dried meat stores.', conditions: 'Very Good', icon: '🐄' },
    { month: 7,  activity: 'Rains easing. Move cattle gradually to lower pastures. Harvest season — keep off farms.', conditions: 'Good', icon: '⚠️' },
    { month: 8,  activity: 'Dry season approaching. Begin southward migration for Fulani herders. Reduce herd size.', conditions: 'Transitional', icon: '🚶' },
    { month: 9,  activity: 'Harmattan approaching. Long migration routes. Water points critical. Salt licks needed.', conditions: 'Challenging', icon: '💧' },
    { month: 10, activity: 'Dry season. Cattle in riverside grazing areas. Supplement with crop residues.', conditions: 'Moderate', icon: '🌾' },
    { month: 11, activity: 'Peak harmattan. Cold nights stress cattle. House young animals. Supplement feeding.', conditions: 'Difficult', icon: '🥶' },
    { month: 12, activity: 'Year-end. Sell surplus cattle. Prepare for festival slaughter. Plan breeding.', conditions: 'Moderate', icon: '📊' },
    { month: 13, activity: 'Intercalary. Rest period. Veterinary checks. Prepare for wet season movement.', conditions: 'Rest', icon: '🏥' },
  ];

  var HEALING = [
    { month: 1,  activity: 'Harvest bark medicines while sap is low. Collect dry-season roots. Annual cleansing rituals.', herbs: ['Ogirisi', 'Uda', 'Nchuanwu'], rituals: 'New Year purification', icon: '🌿' },
    { month: 2,  activity: 'Prepare soil medicines before rains. Collect seeds for medicine gardens. Moon waxing — good for gathering.', herbs: ['Uziza', 'Aidan fruit'], rituals: 'Farm blessing preparation', icon: '🌙' },
    { month: 3,  activity: 'First rains bring new growth. Harvest young medicinal shoots. Treat seasonal fever.', herbs: ['Neem', 'Bitter leaf'], rituals: 'Rain welcome ceremony', icon: '🌧️' },
    { month: 4,  activity: 'Abundant herbs available. Collect and dry quickly before humidity. Treat malaria season.', herbs: ['Fever tree', 'Lemongrass'], rituals: 'Anti-malaria preparation', icon: '🌿' },
    { month: 5,  activity: 'Peak herb growth. Collect, dry, and store. Prepare fertility medicines for planting.', herbs: ['Mixed forest herbs'], rituals: 'Fertility blessing', icon: '✨' },
    { month: 6,  activity: 'New Yam Festival medicines. Spiritual protection herbs. Ifejiọkụ offerings.', herbs: ['Sacred herbs', 'Ofo tree'], rituals: 'Ifejiọkụ ceremony', icon: '🎋' },
    { month: 7,  activity: 'Ikeji festival healing. Community health rituals. Harvest anti-rheumatic plants.', herbs: ['Anti-rheumatic herbs'], rituals: 'Ikeji health ritual', icon: '🏺' },
    { month: 8,  activity: 'Post-harvest healing season. Treat farm injuries. Harvest roots before ground hardens.', herbs: ['Root medicines'], rituals: 'Harvest thanksgiving', icon: '🙏' },
    { month: 9,  activity: 'Dry season medicine storage. Protect dried herbs from harmattan dust. Prepare tinctures.', herbs: ['Stored medicines'], rituals: 'Ancestor remembrance', icon: '🏺' },
    { month: 10, activity: 'Collect bark before harmattan dries trees. Prepare cold-season remedies.', herbs: ['Bark medicines', 'Ginger'], rituals: 'Community health review', icon: '🌿' },
    { month: 11, activity: 'Harmattan remedies. Treat chest ailments, dry skin, cracked lips. Shea butter preparation.', herbs: ['Shea butter', 'Eucalyptus'], rituals: 'Cold season protection', icon: '🌬️' },
    { month: 12, activity: 'Year-end purification. Cleanse homestead. Prepare new year medicines. Masquerade herbs.', herbs: ['Purification herbs'], rituals: 'Year-end cleansing', icon: '🔥' },
    { month: 13, activity: 'Intercalary. Rest and reflect. Teach apprentices. Review medicine stores.', herbs: ['Teaching herbs'], rituals: 'Knowledge transfer', icon: '📚' },
  ];

  var WEATHER = [
    { month: 1,  icon: '🌤', season: 'Dry Season', rains: 'None', temp: 'Hot days, warm nights', wind: 'Harmattan easing', humidity: 'Low', advisory: 'Hot and dry. Protect from dust and dehydration. Harmattan winds from northeast.' },
    { month: 2,  icon: '🌤', season: 'Late Dry', rains: 'Occasional', temp: 'Very hot', wind: 'Light southwesterlies', humidity: 'Rising', advisory: 'Hottest period of year. First clouds appear. Pre-rain humidity rising.' },
    { month: 3,  icon: '🌧️', season: 'Early Rains', rains: 'Light to moderate', temp: 'Hot', wind: 'Southwest monsoon begins', humidity: 'Moderate', advisory: 'First rains arrive. Thunderstorms possible. Temperature relief beginning.' },
    { month: 4,  icon: '🌧️', season: 'Rainy Season', rains: 'Heavy', temp: 'Warm', wind: 'Southwest monsoon', humidity: 'High', advisory: 'Heavy rainfall. Flooding risk in low areas. Rivers rising. Lightning storms.' },
    { month: 5,  icon: '⛈️', season: 'Peak Rains', rains: 'Very heavy', temp: 'Warm and humid', wind: 'Southwest monsoon strong', humidity: 'Very High', advisory: 'Peak rainfall season. Avoid river crossings. Monitor flood levels. Mudslide risk.' },
    { month: 6,  icon: '⛈️', season: 'Peak Rains', rains: 'Heavy', temp: 'Warm', wind: 'Southwest monsoon', humidity: 'Very High', advisory: 'Continued heavy rains. Road conditions poor. River fishing excellent but dangerous.' },
    { month: 7,  icon: '🌥️', season: 'August Break', rains: 'Reduced', temp: 'Warm', wind: 'Mixed', humidity: 'Moderate-High', advisory: 'Brief August break. Less rain. Good for travel and trade. Temporary dry spell.' },
    { month: 8,  icon: '🌦️', season: 'Second Rains', rains: 'Moderate', temp: 'Warm', wind: 'Southwest monsoon returning', humidity: 'High', advisory: 'Second rainy season. Moderate rainfall. Harvest season begins.' },
    { month: 9,  icon: '🌤️', season: 'Late Rains', rains: 'Light', temp: 'Warm, nights cooling', wind: 'Transitional', humidity: 'Moderate', advisory: 'Rains tapering. Good travel weather. Harvest and storage season.' },
    { month: 10, icon: '☀️', season: 'Dry Season', rains: 'Rare', temp: 'Warm days, cool nights', wind: 'Northeast begins', humidity: 'Low-Moderate', advisory: 'Dry season returning. Pleasant temperatures. Good for travel and long-distance trade.' },
    { month: 11, icon: '🌬️', season: 'Harmattan', rains: 'None', temp: 'Warm days, cold nights', wind: 'Harmattan (NE) strong', humidity: 'Very Low', advisory: 'Harmattan arrives. Dusty, dry winds. Protect respiratory health. Bushfire risk.' },
    { month: 12, icon: '🌬️', season: 'Peak Harmattan', rains: 'None', temp: 'Cool to cold nights', wind: 'Harmattan strong', humidity: 'Very Low', advisory: 'Peak harmattan. Very dry and dusty. Cold harmattan nights. Protect livestock and elderly.' },
    { month: 13, icon: '🌤️', season: 'Late Harmattan', rains: 'None', temp: 'Hot days returning', wind: 'Harmattan easing', humidity: 'Low', advisory: 'Harmattan easing. Temperatures rising. Transitional period before new year rains.' },
  ];

  /* ── Helper ─────────────────────────────────────────────────── */
  function getMonth() { return 1; // TEMP: hardcoded until calendar integration fixed
    var app = document.getElementById('igcal-app');
    if (!app) return 1;
    var m = parseInt(app.dataset.igMonth || app.getAttribute('data-ig-month') || '1', 10);
    return (m >= 1 && m <= 13) ? m : 1;
  }

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  /* ── Render ─────────────────────────────────────────────────── */
  function renderModule(title, icon, data, monthIdx, fields) {
    var d = data[monthIdx];
    if (!d) return '';
    var html = '<div class="awag-module">';
    html += '<div class="awag-module__head"><span class="awag-module__icon">' + d.icon + '</span>';
    html += '<h3 class="awag-module__title">' + esc(title) + '</h3></div>';
    html += '<div class="awag-module__body">';
    fields.forEach(function(f) {
      if (d[f.key]) {
        var val = Array.isArray(d[f.key]) ? d[f.key].join(', ') : d[f.key];
        html += '<div class="awag-field"><span class="awag-field__label">' + esc(f.label) + '</span>';
        html += '<span class="awag-field__val">' + esc(val) + '</span></div>';
      }
    });
    html += '</div></div>';
    return html;
  }

  function render(monthNo) {
    var idx = Math.max(0, Math.min(12, monthNo - 1));
    var container = document.getElementById('awag-modules-mount');
    if (!container) return;

    var html = '<div class="awag-grid">';
    html += renderModule('Farming', '🌾', FARMING, idx, [
      {key:'activity', label:'Activity'},
      {key:'crops', label:'Crops'}
    ]);
    html += renderModule('Fishing', '🎣', FISHING, idx, [
      {key:'conditions', label:'Conditions'},
      {key:'activity', label:'Guide'},
      {key:'fish', label:'Target fish'}
    ]);
    html += renderModule('Trading', '🛒', TRADING, idx, [
      {key:'activity', label:'Market guide'},
      {key:'goods', label:'Key goods'}
    ]);
    html += renderModule('Herding', '🐄', HERDING, idx, [
      {key:'conditions', label:'Conditions'},
      {key:'activity', label:'Guide'}
    ]);
    html += renderModule('Healing', '🌿', HEALING, idx, [
      {key:'activity', label:'Guide'},
      {key:'herbs', label:'Herbs'},
      {key:'rituals', label:'Ritual'}
    ]);
    html += renderModule('Weather', '🌤', WEATHER, idx, [
      {key:'season', label:'Season'},
      {key:'rains', label:'Rainfall'},
      {key:'wind', label:'Wind'},
      {key:'advisory', label:'Advisory'}
    ]);
    html += '</div>';

    var month = FARMING[idx];
    var header = document.getElementById('awag-month-label');
    if (header && month) header.textContent = month.name + ' (Month ' + monthNo + ')';

    container.innerHTML = html;
  }

  /* ── Init ────────────────────────────────────────────────────── */
  function init() {
    var section = document.getElementById('awag-section');
    if (!section) return;

    // Watch for igbo calendar month changes
    var observer = new MutationObserver(function() {
      render(getMonth());
    });
    var app = document.getElementById('igcal-app');
    if (app) observer.observe(app, {attributes: true, subtree: true, attributeFilter: ['data-ig-month','data-ig-viewed-month']});

    render(getMonth());

    // Also re-render when calendar navigation buttons are clicked
    document.addEventListener('click', function(e) {
      if (e.target && (
        e.target.id === 'igcal-prev-month' ||
        e.target.id === 'igcal-next-month' ||
        e.target.id === 'igcal-this-month'
      )) {
        setTimeout(function() { render(getMonth()); }, 300);
      }
    });
  }

  window.awagRender = render;
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
// Direct render on load
window.addEventListener('load', function() {
  var mount = document.getElementById('awag-modules-mount');
  if (mount) {
    window.awagRender(1);
  }
});


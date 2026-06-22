/* awag-igbo-calendar.js
 * Igbo Calendar integration for AWAG Igbo skin
 *
 * Rules:
 *   - 13 months per year
 *   - Months 1-6, 8-13 = 28 days each
 *   - Month 7 (Ọnwa Alọm Chi) = 29 days
 *   - Total: 365 days (366 in leap year — extra day added to month 7)
 *   - New year anchor: first new moon in third week of February (Feb 15-21)
 *   - Time unit: nights (abalị), not days — Igbo day begins at sunset
 *   - Market week: 4-night cycle Eke → Orie → Afo → Nkwo → Eke...
 *   - Anchor: Sunday 21 June 2026 = Eke (day index 0)
 *
 * This module only runs on the Igbo skin page.
 */
(function () {
  'use strict';

  /* ── Only run on Igbo skin ───────────────────────────────────── */
  if (!/\/awag\/skins\/igbo\//.test(window.location.pathname)) return;

  /* ── Constants ───────────────────────────────────────────────── */
  var MARKET_DAYS = ['Eke', 'Orie', 'Afo', 'Nkwo'];
  var MARKET_COLORS = ['#ff6030', '#3090ff', '#30a050', '#a0c0ff'];

  // Anchor: 21 June 2026 = Eke (index 0)
  // Using UTC midnight to avoid timezone issues
  var ANCHOR_EKE = Date.UTC(2026, 5, 21); // June = month 5 (0-indexed)

  var MONTHS = [
    { n: 1,  name: 'Ọnwa Mbụ',                  days: 28, note: 'New Year (Mbido Afọ)' },
    { n: 2,  name: 'Ọnwa Abụọ',                  days: 28, note: 'Farm Clearing and Cleansing' },
    { n: 3,  name: 'Ọnwa Ife Eke',               days: 28, note: 'Fasting and Offering' },
    { n: 4,  name: 'Ọnwa Anọ',                   days: 28, note: 'Planting Season' },
    { n: 5,  name: 'Ọnwa Agwụ',                  days: 28, note: 'Masquerade Rites / Knowledge' },
    { n: 6,  name: 'Ọnwa Ifejiọkụ',             days: 28, note: 'Yam Festival / Rituals' },
    { n: 7,  name: 'Ọnwa Alọm Chi / Asaa',       days: 29, note: 'New Yam Festival' }, // 29 days
    { n: 8,  name: 'Ọnwa Ilo Mmụọ / Asatọ',     days: 28, note: 'Ancestral Veneration' },
    { n: 9,  name: 'Ọnwa Ala / Itolu',           days: 28, note: 'Ofala Festival' },
    { n: 10, name: 'Ọnwa Okike / Iri',           days: 28, note: 'Creation Myths / Offerings' },
    { n: 11, name: 'Ọnwa Ajala / Iri na otu',   days: 28, note: 'Spiritual Cleansing' },
    { n: 12, name: 'Ọnwa Ede Ajala / Iri na abụọ', days: 28, note: 'End-Year Offerings' },
    { n: 13, name: 'Ọnwa Ụzọ Arụsị / Iri na atọ', days: 28, note: 'Intercalary Rituals' },
  ];

  /* ── New moon calculator ─────────────────────────────────────── */
  // Returns UTC timestamp of new moon nearest to/after a given date
  function getNewMoonAfter(targetUTC) {
    // Synodic month = 29.53058867 days
    var SYNODIC = 29.53058867 * 24 * 3600 * 1000;
    // Known new moon reference: Jan 6 2000 18:14 UTC (JD 2451550.26)
    var REF_NEW_MOON = Date.UTC(2000, 0, 6, 18, 14, 0);
    var elapsed = targetUTC - REF_NEW_MOON;
    var cycles = Math.floor(elapsed / SYNODIC);
    var candidate = REF_NEW_MOON + cycles * SYNODIC;
    // Find first new moon >= targetUTC
    while (candidate < targetUTC) candidate += SYNODIC;
    return candidate;
  }

  // Find Igbo new year start for a given Gregorian year
  // = first new moon in Feb 15-21
  function getIgboYearStart(gregYear) {
    var feb15 = Date.UTC(gregYear, 1, 15); // Feb 15
    var feb22 = Date.UTC(gregYear, 1, 22); // Feb 22 (exclusive)
    var nm = getNewMoonAfter(feb15);
    // If new moon falls after Feb 21, use next opportunity
    // (this should not happen normally — if it does, use Feb 15 as fallback)
    if (nm >= feb22) {
      // Fallback: use Feb 15 of that year
      return feb15;
    }
    return nm;
  }

  /* ── Market day from Gregorian date ─────────────────────────── */
  function getMarketDay(date) {
    var utc = Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());
    var diff = Math.round((utc - ANCHOR_EKE) / (24 * 3600 * 1000));
    var idx = ((diff % 4) + 4) % 4;
    return { name: MARKET_DAYS[idx], index: idx, color: MARKET_COLORS[idx] };
  }

  /* ── Current Igbo month from Gregorian date ──────────────────── */
  function getIgboDate(date) {
    var utc = Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());
    var gregYear = date.getFullYear();

    // Try current year first, then previous
    var yearStart = getIgboYearStart(gregYear);
    if (utc < yearStart) {
      gregYear--;
      yearStart = getIgboYearStart(gregYear);
    }

    var dayOfYear = Math.floor((utc - yearStart) / (24 * 3600 * 1000));
    var igboYear = gregYear; // Igbo year maps to Gregorian year of new year start

    // Walk through months
    var month = 1;
    var dayInMonth = dayOfYear;
    for (var i = 0; i < MONTHS.length; i++) {
      if (dayInMonth < MONTHS[i].days) {
        month = MONTHS[i].n;
        break;
      }
      dayInMonth -= MONTHS[i].days;
    }

    // Night number (Igbo counts nights — night 1 begins at sunset of day 1)
    var nightNo = dayInMonth + 1;

    return {
      igboYear: igboYear,
      month: month,
      monthName: MONTHS[month - 1].name,
      monthNote: MONTHS[month - 1].note,
      nightNo: nightNo,
      totalNights: MONTHS[month - 1].days,
      dayOfYear: dayOfYear,
    };
  }

  /* ── Gregorian range for a given Igbo month ─────────────────── */
  function getMonthRange(igboYear, monthNo) {
    var yearStart = getIgboYearStart(igboYear);
    var offset = 0;
    for (var i = 0; i < monthNo - 1; i++) offset += MONTHS[i].days;
    var start = new Date(yearStart + offset * 24 * 3600 * 1000);
    var end = new Date(yearStart + (offset + MONTHS[monthNo - 1].days - 1) * 24 * 3600 * 1000);
    var fmt = function(d) {
      return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    };
    return fmt(start) + ' — ' + fmt(end);
  }

  /* ── Inject styles ───────────────────────────────────────────── */
  function injectCSS() {
    var css =
      '.igbo-cal{background:rgba(45,106,31,.08);border:1px solid rgba(45,106,31,.25);border-radius:14px;padding:18px 20px;margin:20px 0;font-family:inherit}' +
      '.igbo-cal__title{font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:rgba(232,240,232,.4);margin-bottom:12px}' +
      '.igbo-cal__row{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:10px}' +
      '.igbo-cal__item{flex:1;min-width:130px;background:rgba(0,0,0,.25);border-radius:9px;padding:10px 13px}' +
      '.igbo-cal__label{font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:rgba(232,240,232,.35);margin-bottom:3px}' +
      '.igbo-cal__val{font-size:.95rem;font-weight:800;color:#e8f0e8;line-height:1.3}' +
      '.igbo-cal__sub{font-size:.72rem;color:rgba(232,240,232,.45);margin-top:2px}' +
      '.igbo-cal__market{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:800;margin-top:4px}' +
      '.igbo-cal__note{font-size:.75rem;color:rgba(232,240,232,.4);margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.06);line-height:1.6}' +
      '.igbo-cal__range{font-size:.75rem;color:rgba(45,200,80,.6);margin-top:3px}';
    var s = document.createElement('style');
    s.textContent = css;
    document.head.appendChild(s);
  }

  /* ── Render ──────────────────────────────────────────────────── */
  function render() {
    var today = new Date();
    var igbo = getIgboDate(today);
    var market = getMarketDay(today);

    // Get selected month from URL param
    var urlMonth = parseInt((window.location.search.match(/[?&]m=(\d+)/) || [])[1] || igbo.month, 10);
    if (urlMonth < 1 || urlMonth > 13) urlMonth = igbo.month;

    var range = getMonthRange(igbo.igboYear, urlMonth);
    var viewedMonth = MONTHS[urlMonth - 1];

    var html =
      '<div class="igbo-cal">' +
      '<div class="igbo-cal__title">🗓 Igbo Calendar — Today</div>' +
      '<div class="igbo-cal__row">' +

      '<div class="igbo-cal__item">' +
      '<div class="igbo-cal__label">Igbo Month</div>' +
      '<div class="igbo-cal__val">' + igbo.monthName + '</div>' +
      '<div class="igbo-cal__sub">' + igbo.monthNote + '</div>' +
      '</div>' +

      '<div class="igbo-cal__item">' +
      '<div class="igbo-cal__label">Night (Abalị)</div>' +
      '<div class="igbo-cal__val">Abalị ' + igbo.nightNo + ' / ' + igbo.totalNights + '</div>' +
      '<div class="igbo-cal__sub">Igbo counts nights, not days</div>' +
      '</div>' +

      '<div class="igbo-cal__item">' +
      '<div class="igbo-cal__label">Market Day (Ụbọchị Ahịa)</div>' +
      '<div class="igbo-cal__val">' +
      '<span class="igbo-cal__market" style="background:' + market.color + '22;color:' + market.color + ';border:1px solid ' + market.color + '44">' +
      market.name + '</span>' +
      '</div>' +
      '<div class="igbo-cal__sub">4-night market cycle</div>' +
      '</div>' +

      '<div class="igbo-cal__item">' +
      '<div class="igbo-cal__label">Igbo Year</div>' +
      '<div class="igbo-cal__val">' + igbo.igboYear + '</div>' +
      '<div class="igbo-cal__sub">New year at Feb new moon</div>' +
      '</div>' +

      '</div>';

    // If viewing a different month, show its Gregorian range
    if (urlMonth !== igbo.month) {
      html +=
        '<div class="igbo-cal__row">' +
        '<div class="igbo-cal__item" style="flex:100%">' +
        '<div class="igbo-cal__label">Viewing: Month ' + urlMonth + '</div>' +
        '<div class="igbo-cal__val">' + viewedMonth.name + '</div>' +
        '<div class="igbo-cal__range">📅 ' + range + ' · ' + viewedMonth.days + ' nights</div>' +
        '<div class="igbo-cal__sub">' + viewedMonth.note + '</div>' +
        '</div>' +
        '</div>';
    } else {
      html +=
        '<div class="igbo-cal__range">📅 This month: ' + range + ' · ' + viewedMonth.days + ' nights</div>';
    }

    html +=
      '<div class="igbo-cal__note">' +
      '<strong>Ọ bụ abalị, ọ bụghị ụbọchị</strong> — Igbo time is measured in nights (abalị). ' +
      'The new year begins at the first new moon in the third week of February. ' +
      'Month 7 (' + MONTHS[6].name + ') has 29 nights to align with the solar year.' +
      '</div>' +
      '</div>';

    // Mount before the modules section
    var mount = document.querySelector('.modules');
    if (mount) {
      var div = document.createElement('div');
      div.className = 'wrap';
      div.innerHTML = html;
      mount.parentNode.insertBefore(div, mount);
    }
  }

  /* ── Init ────────────────────────────────────────────────────── */
  injectCSS();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', render);
  } else {
    render();
  }

})();
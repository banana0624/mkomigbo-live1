/* /public/igbo-calendar/igbo-calendar.js
 * Igbo Calendar renderer (client-side) -> builds calendar into #igcal-mount.
 *
 * Rules:
 * - 13 months
 * - Every month = 28 days
 * - Month 7 = 29 days
 * - Leap year: Month 1 = 29 days
 */

(function () {
  "use strict";

  var DAY_MS = 86400 * 1000;

  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function clampInt(v, lo, hi, fallback) {
    var n = parseInt(String(v === null || v === undefined ? "" : v), 10);
    if (!isFinite(n)) return fallback;
    if (n < lo) return lo;
    if (n > hi) return hi;
    return n;
  }

  function pad2(n) {
    n = String(n);
    return n.length === 1 ? ("0" + n) : n;
  }

  function isoYmd(d) {
    return d.getUTCFullYear() + "-" + pad2(d.getUTCMonth() + 1) + "-" + pad2(d.getUTCDate());
  }

  function parseIsoYmd(s) {
    s = String(s || "");
    var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
    if (!m) return null;

    var y = parseInt(m[1], 10);
    var mo = parseInt(m[2], 10);
    var da = parseInt(m[3], 10);

    if (!(y >= 1 && y <= 9999)) return null;
    if (!(mo >= 1 && mo <= 12)) return null;
    if (!(da >= 1 && da <= 31)) return null;

    var dt = new Date(Date.UTC(y, mo - 1, da, 0, 0, 0));
    return (isoYmd(dt) === (m[1] + "-" + m[2] + "-" + m[3])) ? dt : null;
  }

  function addUtcDays(d, days) {
    return new Date(d.getTime() + (days * DAY_MS));
  }

  function getAppRoot() {
    var all = document.querySelectorAll('[data-app="igbo-calendar"]');
    return (all && all.length) ? all[all.length - 1] : null;
  }

  function readState(app) {
    var attrY = app.getAttribute("data-selected-year");
    var yFallback = (new Date()).getUTCFullYear();
    var y = clampInt(attrY, 1, 9999, parseInt(attrY || "", 10) || yFallback);
    var m = clampInt(app.getAttribute("data-selected-month"), 1, 13, 1);

    var endpoint = String(app.getAttribute("data-yearstart-endpoint") || "");
    var todayIso = String(app.getAttribute("data-today-iso") || "");
    if (!/^\d{4}-\d{2}-\d{2}$/.test(todayIso)) todayIso = isoYmd(new Date());

    return { y: y, m: m, endpoint: endpoint, todayIso: todayIso };
  }

  function isGregorianLeapYear(y) {
    if (y % 400 === 0) return true;
    if (y % 100 === 0) return false;
    return (y % 4 === 0);
  }

  function monthDaysForYear(y) {
    var days = new Array(14);
    for (var i = 1; i <= 13; i++) days[i] = 28;
    days[7] = 29;
    if (isGregorianLeapYear(y)) days[1] = 29;
    return days;
  }

  var MONTH_NAMES = {
    1:  "Ọnwa Mbụ",
    2:  "Ọnwa Abụọ",
    3:  "Ọnwa Ife Eke",
    4:  "Ọnwa Anọ",
    5:  "Ọnwa Agwụ",
    6:  "Ọnwa Ifejiọkụ",
    7:  "Ọnwa Alọm Chi / Asaa",
    8:  "Ọnwa Ilo Mmụọ / Asatọ",
    9:  "Ọnwa Ala / Itolu",
    10: "Ọnwa Okike / Iri",
    11: "Ọnwa Ajala / Iri na otu",
    12: "Ọnwa Ede Ajala / Iri na abụọ",
    13: "Ọnwa Ụzọ Arụsị / Iri na atọ"
  };

  var MONTH_DETAILS = {
    1:  "Ọnwa Mbụ (Feb–Mar): New Year (Mbido Afọ)",
    2:  "Ọnwa Abụọ (Mar–Apr): Farm Clearing & Cleansing",
    3:  "Ọnwa Ife Eke (Apr–May): Fasting & Offering",
    4:  "Ọnwa Anọ (May–Jun): Planting Season",
    5:  "Ọnwa Agwụ (Jun–Jul): Masquerade Rites / Knowledge",
    6:  "Ọnwa Ifejiọkụ (Jul–Aug): Yam Festival / Rituals",
    7:  "Ọnwa Alọm Chi / Asaa (Aug–Sep): New Yam Festival",
    8:  "Ọnwa Ilo Mmụọ / Asatọ (Sep–Oct): Ancestral Veneration",
    9:  "Ọnwa Ala / Itolu (Oct–Nov): Ofala Festival",
    10: "Ọnwa Okike / Iri (Nov): Creation Myths / Offerings",
    11: "Ọnwa Ajala / Iri na otu (Nov–Dec): Spiritual Cleansing",
    12: "Ọnwa Ede Ajala / Iri na abụọ (Dec–Jan): End-Year Offerings",
    13: "Ọnwa Ụzọ Arụsị / Iri na atọ (Jan–Feb): Intercalary Rituals"
  };

  var MARKET_NAMES = ["Eke", "Orie", "Afo", "Nkwo"];
  var MARKET_SYMBOLS = {
    "Eke": "🔥",
    "Orie": "💧",
    "Afo": "🌍",
    "Nkwo": "💨"
  };

  var MARKET_ANCHOR_ISO = "2026-01-07";
  var MARKET_ANCHOR_IDX = 3; // Nkwo

  function marketIdxForIso(iso) {
    var a = parseIsoYmd(MARKET_ANCHOR_ISO);
    var d = parseIsoYmd(iso);
    if (!a || !d) return 0;

    var diffDays = Math.round((d.getTime() - a.getTime()) / DAY_MS);
    var idx = (MARKET_ANCHOR_IDX + (diffDays % 4)) % 4;
    if (idx < 0) idx += 4;
    return idx;
  }

  var LUNATION_DAYS = 29.530588853;
  var MOON_ANCHOR_ISO = "2025-01-29";

  function moonInfoForIso(iso) {
    var d0 = parseIsoYmd(MOON_ANCHOR_ISO);
    var d1 = parseIsoYmd(iso);
    if (!d0 || !d1) return { pct: 0, stage: "New Moon", emoji: "🌑", frame: 1 };

    var days = (d1.getTime() - d0.getTime()) / DAY_MS;
    var age = days % LUNATION_DAYS;
    if (age < 0) age += LUNATION_DAYS;

    var frac = age / LUNATION_DAYS;
    var illum = (1 - Math.cos(2 * Math.PI * frac)) / 2;
    var pct = Math.round(illum * 100);

    var stage = "New Moon";
    if (frac < 0.03 || frac > 0.97) stage = "New Moon";
    else if (frac < 0.22) stage = "Waxing Crescent";
    else if (frac < 0.28) stage = "First Quarter";
    else if (frac < 0.47) stage = "Waxing Gibbous";
    else if (frac < 0.53) stage = "Full Moon";
    else if (frac < 0.72) stage = "Waning Gibbous";
    else if (frac < 0.78) stage = "Last Quarter";
    else stage = "Waning Crescent";

    var emoji = "🌑";
    if (frac < 0.03 || frac > 0.97) emoji = "🌑";
    else if (frac < 0.22) emoji = "🌒";
    else if (frac < 0.28) emoji = "🌓";
    else if (frac < 0.47) emoji = "🌔";
    else if (frac < 0.53) emoji = "🌕";
    else if (frac < 0.72) emoji = "🌖";
    else if (frac < 0.78) emoji = "🌗";
    else emoji = "🌘";

    var frame = 1 + Math.max(0, Math.min(27, Math.floor(frac * 28)));
    return { pct: pct, stage: stage, emoji: emoji, frame: frame };
  }

  function formatUtcWeekday(iso) {
    try {
      return new Date(iso + "T00:00:00Z").toLocaleDateString(undefined, {
        weekday: "long",
        timeZone: "UTC"
      });
    } catch (e) {
      return "";
    }
  }

  function formatUtcLongDate(iso) {
    try {
      return new Date(iso + "T00:00:00Z").toLocaleDateString(undefined, {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
        timeZone: "UTC"
      });
    } catch (e) {
      return iso;
    }
  }

  function buildDayData(state, monthNo, igboDay, iso) {
    var marketIdx = marketIdxForIso(iso);
    var marketName = MARKET_NAMES[marketIdx];
    var moon = moonInfoForIso(iso);

    return {
      year: state.y,
      month: monthNo,
      monthName: MONTH_NAMES[monthNo] || ("Month " + monthNo),
      monthDetail: MONTH_DETAILS[monthNo] || "",
      igboDay: igboDay,
      gregorianIso: iso,
      gregorianWeekday: formatUtcWeekday(iso),
      gregorianLong: formatUtcLongDate(iso),
      marketName: marketName,
      marketSymbol: MARKET_SYMBOLS[marketName] || "",
      moonPct: moon.pct,
      moonStage: moon.stage,
      moonEmoji: moon.emoji,
      moonFrame: moon.frame,
      isToday: iso === state.todayIso
    };
  }

  function el(tag, cls, textValue) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (textValue !== undefined && textValue !== null) n.textContent = String(textValue);
    return n;
  }

  function clear(node) {
    while (node && node.firstChild) node.removeChild(node.firstChild);
  }

  function renderMonthTable(state, monthNo, monthStartIso) {
    var mdays = monthDaysForYear(state.y);
    var daysInMonth = mdays[monthNo] || 28;
    var monthName = MONTH_NAMES[monthNo] || ("Month " + monthNo);
    var monthLabel = monthNo + " — " + monthName;

    var wrap = el("section", "mk-cal-month igcal-month");
    wrap.setAttribute("data-ig-month", String(monthNo));
    wrap.setAttribute("data-month", String(monthNo));
    wrap.setAttribute("aria-label", monthLabel);

    var head = el("div", "mk-cal-month__head");
    head.appendChild(el("h2", "mk-cal-month__title", monthLabel));
    head.appendChild(el("div", "mk-cal-month__meta muted", (MONTH_DETAILS[monthNo] || "") + " · Days: " + daysInMonth));
    wrap.appendChild(head);

    var tableWrap = el("div", "mk-cal-table-wrap");
    var table = el("table", "mk-cal__table mk-cal-grid");
    table.setAttribute("role", "table");
    table.setAttribute("aria-label", monthLabel + " grid");

    var thead = document.createElement("thead");
    var trh = document.createElement("tr");
    for (var i = 0; i < 4; i++) {
      var th = document.createElement("th");
      th.setAttribute("scope", "col");
      th.textContent = MARKET_NAMES[i];
      trh.appendChild(th);
    }
    thead.appendChild(trh);
    table.appendChild(thead);

    var tbody = document.createElement("tbody");
    var startCol = marketIdxForIso(monthStartIso);
    var totalCells = startCol + daysInMonth;
    var rows = Math.ceil(totalCells / 4);
    var day = 1;
    var monthStartDate = parseIsoYmd(monthStartIso);

    for (var r = 0; r < rows; r++) {
      var tr = document.createElement("tr");
      tr.className = "igcal-week";
      tr.setAttribute("data-ig-row", String(r + 1));

      for (var c = 0; c < 4; c++) {
        var cellNumber = (r * 4) + c;
        var td = document.createElement("td");

        if (cellNumber < startCol) {
          td.className = "mk-cal-empty";
          td.innerHTML = "&nbsp;";
          tr.appendChild(td);
          continue;
        }

        var effective = cellNumber - startCol;
        if (effective >= 0 && effective < daysInMonth && monthStartDate) {
          var iso = isoYmd(addUtcDays(monthStartDate, effective));
          var dayData = buildDayData(state, monthNo, day, iso);

          td.className = "mk-cal-cell" + (dayData.isToday ? " mk-cal-cell--today" : "");
          td.setAttribute("data-iso", iso);
          td.setAttribute("data-gregorian", iso);
          td.setAttribute("data-igbo-day", String(day));
          td.setAttribute("data-day", String(day));
          td.setAttribute("data-igbo-month", String(monthNo));
          td.setAttribute("data-month", String(monthNo));
          td.setAttribute("data-igbo-year", String(state.y));
          td.setAttribute("data-year", String(state.y));
          td.setAttribute("data-market", dayData.marketName);
          td.setAttribute("data-market-day", dayData.marketName);
          td.setAttribute("data-market-symbol", dayData.marketSymbol);
          td.setAttribute("data-weekday", dayData.gregorianWeekday);
          td.setAttribute("data-weekday-long", dayData.gregorianLong);
          td.setAttribute("data-date-label", dayData.gregorianLong);
          td.setAttribute("data-igbo-month-name", dayData.monthName);
          td.setAttribute("data-month-name", dayData.monthName);
          td.setAttribute("data-month-detail", dayData.monthDetail);
          td.setAttribute("data-moon-stage", dayData.moonStage);
          td.setAttribute("data-moon-pct", String(dayData.moonPct));
          td.setAttribute("data-moon-icon", dayData.moonEmoji);
          td.setAttribute("data-moon-emoji", dayData.moonEmoji);
          td.setAttribute("data-moon-frame", String(dayData.moonFrame));

          var btn = document.createElement("button");
          btn.type = "button";
          btn.className = "mk-cal-cell__button";
          btn.setAttribute("aria-label", dayData.monthName + ", Igbo day " + dayData.igboDay + ", " + dayData.gregorianLong);

          var inner = el("div", "mk-cal-cell__inner");

          var top = el("div", "mk-cal-cell__top");
          var topLeft = el("div", "mk-cal-daystack");
          topLeft.appendChild(el("div", "mk-cal-market", dayData.marketName + " " + dayData.marketSymbol));
          topLeft.appendChild(el("div", "mk-cal-day", "Igbo Day " + dayData.igboDay));
          top.appendChild(topLeft);
          top.appendChild(el("div", "mk-cal-iso muted", iso));
          inner.appendChild(top);

          if (dayData.isToday) inner.appendChild(el("div", "mk-cal-today-badge", "TODAY"));

          var kv = el("div", "mk-cal-kv");
          kv.appendChild(el("div", "mk-cal-line", dayData.gregorianWeekday + " · " + iso));
          kv.appendChild(el("div", "mk-cal-line", "Moon: " + dayData.moonStage + " " + dayData.moonEmoji + " · " + dayData.moonPct + "%"));
          inner.appendChild(kv);

          var bar = document.createElement("div");
          bar.className = "mk-moonbar";
          bar.setAttribute("aria-hidden", "true");
          bar.innerHTML = '<span style="width:' + String(dayData.moonPct) + '%"></span>';
          inner.appendChild(bar);

          btn.appendChild(inner);
          td.appendChild(btn);
          day++;
        } else {
          td.className = "mk-cal-empty";
          td.innerHTML = "&nbsp;";
        }

        tr.appendChild(td);
      }

      tbody.appendChild(tr);
    }

    table.appendChild(tbody);
    tableWrap.appendChild(table);
    wrap.appendChild(tableWrap);

    return wrap;
  }

  function showOnlyMonth(mount, monthNo) {
    monthNo = clampInt(monthNo, 1, 13, 1);
    qsa('.igcal-month[data-ig-month]', mount).forEach(function (section) {
      var mm = parseInt(section.getAttribute("data-ig-month") || "0", 10);
      var on = (mm === monthNo);
      section.hidden = !on;
      section.setAttribute("aria-hidden", on ? "false" : "true");
    });
  }

  function renderCalendarIntoMount(mount, state, yearStartIso) {
    clear(mount);

    var mdays = monthDaysForYear(state.y);
    var ys = parseIsoYmd(yearStartIso || "");
    if (!ys) ys = parseIsoYmd(state.y + "-02-18");

    var cursor = ys;
    for (var m = 1; m <= 13; m++) {
      var startIso = isoYmd(cursor);
      mount.appendChild(renderMonthTable(state, m, startIso));
      cursor = addUtcDays(cursor, mdays[m] || 28);
    }

    showOnlyMonth(mount, state.m);
  }

  function selectBestDefaultDay(app) {
    var mount = qs("#igcal-mount", app);
    if (!mount) return null;

    var todayCell = mount.querySelector(".mk-cal-cell--today");
    if (todayCell && !todayCell.closest(".igcal-month[hidden]")) return todayCell;

    var visibleMonth = mount.querySelector('.igcal-month[data-ig-month="' + String(readState(app).m) + '"]');
    if (!visibleMonth) return null;

    return visibleMonth.querySelector(".mk-cal-cell") || null;
  }

  var _ysCache = Object.create(null);

  function ysCacheKey(year) {
    return "igcal:yearstart:" + String(year);
  }

  function readStoredYearStart(year) {
    try {
      var v = window.localStorage.getItem(ysCacheKey(year));
      return v ? String(v) : null;
    } catch (_) {
      return null;
    }
  }

  function writeStoredYearStart(year, iso) {
    try {
      if (iso) window.localStorage.setItem(ysCacheKey(year), String(iso));
    } catch (_) {}
  }

  function fetchYearStart(endpoint, year) {
    if (_ysCache[year] !== undefined) {
      return Promise.resolve({ ok: !!_ysCache[year], ys: _ysCache[year] || null });
    }

    var stored = readStoredYearStart(year);
    if (stored) {
      _ysCache[year] = stored;
      return Promise.resolve({ ok: true, ys: stored });
    }

    if (!endpoint) {
      _ysCache[year] = null;
      return Promise.resolve({ ok: false, ys: null });
    }

    try {
      var u = new URL(endpoint, window.location.origin);
      u.searchParams.set("year", String(year));

      return fetch(u.toString(), {
        credentials: "same-origin",
        cache: "no-store"
      })
        .then(function (r) {
          if (!r || !r.ok) throw new Error("year-start fetch failed");
          return r.json();
        })
        .then(function (j) {
          var ys = (j && j.ok && j.ys) ? String(j.ys) : null;
          _ysCache[year] = ys;
          if (ys) writeStoredYearStart(year, ys);
          return { ok: !!ys, ys: ys };
        })
        .catch(function () {
          var fallback = readStoredYearStart(year);
          _ysCache[year] = fallback || null;
          return { ok: !!_ysCache[year], ys: _ysCache[year] };
        });
    } catch (e) {
      var fallback = readStoredYearStart(year);
      _ysCache[year] = fallback || null;
      return Promise.resolve({ ok: !!_ysCache[year], ys: _ysCache[year] });
    }
  }

  function boot() {
    var app = getAppRoot();
    if (!app) return;

    var mount = qs("#igcal-mount", app);
    if (!mount) return;

    var state = readState(app);
    mount.setAttribute("data-rendering", "1");
    mount.setAttribute("aria-busy", "true");

    fetchYearStart(state.endpoint, state.y).then(function (data) {
      var ys = (data && data.ok && data.ys) ? String(data.ys) : "";
      renderCalendarIntoMount(mount, state, ys);

      mount.removeAttribute("data-rendering");
      mount.setAttribute("data-rendered", "1");
      mount.setAttribute("aria-busy", "false");

      var defaultCell = selectBestDefaultDay(app);

      try {
        window.dispatchEvent(new CustomEvent("igcal:rendered", {
          detail: {
            year: state.y,
            month: state.m,
            yearStartIso: ys,
            defaultIso: defaultCell ? String(defaultCell.getAttribute("data-iso") || "") : ""
          }
        }));
      } catch (e) {}
    }).catch(function () {
      renderCalendarIntoMount(mount, state, "");
      mount.removeAttribute("data-rendering");
      mount.setAttribute("data-rendered", "1");
      mount.setAttribute("aria-busy", "false");

      var defaultCell = selectBestDefaultDay(app);

      try {
        window.dispatchEvent(new CustomEvent("igcal:rendered", {
          detail: {
            year: state.y,
            month: state.m,
            yearStartIso: "",
            defaultIso: defaultCell ? String(defaultCell.getAttribute("data-iso") || "") : ""
          }
        }));
      } catch (e2) {}
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot, { once: true });
  } else {
    boot();
  }
})();
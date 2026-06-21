/* /public/igbo-calendar/calendar-ux.js */
(function () {
  "use strict";

  window.__igcalUxLoaded = "2026-03-25-year-nav-hardening";

  var ONE_DAY_MS = 86400 * 1000;
  var STORAGE_KEY_PREFERRED_DAY = "igcal_preferred_day_iso_v1";
  var YEAR_START_CACHE = Object.create(null);

  var MARKET_NAMES = ["Eke", "Orie", "Afo", "Nkwo"];
  var MARKET_SYMBOLS = {
    Eke: "🔥",
    Orie: "💧",
    Afo: "🌍",
    Nkwo: "💨"
  };

  var MARKET_ANCHOR_ISO = "2026-01-07";
  var MARKET_ANCHOR_IDX = 3;

  var LUNATION_DAYS = 29.530588853;
  var MOON_ANCHOR_ISO = "2025-01-29";

  var MONTH_NAMES = {
    1: "Ọnwa Mbụ",
    2: "Ọnwa Abụọ",
    3: "Ọnwa Ife Eke",
    4: "Ọnwa Anọ",
    5: "Ọnwa Agwụ",
    6: "Ọnwa Ifejiọkụ",
    7: "Ọnwa Alọm Chi / Asaa",
    8: "Ọnwa Ilo Mmụọ / Asatọ",
    9: "Ọnwa Ala / Itolu",
    10: "Ọnwa Okike / Iri",
    11: "Ọnwa Ajala / Iri na otu",
    12: "Ọnwa Ede Ajala / Iri na abụọ",
    13: "Ọnwa Ụzọ Arụsị / Iri na atọ"
  };

  var MONTH_DETAILS = {
    1: "Ọnwa Mbụ (Feb–Mar): New Year (Mbido Afọ)",
    2: "Ọnwa Abụọ (Mar–Apr): Farm Clearing & Cleansing",
    3: "Ọnwa Ife Eke (Apr–May): Fasting & Offering",
    4: "Ọnwa Anọ (May–Jun): Planting Season",
    5: "Ọnwa Agwụ (Jun–Jul): Masquerade Rites / Knowledge",
    6: "Ọnwa Ifejiọkụ (Jul–Aug): Yam Festival / Rituals",
    7: "Ọnwa Alọm Chi / Asaa (Aug–Sep): New Yam Festival",
    8: "Ọnwa Ilo Mmụọ / Asatọ (Sep–Oct): Ancestral Veneration",
    9: "Ọnwa Ala / Itolu (Oct–Nov): Ofala Festival",
    10: "Ọnwa Okike / Iri (Nov): Creation Myths / Offerings",
    11: "Ọnwa Ajala / Iri na otu (Nov–Dec): Spiritual Cleansing",
    12: "Ọnwa Ede Ajala / Iri na abụọ (Dec–Jan): End-Year Offerings",
    13: "Ọnwa Ụzọ Arụsị / Iri na atọ (Jan–Feb): Intercalary Rituals"
  };

  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function qsa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function text(el) {
    return ((el && (el.innerText || el.textContent)) || "").replace(/\s+/g, " ").trim();
  }

  function esc(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function clampInt(v, lo, hi, fallback) {
    var n = parseInt(String(v == null ? "" : v), 10);
    if (!isFinite(n)) return fallback;
    if (n < lo) return lo;
    if (n > hi) return hi;
    return n;
  }

  function app() {
    var all = document.querySelectorAll('[data-app="igbo-calendar"]');
    return all.length ? all[all.length - 1] : null;
  }

  function isIso(v) {
    return /^\d{4}-\d{2}-\d{2}$/.test(String(v || ""));
  }

  function parseYmdToUtcDate(s) {
    var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(s || ""));
    if (!m) return null;

    var y = parseInt(m[1], 10);
    var mo = parseInt(m[2], 10);
    var d = parseInt(m[3], 10);
    var dt = new Date(Date.UTC(y, mo - 1, d, 0, 0, 0));
    var iso = utcDateToYmd(dt);

    return iso === (m[1] + "-" + m[2] + "-" + m[3]) ? dt : null;
  }

  function utcDateToYmd(dt) {
    return dt.getUTCFullYear() + "-" +
      String(dt.getUTCMonth() + 1).padStart(2, "0") + "-" +
      String(dt.getUTCDate()).padStart(2, "0");
  }

  function formatUtcLongDate(iso) {
    try {
      return new Date(String(iso) + "T00:00:00Z").toLocaleDateString(undefined, {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
        timeZone: "UTC"
      });
    } catch (_) {
      return String(iso || "");
    }
  }

  function formatIsoHuman(iso) {
    if (!isIso(iso)) return "";
    try {
      return new Date(iso + "T00:00:00Z").toLocaleDateString("en-GB", {
        year: "numeric",
        month: "short",
        day: "numeric",
        timeZone: "UTC"
      });
    } catch (_) {
      return iso;
    }
  }

  function isLeapYear(y) {
    if (y % 400 === 0) return true;
    if (y % 100 === 0) return false;
    return y % 4 === 0;
  }

  function getMonthDays(y, m) {
    if (m === 7) return 29;
    if (m === 1 && isLeapYear(y)) return 29;
    return 28;
  }

  function monthName(m) {
    return MONTH_NAMES[m] || ("Month " + m);
  }

  function monthDetail(m) {
    return MONTH_DETAILS[m] || "";
  }

  function parseIsoParts(iso) {
    if (!isIso(iso)) return null;
    var m = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!m) return null;
    return {
      year: parseInt(m[1], 10),
      month: parseInt(m[2], 10),
      day: parseInt(m[3], 10)
    };
  }

  function getState(a) {
    return {
      y: clampInt(a.getAttribute("data-selected-year"), 1, 9999, (new Date()).getUTCFullYear()),
      m: clampInt(a.getAttribute("data-selected-month"), 1, 13, 1)
    };
  }

  function setState(a, y, m) {
    a.setAttribute("data-selected-year", String(y));
    a.setAttribute("data-selected-month", String(m));
  }

  function hardNav(y, m) {
    window.location.href =
      "/igbo-calendar/?year=" + encodeURIComponent(String(y)) +
      "&m=" + encodeURIComponent(String(m));
  }

  function setLoading(a, isLoading) {
    var loading = qs(".igcal-loading", a);
    var mount = qs("#igcal-mount", a);
    if (loading) loading.hidden = !isLoading;
    if (mount) mount.setAttribute("aria-busy", isLoading ? "true" : "false");
  }

  function yearStartCacheKey(year) {
    return "igcal:yearstart:" + String(year);
  }

  function readStoredYearStart(year) {
    try {
      var v = window.localStorage.getItem(yearStartCacheKey(year));
      return v ? String(v) : null;
    } catch (_) {
      return null;
    }
  }

  function writeStoredYearStart(year, iso) {
    try {
      if (iso) window.localStorage.setItem(yearStartCacheKey(year), String(iso));
    } catch (_) {}
  }

  function fetchYearStart(a, year) {
    if (YEAR_START_CACHE[year] !== undefined) {
      return Promise.resolve(YEAR_START_CACHE[year]);
    }

    var stored = readStoredYearStart(year);
    if (stored) {
      YEAR_START_CACHE[year] = stored;
      return Promise.resolve(stored);
    }

    var endpoint = a.getAttribute("data-yearstart-endpoint") || "";
    if (!endpoint) {
      YEAR_START_CACHE[year] = null;
      return Promise.resolve(null);
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
          YEAR_START_CACHE[year] = ys;
          if (ys) writeStoredYearStart(year, ys);
          return ys;
        })
        .catch(function () {
          var fallback = readStoredYearStart(year);
          YEAR_START_CACHE[year] = fallback || null;
          return YEAR_START_CACHE[year];
        });
    } catch (_) {
      var fallback = readStoredYearStart(year);
      YEAR_START_CACHE[year] = fallback || null;
      return Promise.resolve(YEAR_START_CACHE[year]);
    }
  }

  function marketIdxForIso(iso) {
    var anchor = parseYmdToUtcDate(MARKET_ANCHOR_ISO);
    var d = parseYmdToUtcDate(iso);
    if (!anchor || !d) return 0;

    var diffDays = Math.round((d.getTime() - anchor.getTime()) / ONE_DAY_MS);
    var idx = (MARKET_ANCHOR_IDX + (diffDays % 4)) % 4;
    if (idx < 0) idx += 4;
    return idx;
  }

  function moonInfoForIso(iso) {
    var d0 = parseYmdToUtcDate(MOON_ANCHOR_ISO);
    var d1 = parseYmdToUtcDate(iso);
    if (!d0 || !d1) return { pct: 0, stage: "New Moon", emoji: "🌑", frame: 1 };

    var days = (d1.getTime() - d0.getTime()) / ONE_DAY_MS;
    var age = days % LUNATION_DAYS;
    if (age < 0) age += LUNATION_DAYS;

    var frac = age / LUNATION_DAYS;
    var illum = (1 - Math.cos(2 * Math.PI * frac)) / 2;
    var pct = Math.round(illum * 100);

    var stage = "New Moon";
    var emoji = "🌑";

    if (frac < 0.03 || frac > 0.97) {
      stage = "New Moon"; emoji = "🌑";
    } else if (frac < 0.22) {
      stage = "Waxing Crescent"; emoji = "🌒";
    } else if (frac < 0.28) {
      stage = "First Quarter"; emoji = "🌓";
    } else if (frac < 0.47) {
      stage = "Waxing Gibbous"; emoji = "🌔";
    } else if (frac < 0.53) {
      stage = "Full Moon"; emoji = "🌕";
    } else if (frac < 0.72) {
      stage = "Waning Gibbous"; emoji = "🌖";
    } else if (frac < 0.78) {
      stage = "Last Quarter"; emoji = "🌗";
    } else {
      stage = "Waning Crescent"; emoji = "🌘";
    }

    return {
      pct: pct,
      stage: stage,
      emoji: emoji,
      frame: 1 + Math.max(0, Math.min(27, Math.floor(frac * 28)))
    };
  }

  function htmlCurrentBox(d) {
    return ""
      + '<div class="igcal-summary-line"><strong>' + esc(d.marketName + " " + d.marketSymbol) + "</strong></div>"
      + '<div class="igcal-summary-line">Igbo Year: <strong>' + esc(String(d.igboYear)) + "</strong></div>"
      + '<div class="igcal-summary-line">Igbo Month: <strong>' + esc(d.igboMonthName) + "</strong></div>"
      + '<div class="igcal-summary-line">' + esc(monthDetail(d.igboMonth)) + "</div>"
      + '<div class="igcal-summary-line">Igbo Day: <strong>' + esc(String(d.igboDay)) + "</strong></div>"
      + '<div class="igcal-summary-line">' + esc(d.gregorianLong) + "</div>"
      + '<div class="igcal-summary-line">Moon: <strong>' + esc(d.moonStage) + "</strong> " + esc(d.moonEmoji) + " · " + esc(String(d.moonPct)) + "%</div>";
  }

  function htmlViewedDayBox(d) {
    return ""
      + '<div class="igcal-summary-line"><strong>' + esc(d.marketName + " " + d.marketSymbol) + "</strong></div>"
      + '<div class="igcal-summary-line">Igbo Year: <strong>' + esc(String(d.year)) + "</strong></div>"
      + '<div class="igcal-summary-line">Igbo Month: <strong>' + esc(d.monthName) + "</strong></div>"
      + '<div class="igcal-summary-line">Igbo Day: <strong>' + esc(String(d.igboDay)) + "</strong></div>"
      + '<div class="igcal-summary-line">' + esc(d.gregorianLong) + "</div>"
      + '<div class="igcal-summary-line">Moon: <strong>' + esc(d.moonStage) + "</strong> " + esc(d.moonEmoji) + " · " + esc(String(d.moonPct)) + "%</div>";
  }

  function htmlViewedMonthBox(y, m, startIso, endIso) {
    return ""
      + '<div class="igcal-summary-line"><strong>' + esc(monthName(m)) + "</strong></div>"
      + '<div class="igcal-summary-line">' + esc(monthDetail(m)) + "</div>"
      + '<div class="igcal-summary-line">Igbo Year: <strong>' + esc(String(y)) + "</strong></div>"
      + '<div class="igcal-summary-line">Month Days: <strong>' + esc(String(getMonthDays(y, m))) + "</strong></div>"
      + '<div class="igcal-summary-line">Gregorian Start: <strong>' + esc(startIso || "Unavailable") + "</strong></div>"
      + '<div class="igcal-summary-line">Gregorian End: <strong>' + esc(endIso || "Unavailable") + "</strong></div>";
  }

  function htmlViewedYearBox(y, m) {
    return ""
      + '<div class="igcal-summary-line"><strong>Igbo Year ' + esc(String(y)) + "</strong></div>"
      + '<div class="igcal-summary-line">Viewed Month: <strong>' + esc(monthName(m)) + "</strong></div>"
      + '<div class="igcal-summary-line">Leap Year Rule: ' + esc(isLeapYear(y) ? "Month 1 has 29 days." : "Month 1 has 28 days.") + "</div>"
      + '<div class="igcal-summary-line">Month 7 always has 29 days.</div>';
  }

  function htmlRangeBox(y, ys, ysNext) {
    var endLabel = "Unavailable";
    if (ysNext) {
      var nextDt = parseYmdToUtcDate(ysNext);
      if (nextDt) {
        endLabel = utcDateToYmd(new Date(nextDt.getTime() - ONE_DAY_MS));
      }
    }

    return ""
      + '<div class="igcal-summary-line"><strong>Igbo Year ' + esc(String(y)) + "</strong></div>"
      + '<div class="igcal-summary-line">Starts: <strong>' + esc(ys || "Unavailable") + "</strong></div>"
      + '<div class="igcal-summary-line">Ends: <strong>' + esc(endLabel) + "</strong></div>";
  }

  function findVisibleMonthEl(a) {
    var st = getState(a);
    return qs('.igcal-month[data-ig-month="' + String(st.m) + '"]', a);
  }

  function showOnlyMonth(a, m) {
    var mount = qs("#igcal-mount", a);
    if (!mount) return null;

    var chosen = null;
    qsa('.igcal-month[data-ig-month]', mount).forEach(function (el) {
      var mm = parseInt(el.getAttribute("data-ig-month") || "0", 10);
      var on = mm === m;
      el.hidden = !on;
      el.setAttribute("aria-hidden", on ? "false" : "true");
      if (on) chosen = el;
    });
    return chosen;
  }

  function allDayCells(root) {
    return qsa(".mk-cal-cell", root || app());
  }

  function getCellButton(cell) {
    return qs(".mk-cal-cell__button", cell) || cell;
  }

  function getCellIso(cell) {
    if (!cell) return "";
    var iso = String(cell.getAttribute("data-iso") || "").trim();
    if (isIso(iso)) return iso;

    var isoNode = qs(".mk-cal-iso", cell);
    var txt = text(isoNode);
    return isIso(txt) ? txt : "";
  }

  function findCellByIsoInApp(a, iso) {
    return iso ? qs('.mk-cal-cell[data-iso="' + String(iso) + '"]', a) : null;
  }

  function firstSelectableCellInVisibleMonth(a) {
    var monthEl = findVisibleMonthEl(a);
    if (!monthEl) return null;

    var todayIso = a.getAttribute("data-today-iso") || "";
    var todayCell = qs('.mk-cal-cell[data-iso="' + todayIso + '"]', monthEl);
    return todayCell || qs(".mk-cal-cell", monthEl);
  }

  function getSelectedViewedCell(a) {
    return qs(".mk-cal-cell.is-selected", a) || firstSelectableCellInVisibleMonth(a);
  }

  function clearViewedSelection(a) {
    allDayCells(a).forEach(function (cell) {
      cell.classList.remove("is-selected");
      var btn = getCellButton(cell);
      if (btn) btn.setAttribute("aria-pressed", "false");
    });
  }

  function rememberPreferredIso(iso) {
    if (!isIso(iso)) return;
    try {
      sessionStorage.setItem(STORAGE_KEY_PREFERRED_DAY, iso);
    } catch (_) {}
  }

  function readPreferredIso() {
    try {
      return sessionStorage.getItem(STORAGE_KEY_PREFERRED_DAY) || "";
    } catch (_) {
      return "";
    }
  }

  function buildDayDataFromCell(cell) {
    if (!cell) return null;

    var iso = getCellIso(cell);
    var igboDay = parseInt(
      cell.getAttribute("data-igbo-day") ||
      cell.getAttribute("data-day") ||
      "0",
      10
    );

    var month = parseInt(
      cell.getAttribute("data-igbo-month") ||
      cell.getAttribute("data-month") ||
      "0",
      10
    );

    var year = parseInt(
      cell.getAttribute("data-igbo-year") ||
      cell.getAttribute("data-year") ||
      "0",
      10
    );

    if (!iso || !igboDay || !month || !year) return null;

    var marketName = String(
      cell.getAttribute("data-market-day") ||
      cell.getAttribute("data-market") ||
      ""
    ) || MARKET_NAMES[marketIdxForIso(iso)] || "";

    var marketSymbol = String(cell.getAttribute("data-market-symbol") || "") || MARKET_SYMBOLS[marketName] || "";

    var gregorianLong = String(
      cell.getAttribute("data-date-label") ||
      cell.getAttribute("data-weekday-long") ||
      ""
    ) || formatUtcLongDate(iso);

    var moonStage = String(cell.getAttribute("data-moon-stage") || "");
    var moonPct = parseInt(cell.getAttribute("data-moon-pct") || "0", 10);
    var moonEmoji = String(
      cell.getAttribute("data-moon-icon") ||
      cell.getAttribute("data-moon-emoji") ||
      ""
    );

    if (!moonStage || !moonEmoji || isNaN(moonPct)) {
      var moon = moonInfoForIso(iso);
      moonStage = moonStage || moon.stage;
      moonPct = isNaN(moonPct) ? moon.pct : moonPct;
      moonEmoji = moonEmoji || moon.emoji;
    }

    return {
      year: year,
      month: month,
      monthName:
        cell.getAttribute("data-igbo-month-name") ||
        cell.getAttribute("data-month-name") ||
        monthName(month),

      monthDetail:
        cell.getAttribute("data-month-detail") ||
        monthDetail(month),

      igboDay: igboDay,
      gregorianIso: iso,
      gregorianLong: gregorianLong,
      marketName: marketName,
      marketSymbol: marketSymbol,
      moonStage: moonStage,
      moonPct: moonPct,
      moonEmoji: moonEmoji
    };
  }

  function deriveCurrentIgboData(a) {
    var todayIso = a.getAttribute("data-today-iso") || "";
    if (!isIso(todayIso)) {
      todayIso = utcDateToYmd(new Date());
    }

    var todayDt = parseYmdToUtcDate(todayIso);
    if (!todayDt) return Promise.resolve(null);

    var gregYear = todayDt.getUTCFullYear();

    return Promise.all([
      fetchYearStart(a, gregYear - 1),
      fetchYearStart(a, gregYear),
      fetchYearStart(a, gregYear + 1)
    ]).then(function (res) {
      var ysPrev = res[0];
      var ysCurr = res[1];
      var ysNext = res[2];

      var todayMs = todayDt.getTime();
      var currStart = ysCurr ? parseYmdToUtcDate(ysCurr) : null;
      var nextStart = ysNext ? parseYmdToUtcDate(ysNext) : null;
      var prevStart = ysPrev ? parseYmdToUtcDate(ysPrev) : null;

      var igboYear = gregYear;
      var startDt = currStart;

      if (currStart && todayMs < currStart.getTime()) {
        igboYear = gregYear - 1;
        startDt = prevStart;
      } else if (currStart && nextStart && todayMs >= nextStart.getTime()) {
        igboYear = gregYear + 1;
        startDt = nextStart;
      }

      if (!startDt) return null;

      var diffDays = Math.floor((todayMs - startDt.getTime()) / ONE_DAY_MS);
      if (diffDays < 0) return null;

      var month = 1;
      var day = diffDays + 1;

      for (var mm = 1; mm <= 13; mm++) {
        var md = getMonthDays(igboYear, mm);
        if (day <= md) {
          month = mm;
          break;
        }
        day -= md;
      }

      var marketName = MARKET_NAMES[marketIdxForIso(todayIso)];
      var moon = moonInfoForIso(todayIso);

      return {
        todayIso: todayIso,
        gregorianLong: formatUtcLongDate(todayIso),
        igboYear: igboYear,
        igboMonth: month,
        igboDay: day,
        igboMonthName: monthName(month),
        marketName: marketName,
        marketSymbol: MARKET_SYMBOLS[marketName] || "",
        moonStage: moon.stage,
        moonPct: moon.pct,
        moonEmoji: moon.emoji
      };
    }).catch(function () {
      return null;
    });
  }

  function updateCurrentSummary(a) {
    var box = qs("#igcal-current-summary", a);
    if (!box) return;

    box.textContent = "Loading current day…";

    deriveCurrentIgboData(a).then(function (d) {
      if (!d) {
        box.textContent = "Current day details unavailable.";
        return;
      }

      box.innerHTML = htmlCurrentBox(d);

      var yEl = qs("#igcal-current-year-label", a);
      var mEl = qs("#igcal-current-month-label", a);
      var dEl = qs("#igcal-current-day-label", a);

      if (yEl) yEl.textContent = String(d.igboYear);
      if (mEl) mEl.textContent = d.igboMonthName;
      if (dEl) dEl.textContent = d.marketName + " · Igbo Day " + d.igboDay;
    }).catch(function () {
      box.textContent = "Current day details unavailable.";
    });
  }

  function updateViewedDaySummary(a, cell) {
    var box = qs("#igcal-viewed-day-summary", a);
    if (!box) return null;

    cell = cell || firstSelectableCellInVisibleMonth(a);
    if (!cell) {
      clearViewedSelection(a);
      box.textContent = "Select a day from the calendar.";
      return null;
    }

    clearViewedSelection(a);
    cell.classList.add("is-selected");

    var btn = getCellButton(cell);
    if (btn) btn.setAttribute("aria-pressed", "true");

    var d = buildDayDataFromCell(cell);
    if (!d) {
      box.textContent = "Select a day from the calendar.";
      return null;
    }

    rememberPreferredIso(d.gregorianIso);
    box.innerHTML = htmlViewedDayBox(d);
    refreshDayCellStateClasses(a);
    return cell;
  }

  function deriveViewedMonthRange(a) {
    var st = getState(a);

    return fetchYearStart(a, st.y).then(function (ys) {
      var startDt = parseYmdToUtcDate(ys || "");
      if (!startDt) return { startIso: "Unavailable", endIso: "Unavailable" };

      var offsetDays = 0;
      for (var mm = 1; mm < st.m; mm++) {
        offsetDays += getMonthDays(st.y, mm);
      }

      var monthStartDt = new Date(startDt.getTime() + (offsetDays * ONE_DAY_MS));
      var monthEndDt = new Date(monthStartDt.getTime() + ((getMonthDays(st.y, st.m) - 1) * ONE_DAY_MS));

      return {
        startIso: utcDateToYmd(monthStartDt),
        endIso: utcDateToYmd(monthEndDt)
      };
    }).catch(function () {
      return { startIso: "Unavailable", endIso: "Unavailable" };
    });
  }

  function updateMonthSummary(a) {
    var box = qs("#igcal-viewed-month-summary", a);
    if (!box) return;

    var st = getState(a);
    box.textContent = "Loading month…";

    deriveViewedMonthRange(a).then(function (range) {
      box.innerHTML = htmlViewedMonthBox(st.y, st.m, range.startIso, range.endIso);
      upsertViewedMonthBadges(a);
    }).catch(function () {
      box.innerHTML = htmlViewedMonthBox(st.y, st.m, "Unavailable", "Unavailable");
      upsertViewedMonthBadges(a);
    });
  }
  
    function deriveViewedYearRange(a) {
    var st = getState(a);

    return Promise.all([
      fetchYearStart(a, st.y),
      fetchYearStart(a, st.y + 1)
    ]).then(function (res) {
      var ys = res[0];
      var ysNext = res[1];

      var startDt = parseYmdToUtcDate(ys || "");
      if (!startDt) {
        return { startIso: "Unavailable", endIso: "Unavailable" };
      }

      var endIso = "Unavailable";
      var nextDt = parseYmdToUtcDate(ysNext || "");
      if (nextDt) {
        endIso = utcDateToYmd(new Date(nextDt.getTime() - ONE_DAY_MS));
      }

      return {
        startIso: utcDateToYmd(startDt),
        endIso: endIso
      };
    }).catch(function () {
      return { startIso: "Unavailable", endIso: "Unavailable" };
    });
  }

  function updateYearSummary(a) {
    var box = qs("#igcal-viewed-year-summary", a);
    if (!box) return;
    var st = getState(a);
    box.innerHTML = htmlViewedYearBox(st.y, st.m);
    upsertViewedYearFacts(a);
  }

  function updateRangeSummary(a) {
    var st = getState(a);
    var box = qs("#igcal-year-range-summary", a);
    if (!box) return;

    Promise.all([fetchYearStart(a, st.y), fetchYearStart(a, st.y + 1)]).then(function (res) {
      box.innerHTML = htmlRangeBox(st.y, res[0], res[1]);
      upsertYearRangeFacts(a);
    }).catch(function () {
      box.textContent = "Year range unavailable.";
      upsertYearRangeFacts(a);
    });
  }

  function syncSelectors(a) {
    var st = getState(a);
    var monthSel = qs("#igcal-month-select", a);
    var yearSel = qs("#igcal-year-select", a);

    if (monthSel) monthSel.value = String(st.m);
    if (yearSel) yearSel.value = String(st.y);
  }

  function scrollIntoViewSafe(el) {
    if (!el || !el.scrollIntoView) return;
    try {
      el.scrollIntoView({ behavior: "smooth", block: "center" });
    } catch (_) {}
  }

  function syncVisibleMonth(a, preferredCell) {
    var st = getState(a);
    showOnlyMonth(a, st.m);
    syncSelectors(a);
    updateMonthSummary(a);
    updateYearSummary(a);
    updateRangeSummary(a);
    var chosen = preferredCell || choosePreferredCell(a) || firstSelectableCellInVisibleMonth(a);
    return updateViewedDaySummary(a, chosen);
  }

  function choosePreferredCell(a) {
    var preferredIso = readPreferredIso();
    var monthEl = findVisibleMonthEl(a);
    if (!monthEl) return null;

    var cells = allDayCells(monthEl).map(function (cell) {
      var iso = getCellIso(cell);
      return { cell: cell, iso: iso, parts: parseIsoParts(iso) };
    }).filter(function (x) {
      return !!x.parts;
    });

    if (!cells.length) return null;
    if (!isIso(preferredIso)) return null;

    var pref = parseIsoParts(preferredIso);
    if (!pref) return null;

    var exact = cells.find(function (x) { return x.iso === preferredIso; });
    if (exact) return exact.cell;

    var sameDay = cells.find(function (x) { return x.parts.day === pref.day; });
    if (sameDay) return sameDay.cell;

    cells.sort(function (a, b) {
      return Math.abs(a.parts.day - pref.day) - Math.abs(b.parts.day - pref.day);
    });

    return cells[0] ? cells[0].cell : null;
  }

  function moveViewedDay(a, delta) {
    var selected = getSelectedViewedCell(a);
    if (!selected) {
      syncVisibleMonth(a);
      return;
    }

    var iso = getCellIso(selected);
    var dt = parseYmdToUtcDate(iso);
    if (!dt) {
      syncVisibleMonth(a);
      return;
    }

    var targetIso = utcDateToYmd(new Date(dt.getTime() + (delta * ONE_DAY_MS)));
    var targetCell = findCellByIsoInApp(a, targetIso);
    if (!targetCell) {
      syncVisibleMonth(a);
      return;
    }

    var targetYear = clampInt(targetCell.getAttribute("data-year"), 1, 9999, getState(a).y);
    var targetMonth = clampInt(targetCell.getAttribute("data-month"), 1, 13, getState(a).m);

    setState(a, targetYear, targetMonth);
    scrollIntoViewSafe(syncVisibleMonth(a, targetCell));
  }

  function goToRealToday(a) {
    deriveCurrentIgboData(a).then(function (currentInfo) {
      if (!currentInfo) {
        syncVisibleMonth(a);
        return;
      }

      var targetYear = clampInt(currentInfo.igboYear, 1, 9999, getState(a).y);
      var targetMonth = clampInt(currentInfo.igboMonth, 1, 13, 1);
      var todayIso = String(currentInfo.todayIso || a.getAttribute("data-today-iso") || "");
      var currentState = getState(a);

      if (currentState.y !== targetYear && navigator.onLine) {
        hardNav(targetYear, targetMonth);
        return;
      }

      setState(a, targetYear, targetMonth);
      scrollIntoViewSafe(syncVisibleMonth(a, findCellByIsoInApp(a, todayIso)));
    }).catch(function () {
      syncVisibleMonth(a);
    });
  }

  function goToRealCurrentMonth(a) {
    deriveCurrentIgboData(a).then(function (currentInfo) {
      if (!currentInfo) {
        syncVisibleMonth(a);
        return;
      }

      var targetYear = clampInt(currentInfo.igboYear, 1, 9999, getState(a).y);
      var targetMonth = clampInt(currentInfo.igboMonth, 1, 13, 1);
      var currentState = getState(a);

      if (currentState.y !== targetYear && navigator.onLine) {
        hardNav(targetYear, targetMonth);
        return;
      }

      setState(a, targetYear, targetMonth);
      scrollIntoViewSafe(syncVisibleMonth(a));
    }).catch(function () {
      syncVisibleMonth(a);
    });
  }

  function navWithinRenderedYear(a, targetYear, targetMonth, preferredIso) {
    var current = getState(a);
    if (current.y !== targetYear && navigator.onLine) {
      hardNav(targetYear, targetMonth);
      return;
    }

    setState(a, targetYear, targetMonth);
    var preferredCell = preferredIso ? findCellByIsoInApp(a, preferredIso) : null;
    scrollIntoViewSafe(syncVisibleMonth(a, preferredCell));
  }

  function populateRanges(a) {
    var sel = qs("#igcal-gregorian-range", a);
    if (!sel) return;

    var st = getState(a);
    var center = st.y;
    var fromY = Math.max(1, center - 30);
    var toY = Math.min(9999, center + 30);

    sel.innerHTML = "";
    var optMap = Object.create(null);

    for (var y = fromY; y <= toY; y++) {
      var o = document.createElement("option");
      o.value = String(y);
      o.textContent = y + " — Loading…";
      sel.appendChild(o);
      optMap[y] = o;
    }

    sel.value = String(center);

    if (sel.dataset.igBound !== "1") {
      sel.dataset.igBound = "1";
      sel.addEventListener("change", function () {
        var y2 = clampInt(sel.value, 1, 9999, center);
        setState(a, y2, 1);

        if (!navigator.onLine) {
          syncVisibleMonth(a);
          return;
        }
        hardNav(y2, 1);
      });
    }

    function setLabel(y, ys, ysNext) {
      var o = optMap[y];
      if (!o) return;

      if (!ys) {
        o.textContent = y + " — Unavailable";
        return;
      }

      var label = "Starts: " + ys;
      if (ysNext) {
        var nextDt = parseYmdToUtcDate(ysNext);
        if (nextDt) {
          label = "Starts: " + ys + " → Ends: " + utcDateToYmd(new Date(nextDt.getTime() - ONE_DAY_MS));
        }
      }
      o.textContent = y + " — " + label;
    }

    function fillOne(y) {
      return Promise.all([fetchYearStart(a, y), fetchYearStart(a, y + 1)]).then(function (res) {
        setLabel(y, res[0], res[1]);
      });
    }

    fillOne(center);

    var queue = [];
    for (var yy = fromY; yy <= toY; yy++) {
      if (yy !== center) queue.push(yy);
    }

    (function batch() {
      if (!queue.length) return;
      var b = queue.splice(0, 6);
      Promise.all(b.map(fillOne)).then(function () {
        setTimeout(batch, 35);
      }).catch(function () {
        setTimeout(batch, 120);
      });
    })();
  }

  function bindClickOnce(el, type, key, handler) {
    if (!el) return;
    var attr = "data-ig-bound-" + key;
    if (el.getAttribute(attr) === "1") return;
    el.setAttribute(attr, "1");
    el.addEventListener(type, handler);
  }

  function bindDayClicks(a) {
    qsa(".mk-cal-cell__button", a).forEach(function (btn) {
      bindClickOnce(btn, "click", "day", function (e) {
        e.preventDefault();
        var cell = btn.closest(".mk-cal-cell");
        if (cell) updateViewedDaySummary(a, cell);
      });
    });

    allDayCells(a).forEach(function (cell) {
      if (!cell.hasAttribute("tabindex")) {
        cell.setAttribute("tabindex", "0");
      }

      bindClickOnce(cell, "click", "cell-select", function () {
        updateViewedDaySummary(a, cell);
      });

      bindClickOnce(cell, "keydown", "cell-key", function (e) {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          getCellButton(cell).click();
        }
      });
    });
  }

  function bindYearAndRangeActions(a) {
    [
      "#igcal-year-copy",
      "#igcal-year-share",
      "#igcal-year-print",
      "#igcal-range-copy",
      "#igcal-range-share",
      "#igcal-range-print"
    ].forEach(function (sel) {
      var el = qs(sel, a);
      if (el) el.setAttribute("type", "button");
    });

    bindClickOnce(qs("#igcal-year-copy", a), "click", "year-copy", function (e) {
      e.preventDefault();
      e.stopPropagation();
      copyPlain(buildYearPayload(a).text, "year");
    });

    bindClickOnce(qs("#igcal-year-share", a), "click", "year-share", function (e) {
      e.preventDefault();
      e.stopPropagation();
      sharePayload(buildYearPayload(a), "year");
    });

    bindClickOnce(qs("#igcal-year-print", a), "click", "year-print", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var payload = buildYearPayload(a);
      printPayload(payload.title, payload.text, "year");
    });

    bindClickOnce(qs("#igcal-range-copy", a), "click", "range-copy", function (e) {
      e.preventDefault();
      e.stopPropagation();
      copyPlain(buildRangePayload(a).text, "range");
    });

    bindClickOnce(qs("#igcal-range-share", a), "click", "range-share", function (e) {
      e.preventDefault();
      e.stopPropagation();
      sharePayload(buildRangePayload(a), "range");
    });

    bindClickOnce(qs("#igcal-range-print", a), "click", "range-print", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var payload = buildRangePayload(a);
      printPayload(payload.title, payload.text, "range");
    });
  }

  function bindYearNavButtons(a) {
    var prevYear = qs("#igcal-year-prev", a);
    var thisYear = qs("#igcal-year-current", a);
    var nextYear = qs("#igcal-year-next", a);

    if (prevYear) prevYear.setAttribute("type", "button");
    if (thisYear) thisYear.setAttribute("type", "button");
    if (nextYear) nextYear.setAttribute("type", "button");

    bindClickOnce(prevYear, "click", "year-prev", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var cur = getState(a);
      var y2 = Math.max(1, cur.y - 1);
      setState(a, y2, 1);

      if (!navigator.onLine || y2 === cur.y) {
        syncVisibleMonth(a);
        return;
      }

      hardNav(y2, 1);
    });

        bindClickOnce(thisYear, "click", "year-current", function (e) {
      e.preventDefault();
      e.stopPropagation();

      deriveCurrentIgboData(a).then(function (currentInfo) {
        var y2 = currentInfo && currentInfo.igboYear
          ? clampInt(currentInfo.igboYear, 1, 9999, getState(a).y)
          : clampInt((new Date()).getUTCFullYear(), 1, 9999, getState(a).y);

        setState(a, y2, 1);

        if (!navigator.onLine) {
          syncVisibleMonth(a);
          return;
        }

        hardNav(y2, 1);
      }).catch(function () {
        var y2 = clampInt((new Date()).getUTCFullYear(), 1, 9999, getState(a).y);
        setState(a, y2, 1);

        if (!navigator.onLine) {
          syncVisibleMonth(a);
          return;
        }

        hardNav(y2, 1);
      });
    });

    bindClickOnce(nextYear, "click", "year-next", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var cur = getState(a);
      var y2 = Math.min(9999, cur.y + 1);
      setState(a, y2, 1);

      if (!navigator.onLine || y2 === cur.y) {
        syncVisibleMonth(a);
        return;
      }

      hardNav(y2, 1);
    });
  }

  function chooseDefaultViewedCell(a, detail) {
    if (detail && detail.defaultIso) {
      var byIso = findCellByIsoInApp(a, detail.defaultIso);
      if (byIso) return byIso;
    }
    return choosePreferredCell(a) || firstSelectableCellInVisibleMonth(a);
  }

  function getRenderedIsoRange(a) {
    var cells = allDayCells(findVisibleMonthEl(a) || a).map(function (cell) {
      return getCellIso(cell);
    }).filter(isIso).sort();

    if (!cells.length) return null;
    return { start: cells[0], end: cells[cells.length - 1] };
  }

  function getRenderedMonthName(a) {
    var sel = qs("#igcal-month-select", a);
    if (sel && sel.selectedOptions && sel.selectedOptions[0]) {
      return text(sel.selectedOptions[0]).replace(/^\d+\s+—\s+/, "");
    }
    return "";
  }

  function getRenderedMonthNumber(a) {
    var sel = qs("#igcal-month-select", a);
    return sel && sel.value ? String(sel.value) : "";
  }

  function getRenderedYear(a) {
    var sel = qs("#igcal-year-select", a);
    return sel && sel.value ? String(sel.value) : "";
  }

  function buildBadge(label, value, detail, extraClass) {
    var cls = "igcal-mini-badge" + (extraClass ? " " + extraClass : "");
    var html = '<span class="' + cls + '">';
    if (label) html += '<span class="igcal-mini-badge__label">' + esc(label) + ":</span>";
    html += '<span class="igcal-mini-badge__value">' + esc(value) + "</span>";
    if (detail) html += '<span class="igcal-mini-badge__detail">' + esc(detail) + "</span>";
    html += "</span>";
    return html;
  }

  function upsertViewedMonthBadges(a) {
    var box = qs("#igcal-viewed-month-summary", a);
    var range = getRenderedIsoRange(a);
    if (!box || !range) return;

    var monthNo = getRenderedMonthNumber(a) || "";
    var monthNameLabel = getRenderedMonthName(a) || "Month";
    var monthLine = monthNo ? (monthNo + " - " + monthNameLabel) : monthNameLabel;
    var visibleDayCount = allDayCells(findVisibleMonthEl(a) || a).length;
    var monthDetailText = {
      "1": "New Year (Mbido Afọ)",
      "2": "Farm Clearing & Cleansing",
      "3": "Fasting & Offering",
      "4": "Planting Season",
      "5": "Masquerade Rites / Knowledge",
      "6": "Yam Festival / Rituals",
      "7": "New Yam Festival",
      "8": "Ancestral Veneration",
      "9": "Ofala Festival",
      "10": "Creation Myths / Offerings",
      "11": "Spiritual Cleansing",
      "12": "End-Year Offerings",
      "13": "Intercalary Rituals"
    }[monthNo] || "";

    var html = ""
      + '<div class="igcal-badge-row" data-ig-month-badges="1">'
      + buildBadge("", monthLine, monthDetailText, "igcal-mini-badge--month")
      + buildBadge("Days", String(visibleDayCount))
      + buildBadge("Start", formatIsoHuman(range.start))
      + buildBadge("End", formatIsoHuman(range.end))
      + "</div>";

    var old = qs('[data-ig-month-badges="1"]', box);
    if (old) old.outerHTML = html;
    else box.insertAdjacentHTML("beforeend", html);
  }

    function upsertViewedYearFacts(a) {
    var box = qs("#igcal-viewed-year-summary", a);
    if (!box) return;

    var year = getRenderedYear(a) || String(getState(a).y);

    deriveViewedYearRange(a).then(function (range) {
      var html = ""
        + '<div class="igcal-facts" data-ig-year-facts="1">'
        + '<div class="igcal-fact"><strong>Viewed Year:</strong> ' + esc(year) + "</div>"
        + '<div class="igcal-fact"><strong>Gregorian Start:</strong> ' + esc(formatIsoHuman(range.startIso)) + "</div>"
        + '<div class="igcal-fact"><strong>Gregorian End:</strong> ' + esc(formatIsoHuman(range.endIso)) + "</div>"
        + '<div class="igcal-fact"><strong>Total Igbo Months:</strong> 13</div>'
        + "</div>";

      var old = qs('[data-ig-year-facts="1"]', box);
      if (old) old.outerHTML = html;
      else box.insertAdjacentHTML("beforeend", html);
    }).catch(function () {
      var html = ""
        + '<div class="igcal-facts" data-ig-year-facts="1">'
        + '<div class="igcal-fact"><strong>Viewed Year:</strong> ' + esc(year) + "</div>"
        + '<div class="igcal-fact"><strong>Gregorian Start:</strong> Unavailable</div>'
        + '<div class="igcal-fact"><strong>Gregorian End:</strong> Unavailable</div>'
        + '<div class="igcal-fact"><strong>Total Igbo Months:</strong> 13</div>'
        + "</div>";

      var old = qs('[data-ig-year-facts="1"]', box);
      if (old) old.outerHTML = html;
      else box.insertAdjacentHTML("beforeend", html);
    });
  }

    function upsertYearRangeFacts(a) {
    var box = qs("#igcal-year-range-summary", a);
    if (!box) return;

    var year = getRenderedYear(a) || String(getState(a).y);

    deriveViewedYearRange(a).then(function (range) {
      var html = ""
        + '<div class="igcal-facts" data-ig-range-facts="1">'
        + '<div class="igcal-fact"><strong>Viewed Year:</strong> ' + esc(year) + "</div>"
        + '<div class="igcal-fact"><strong>Gregorian Start:</strong> ' + esc(formatIsoHuman(range.startIso)) + "</div>"
        + '<div class="igcal-fact"><strong>Gregorian End:</strong> ' + esc(formatIsoHuman(range.endIso)) + "</div>"
        + '<div class="igcal-fact"><strong>Coverage:</strong> Full Igbo year span</div>'
        + "</div>";

      var old = qs('[data-ig-range-facts="1"]', box);
      if (old) old.outerHTML = html;
      else box.insertAdjacentHTML("beforeend", html);
    }).catch(function () {
      var html = ""
        + '<div class="igcal-facts" data-ig-range-facts="1">'
        + '<div class="igcal-fact"><strong>Viewed Year:</strong> ' + esc(year) + "</div>"
        + '<div class="igcal-fact"><strong>Gregorian Start:</strong> Unavailable</div>'
        + '<div class="igcal-fact"><strong>Gregorian End:</strong> Unavailable</div>'
        + '<div class="igcal-fact"><strong>Coverage:</strong> Full Igbo year span</div>'
        + "</div>";

      var old = qs('[data-ig-range-facts="1"]', box);
      if (old) old.outerHTML = html;
      else box.insertAdjacentHTML("beforeend", html);
    });
  }

  function upsertCurrentGregorianRange(a) {
    var box = qs("#igcal-current-summary", a);
    var range = getRenderedIsoRange(a);
    if (!box || !range) return;

    var line = qs('[data-gregorian-month-range="1"]', box);
    if (!line) {
      line = document.createElement("div");
      line.className = "igcal-summary-line";
      line.setAttribute("data-gregorian-month-range", "1");
      box.appendChild(line);
    }

    line.innerHTML = "<strong>Gregorian Month Range:</strong> "
      + esc(formatIsoHuman(range.start))
      + " — "
      + esc(formatIsoHuman(range.end));
  }

  function keepPanelHeightsStable(a) {
    [
      qs("#igcal-current-summary", a),
      qs("#igcal-viewed-month-summary", a),
      qs("#igcal-viewed-year-summary", a),
      qs("#igcal-year-range-summary", a)
    ].forEach(function (box) {
      if (box && !box.style.minHeight) {
        box.style.minHeight = "110px";
      }
    });
  }

  function refreshDayCellStateClasses(a) {
    allDayCells(a).forEach(function (c) {
      c.classList.remove("igcal-is-today-only", "igcal-is-selected-only", "igcal-is-today-and-selected");
      var isToday = c.classList.contains("mk-cal-cell--today") || c.classList.contains("is-today");
      var isSelected = c.classList.contains("is-selected");

      if (isToday && isSelected) c.classList.add("igcal-is-today-and-selected");
      else if (isToday) c.classList.add("igcal-is-today-only");
      else if (isSelected) c.classList.add("igcal-is-selected-only");
    });
  }

  function runRefinementPass(a) {
    bindDayClicks(a);
    if (!qs(".mk-cal-cell.is-selected", a)) {
      var preferred = choosePreferredCell(a) || firstSelectableCellInVisibleMonth(a);
      if (preferred) updateViewedDaySummary(a, preferred);
    } else {
      refreshDayCellStateClasses(a);
    }
    upsertCurrentGregorianRange(a);
    upsertViewedMonthBadges(a);
    upsertViewedYearFacts(a);
    upsertYearRangeFacts(a);
    keepPanelHeightsStable(a);
  }

  function bindPersistenceControls(a) {
    [
      "#igcal-prev-day",
      "#igcal-next-day",
      "#igcal-today-day",
      "#igcal-prev-month",
      "#igcal-this-month",
      "#igcal-next-month",
      "#igcal-apply-year",
      "#igcal-year-prev",
      "#igcal-year-current",
      "#igcal-year-next",
      "#igcal-month-select",
      "#igcal-year-select"
    ].forEach(function (sel) {
      var el = qs(sel, a);
      if (!el || el.dataset.igBoundPersist === "1") return;

      el.dataset.igBoundPersist = "1";

      var handler = function () {
        var selected = qs(".mk-cal-cell.is-selected", a);
        var iso = getCellIso(selected);
        if (iso) rememberPreferredIso(iso);
      };

      el.addEventListener("click", handler);
      el.addEventListener("change", handler);
    });
  }

  function copyPlain(value, statusKey) {
    var v = String(value || "").trim();
    if (!v) return Promise.resolve(false);

    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(v).then(function () {
        setShareStatus(statusKey, "Copied successfully.");
        return true;
      }).catch(function () {
        return Promise.resolve(legacyCopy(v, statusKey));
      });
    }

    return Promise.resolve(legacyCopy(v, statusKey));
  }

  function legacyCopy(value, statusKey) {
    try {
      var ta = document.createElement("textarea");
      ta.value = value;
      ta.setAttribute("readonly", "readonly");
      ta.style.position = "fixed";
      ta.style.left = "-9999px";
      ta.style.top = "0";
      document.body.appendChild(ta);
      ta.select();
      var ok = document.execCommand("copy");
      document.body.removeChild(ta);
      setShareStatus(statusKey, ok ? "Copied successfully." : "Copy failed.");
      return ok;
    } catch (_) {
      setShareStatus(statusKey, "Copy failed.");
      return false;
    }
  }

  function sharePayload(payload, statusKey) {
    if (navigator.share) {
      return navigator.share({
        title: payload.title,
        text: payload.text,
        url: payload.url || window.location.href
      }).then(function () {
        setShareStatus(statusKey, "Shared successfully.");
        return true;
      }).catch(function () {
        return copyPlain(payload.text, statusKey).then(function (ok) {
          if (ok) setShareStatus(statusKey, "Share not available here. Copied instead.");
          return ok;
        });
      });
    }

    return copyPlain(payload.text, statusKey).then(function (ok) {
      if (ok) setShareStatus(statusKey, "Share not available here. Copied instead.");
      return ok;
    });
  }

  function printPayload(title, content, statusKey) {
    var w = window.open("", "_blank", "width=860,height=700");
    if (!w) {
      setShareStatus(statusKey, "Print could not open.");
      return false;
    }

    var css = ""
      + "body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:28px;color:#222;}"
      + "h1{font-size:24px;margin:0 0 12px;color:#6f4724;}"
      + ".meta{color:#555;margin-bottom:16px;}"
      + ".card{border:1px solid #d8c7aa;border-radius:14px;padding:18px;background:#fffaf2;line-height:1.55;white-space:pre-wrap;}";

    w.document.open();
    w.document.write(
      "<!doctype html><html><head><meta charset=\"utf-8\"><title>" + esc(title) + "</title><style>" + css + "</style></head><body>"
      + "<h1>" + esc(title) + "</h1>"
      + "<div class=\"meta\">Printed from Igbo Calendar • " + esc(window.location.href) + "</div>"
      + "<div class=\"card\">" + esc(content) + "</div>"
      + "</body></html>"
    );
    w.document.close();
    w.focus();
    setTimeout(function () {
      try { w.print(); } catch (_) {}
    }, 200);

    setShareStatus(statusKey, "Print dialog opened.");
    return true;
  }

  function getMonthNameFromUI(a) {
    return getRenderedMonthName(a);
  }

  function getYearValueFromUI(a) {
    return getRenderedYear(a);
  }

  function getViewedDayText(a) {
    return text(qs("#igcal-viewed-day-summary", a));
  }

  function getViewedMonthText(a) {
    return text(qs("#igcal-viewed-month-summary", a));
  }

  function getViewedYearText(a) {
    var t = text(qs("#igcal-viewed-year-summary", a));
    if (t) return t;
    var y = getYearValueFromUI(a);
    return "Viewed Year\nYear: " + (y || "—");
  }

  function getYearRangeText(a) {
    var t = text(qs("#igcal-year-range-summary", a));
    return t || "Year Start / End";
  }

  function buildDayPayload(a) {
    var activeCell = qs(".mk-cal-cell.is-selected", a) || qs(".mk-cal-cell--today, .mk-cal-cell.is-today", a);
    var iso = getCellIso(activeCell);
    var summary = getViewedDayText(a);
    var monthNameLabel = getMonthNameFromUI(a);
    var year = getYearValueFromUI(a);
    var lines = ["Igbo Calendar — Viewed Day", summary || "Viewed day summary unavailable."];

    if (iso) lines.push("Gregorian ISO: " + iso);
    if (monthNameLabel || year) lines.push("Context: " + [monthNameLabel, year].filter(Boolean).join(" • "));
    lines.push("URL: " + window.location.href);

    return {
      title: "Igbo Calendar — Viewed Day",
      text: lines.join("\n"),
      url: window.location.href
    };
  }

  function buildMonthPayload(a) {
    var summary = getViewedMonthText(a);
    var monthNameLabel = getMonthNameFromUI(a);
    var year = getYearValueFromUI(a);
    var lines = ["Igbo Calendar — Viewed Month", summary || "Viewed month summary unavailable."];

    if (monthNameLabel || year) lines.push("Context: " + [monthNameLabel, year].filter(Boolean).join(" • "));
    lines.push("URL: " + window.location.href);

    return {
      title: "Igbo Calendar — Viewed Month",
      text: lines.join("\n"),
      url: window.location.href
    };
  }

  function buildYearPayload(a) {
    var year = getYearValueFromUI(a);
    var summary = getViewedYearText(a);
    var lines = ["Igbo Calendar — Viewed Year", summary || "Viewed year summary unavailable."];

    if (year) lines.push("Year: " + year);
    lines.push("URL: " + window.location.href);

    return {
      title: "Igbo Calendar — Viewed Year",
      text: lines.join("\n"),
      url: window.location.href
    };
  }

  function buildRangePayload(a) {
    var year = getYearValueFromUI(a);
    var summary = getYearRangeText(a);
    var lines = ["Igbo Calendar — Year Start / End", summary || "Year range unavailable."];

    if (year) lines.push("Viewed Year: " + year);
    lines.push("URL: " + window.location.href);

    return {
      title: "Igbo Calendar — Year Start / End",
      text: lines.join("\n"),
      url: window.location.href
    };
  }

  function setShareStatus(kind, msg) {
    var el = qs('[data-ig-share-status="' + kind + '"]');
    if (el) el.textContent = msg || "";
  }

  function ensureActionBar(targetSelector, kind, a) {
    var target = qs(targetSelector, a);
    if (!target || !target.parentNode) return null;

    var existing = qs('[data-ig-share-bar="' + kind + '"]', target.parentNode);
    if (existing) return existing;

    var bar = document.createElement("div");
    bar.className = "igcal-sharebar";
    bar.setAttribute("data-ig-share-bar", kind);

    if (kind === "day") {
      bar.innerHTML = ""
        + '<button type="button" class="igcal-sharebar__btn" data-ig-copy-day="1">Copy Viewed Day</button>'
        + '<button type="button" class="igcal-sharebar__btn" data-ig-share-day="1">Share Viewed Day</button>'
        + '<button type="button" class="igcal-sharebar__btn" data-ig-print-day="1">Print Day</button>'
        + '<div class="igcal-sharebar__status" data-ig-share-status="day" aria-live="polite"></div>';
    } else if (kind === "month") {
      bar.innerHTML = ""
        + '<button type="button" class="igcal-sharebar__btn" data-ig-copy-month="1">Copy Viewed Month</button>'
        + '<button type="button" class="igcal-sharebar__btn" data-ig-share-month="1">Share Viewed Month</button>'
        + '<button type="button" class="igcal-sharebar__btn" data-ig-print-month="1">Print Month</button>'
        + '<div class="igcal-sharebar__status" data-ig-share-status="month" aria-live="polite"></div>';
    } else {
      return null;
    }

    target.insertAdjacentElement("afterend", bar);
    return bar;
  }

  function bindShareButtons(a) {
    var dayBar = ensureActionBar("#igcal-viewed-day-summary", "day", a);
    var monthBar = ensureActionBar("#igcal-viewed-month-summary", "month", a);

    bindClickOnce(qs('[data-ig-copy-day="1"]', dayBar || a), "click", "copy-day", function () {
      copyPlain(buildDayPayload(a).text, "day");
    });

    bindClickOnce(qs('[data-ig-share-day="1"]', dayBar || a), "click", "share-day", function () {
      sharePayload(buildDayPayload(a), "day");
    });

    bindClickOnce(qs('[data-ig-print-day="1"]', dayBar || a), "click", "print-day", function () {
      var payload = buildDayPayload(a);
      printPayload(payload.title, payload.text, "day");
    });

    bindClickOnce(qs('[data-ig-copy-month="1"]', monthBar || a), "click", "copy-month", function () {
      copyPlain(buildMonthPayload(a).text, "month");
    });

    bindClickOnce(qs('[data-ig-share-month="1"]', monthBar || a), "click", "share-month", function () {
      sharePayload(buildMonthPayload(a), "month");
    });

    bindClickOnce(qs('[data-ig-print-month="1"]', monthBar || a), "click", "print-month", function () {
      var payload = buildMonthPayload(a);
      printPayload(payload.title, payload.text, "month");
    });
  }

  function bindMainControls(a) {
    var prevDay = qs("#igcal-prev-day", a);
    var nextDay = qs("#igcal-next-day", a);
    var todayDay = qs("#igcal-today-day", a);

    var prevMonth = qs("#igcal-prev-month", a);
    var nextMonth = qs("#igcal-next-month", a);
    var thisMonth = qs("#igcal-this-month", a);

    var monthSel = qs("#igcal-month-select", a);
    var yearSel = qs("#igcal-year-select", a);
    var yearBtn = qs("#igcal-apply-year", a);

    syncSelectors(a);

    bindClickOnce(prevDay, "click", "prev-day", function (e) {
      e.preventDefault();
      moveViewedDay(a, -1);
    });

    bindClickOnce(nextDay, "click", "next-day", function (e) {
      e.preventDefault();
      moveViewedDay(a, 1);
    });

    bindClickOnce(todayDay, "click", "today-day", function (e) {
      e.preventDefault();
      goToRealToday(a);
    });

    function navMonth(delta) {
      var cur = getState(a);
      var y2 = cur.y;
      var m2 = cur.m + delta;

      if (m2 < 1) {
        m2 = 13;
        y2 = Math.max(1, cur.y - 1);
      } else if (m2 > 13) {
        m2 = 1;
        y2 = Math.min(9999, cur.y + 1);
      }

      navWithinRenderedYear(a, y2, m2, "");
    }

    bindClickOnce(prevMonth, "click", "prev-month", function (e) {
      e.preventDefault();
      navMonth(-1);
    });

    bindClickOnce(nextMonth, "click", "next-month", function (e) {
      e.preventDefault();
      navMonth(1);
    });

    bindClickOnce(thisMonth, "click", "this-month", function (e) {
      e.preventDefault();
      goToRealCurrentMonth(a);
    });

    bindClickOnce(yearBtn, "click", "apply-year", function (e) {
      e.preventDefault();
      var cur = getState(a);
      var y2 = clampInt(yearSel ? yearSel.value : cur.y, 1, 9999, cur.y);
      setState(a, y2, 1);

      if (!navigator.onLine || y2 === cur.y) {
        syncVisibleMonth(a);
        return;
      }

      hardNav(y2, 1);
    });

    bindClickOnce(yearSel, "keydown", "year-enter", function (e) {
      if (e.key !== "Enter") return;
      e.preventDefault();

      var cur = getState(a);
      var y2 = clampInt(yearSel.value, 1, 9999, cur.y);
      setState(a, y2, 1);

      if (!navigator.onLine || y2 === cur.y) {
        syncVisibleMonth(a);
        return;
      }

      hardNav(y2, 1);
    });

    bindClickOnce(monthSel, "change", "month-change", function () {
      var cur = getState(a);
      var m2 = clampInt(monthSel.value, 1, 13, cur.m);
      setState(a, cur.y, m2);
      syncVisibleMonth(a);
    });

    bindPersistenceControls(a);
    bindShareButtons(a);
    bindYearAndRangeActions(a);
    bindYearNavButtons(a);
  }

  function bindMutationObserver(a) {
    var mount = qs("#igcal-mount", a);
    if (!mount || mount.dataset.igObserverBound === "1" || !window.MutationObserver) return;

    mount.dataset.igObserverBound = "1";
    var obs = new MutationObserver(function () {
      bindDayClicks(a);
      bindPersistenceControls(a);
      bindShareButtons(a);
      bindYearAndRangeActions(a);
      bindYearNavButtons(a);
      runRefinementPass(a);
    });

    obs.observe(mount, { childList: true, subtree: true });
  }

  function bindInitial(a) {
    populateRanges(a);
    bindMainControls(a);
    updateMonthSummary(a);
    updateYearSummary(a);
    updateRangeSummary(a);
    updateCurrentSummary(a);
    setLoading(a, true);
    bindMutationObserver(a);
  }

  function onRendered(ev) {
    var a = app();
    if (!a) return;

    setLoading(a, false);
    showOnlyMonth(a, clampInt(getState(a).m, 1, 13, 1));
    bindDayClicks(a);
    bindPersistenceControls(a);
    bindShareButtons(a);
    bindYearAndRangeActions(a);
    bindYearNavButtons(a);
    updateCurrentSummary(a);
    updateMonthSummary(a);
    updateYearSummary(a);
    updateRangeSummary(a);

    var detail = ev && ev.detail ? ev.detail : null;
    updateViewedDaySummary(a, chooseDefaultViewedCell(a, detail));
    syncSelectors(a);
    runRefinementPass(a);
  }

  function boot() {
    var a = app();
    if (!a) return;

    bindInitial(a);
    runRefinementPass(a);
    setTimeout(function () { runRefinementPass(a); }, 100);
    setTimeout(function () { runRefinementPass(a); }, 350);
    setTimeout(function () { runRefinementPass(a); }, 800);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot, { once: true });
  } else {
    boot();
  }

  window.addEventListener("igcal:rendered", onRendered);
  window.addEventListener("load", function () {
    var a = app();
    if (!a) return;
    setTimeout(function () {
      bindShareButtons(a);
      bindYearAndRangeActions(a);
      bindYearNavButtons(a);
      runRefinementPass(a);
    }, 120);
  });
})();
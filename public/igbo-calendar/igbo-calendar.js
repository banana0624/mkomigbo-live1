/* /public/igbo-calendar/igbo-calendar.js */
(function () {
  "use strict";

  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function getAppRoot(fromEl) {
    if (fromEl && fromEl.closest) {
      return fromEl.closest('[data-app="igbo-calendar"]') || qs('[data-app="igbo-calendar"]');
    }
    return qs('[data-app="igbo-calendar"]');
  }

  function normAction(v) {
    v = String(v || '').trim().toLowerCase();
    if (v === 'current' || v === 'today' || v === 'current_month' || v === 'currentmonth') return 'current-month';
    if (v === 'prev' || v === 'previous' || v === 'previous-month' || v === 'prevmonth') return 'prev-month';
    if (v === 'next' || v === 'nextmonth') return 'next-month';
    if (v === '4weeks' || v === 'four-weeks' || v === 'toggle4weeks' || v === 'toggle_4weeks') return 'toggle-4weeks';
    return v;
  }

  function readState(app) {
    var todayIso = app.getAttribute('data-today-iso') || '';

    var selectedMonth = parseInt(app.getAttribute('data-selected-month') || '1', 10);
    if (!(selectedMonth >= 1 && selectedMonth <= 13)) selectedMonth = 1;

    var yearStartIso = app.getAttribute('data-year-start') || '';
    var prevYearUrl = app.getAttribute('data-prev-year-url') || '/igbo-calendar/';
    var nextYearUrl = app.getAttribute('data-next-year-url') || '/igbo-calendar/';
    var homeUrl = app.getAttribute('data-home-url') || '/igbo-calendar/';

    return {
      todayIso: todayIso,
      selectedMonth: selectedMonth,
      yearStartIso: yearStartIso,
      prevYearUrl: prevYearUrl,
      nextYearUrl: nextYearUrl,
      homeUrl: homeUrl
    };
  }

  function allMonthEls(app) {
  // Attribute-first, markup-agnostic:
  // - supports .igcal-month and/or .mk-cal-month
  // - supports any container/DOM structure
  var m = qsa('[data-month],[data-ig-month],[data-month-index]', app);
  if (m.length) return m;

  // Hard fallback for legacy markup (should become unnecessary once render is updated everywhere)
  m = qsa('.igcal-month, .mk-cal-month', app);
  return m;
}

  function getMonthEl(app, n) {
  n = parseInt(n, 10);
  if (!(n >= 1 && n <= 13)) return null;

  // Prefer explicit attributes (new contract)
  var sel =
    '[data-month="' + n + '"], ' +
    '[data-ig-month="' + n + '"], ' +
    '[data-month-index="' + n + '"]';

  // If multiple match (shouldn’t), prefer the one that looks like a month wrapper
  var candidates = qsa(sel, app);
  if (candidates.length) {
    // Prefer elements that have either class (most stable month wrapper intent)
    for (var i = 0; i < candidates.length; i++) {
      var el = candidates[i];
      if (el.classList && (el.classList.contains('igcal-month') || el.classList.contains('mk-cal-month'))) {
        return el;
      }
    }
    return candidates[0];
  }

  // Legacy fallback: class+attribute
  var found = qs('.igcal-month[data-ig-month="' + n + '"]', app);
  if (found) return found;

  // NOTE: we intentionally REMOVE the nth-card fallback:
  // var cards = qsa('.mk-cal-month', app); return cards[n - 1] || null;
  // Because it is not selector-stable.
  return null;
}

  function hideAllMonths(app) {
  allMonthEls(app).forEach(function (m) {
    // Use the native boolean hidden attribute only.
    // Do NOT force display:none; that can fight CSS transitions and "hidden" semantics.
    m.hidden = true;
    m.setAttribute('aria-hidden', 'true');
  });
}

  function scrollToIso(app, iso) {
    if (!iso) return;
    var target = qs('[data-iso="' + iso + '"]', app);
    if (target && target.scrollIntoView) {
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    var todayCell = qs('.mk-cal-cell--today', app);
    if (todayCell && todayCell.scrollIntoView) {
      todayCell.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  // -----------------------------
  // 4-week paging: two windows (rows 1-4 OR rows 5-8)
  // -----------------------------
  // mode: 0 = off (show all 8), 1 = show top half (rows 1-4), 2 = show bottom half (rows 5-8)
  var fourWeekMode = 0;

  function monthTableBodyRows(monthEl) {
    if (!monthEl) return [];
    // Use the first table inside the month card/section (stable)
    var table = qs('table', monthEl);
    if (!table) return [];

    var tbody = qs('tbody', table);
    if (!tbody) return [];

    return qsa('tr', tbody);
  }

  function clearFourWeekPaging(app) {
    allMonthEls(app).forEach(function (m) {
      m.classList.remove('is-4week');
      m.classList.remove('is-4week-top');
      m.classList.remove('is-4week-bottom');
      qsa('tbody tr.is-hidden', m).forEach(function (r) { r.classList.remove('is-hidden'); });
    });
  }

  function applyFourWeekPaging(app, state) {
    var monthEl = getMonthEl(app, state.selectedMonth);
    if (!monthEl) return;

    clearFourWeekPaging(app);

    if (fourWeekMode === 0) return;

    monthEl.classList.add('is-4week');
    if (fourWeekMode === 1) monthEl.classList.add('is-4week-top');
    if (fourWeekMode === 2) monthEl.classList.add('is-4week-bottom');

    var rows = monthTableBodyRows(monthEl);

    // Your contract: month has 8 rows (weeks). We toggle rows 0-3 vs 4-7.
    // If a month has fewer rows (rare), we degrade safely.
    rows.forEach(function (r, idx) {
      var keep = true;
      if (fourWeekMode === 1) keep = (idx <= 3);
      if (fourWeekMode === 2) keep = (idx >= 4 && idx <= 7);
      if (!keep) r.classList.add('is-hidden');
      else r.classList.remove('is-hidden');
    });

    // Keep view anchored to the top of the visible window
    // (prevents confusion when switching top<->bottom)
    if (monthEl.scrollIntoView) {
      monthEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function cycleFourWeeksMode(app, state, btn) {
    // Cycle: off -> top -> bottom -> off ...
    if (fourWeekMode === 0) fourWeekMode = 1;
    else if (fourWeekMode === 1) fourWeekMode = 2;
    else fourWeekMode = 0;

    // Update button aria + optional label if you want to show state
    if (btn) {
      btn.setAttribute('aria-pressed', fourWeekMode ? 'true' : 'false');
      // Optional: change text to show which window is active
      // Comment out if you want a static label.
      var base = 'Toggle 4 Weeks';
      if (fourWeekMode === 1) btn.textContent = '4 Weeks: Rows 1–4';
      else if (fourWeekMode === 2) btn.textContent = '4 Weeks: Rows 5–8';
      else btn.textContent = base;
    }

    applyFourWeekPaging(app, state);
  }

  // -----------------------------
  // Month switching
  // -----------------------------
  function showMonth(app, state, n, scrollTo) {
    n = parseInt(n, 10);
    if (!(n >= 1 && n <= 13)) n = 1;
    state.selectedMonth = n;

    // Reset 4-week window whenever month changes (per your requirement)
    // so users always start from a predictable view.
    fourWeekMode = 0;

    hideAllMonths(app);

    var el = getMonthEl(app, n);
    if (el) {
      el.hidden = false;
      el.setAttribute('aria-hidden', 'false');
    }

    // Persist month/year-start in URL without reload
    try {
      var u = new URL(window.location.href);
      u.searchParams.set('m', String(state.selectedMonth));
      if (state.yearStartIso) u.searchParams.set('ys', state.yearStartIso);
      window.history.replaceState({}, '', u.toString());
    } catch (e) {}

    if (scrollTo === 'today' && state.todayIso) {
      scrollToIso(app, state.todayIso);
    } else if (scrollTo === 'month') {
      if (el && el.scrollIntoView) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Ensure any hidden rows are cleared after switching
    clearFourWeekPaging(app);
  }

  function withMonth(url, m) {
    try {
      var u = new URL(url, window.location.origin);
      u.searchParams.set('m', String(m));
      return u.toString();
    } catch (e) {
      if (url.indexOf('?') === -1) return url + '?m=' + encodeURIComponent(String(m));
      return url + '&m=' + encodeURIComponent(String(m));
    }
  }

  function goPrevMonth(app, state) {
    var n = state.selectedMonth - 1;
    if (n >= 1) {
      showMonth(app, state, n, 'month');
      return;
    }
    window.location.href = withMonth(state.prevYearUrl, 13);
  }

  function goNextMonth(app, state) {
    var n = state.selectedMonth + 1;
    if (n <= 13) {
      showMonth(app, state, n, 'month');
      return;
    }
    window.location.href = withMonth(state.nextYearUrl, 1);
  }

  function goCurrent(app, state) {
    // Always jump to active Igbo year+month by dropping params.
    var base = String(state.homeUrl || '/igbo-calendar/');
    try {
      var u = new URL(base, window.location.origin);
      u.search = '';
      window.location.href = u.toString();
    } catch (e) {
      window.location.href = '/igbo-calendar/';
    }
  }

  // -----------------------------
  // Boot
  // -----------------------------
  var app0 = getAppRoot(null);
  if (!app0) return;

  var state0 = readState(app0);

  showMonth(app0, state0, state0.selectedMonth, 'month');

  if (state0.todayIso && qs('[data-iso="' + state0.todayIso + '"]', app0)) {
    scrollToIso(app0, state0.todayIso);
  }

  // Delegated clicks (robust even if controls move)
  document.addEventListener('click', function (ev) {
    var t = ev.target;
    if (!t || !t.closest) return;

    var btn = t.closest('[data-action]');
    if (!btn) return;

    var app = getAppRoot(btn);
    if (!app) return;

    var state = readState(app);
    var act = normAction(btn.getAttribute('data-action'));

    if (act === 'prev-month') { ev.preventDefault(); return goPrevMonth(app, state); }
    if (act === 'next-month') { ev.preventDefault(); return goNextMonth(app, state); }
    if (act === 'current-month') { ev.preventDefault(); return goCurrent(app, state); }

    if (act === 'toggle-4weeks') {
      ev.preventDefault();
      return cycleFourWeeksMode(app, state, btn);
    }
  });

  // Year select (strict: same origin + same pathname)
    document.addEventListener('change', function (ev) {
      var el = ev.target;
      if (!el) return;
      if (el.id !== 'igcalYearSelect') return;
    
      var app = getAppRoot(el);
      if (!app) return;
    
      var state = readState(app);
    
      var ys = String(el.value || '').trim();
      if (!ys) return;
    
      // Reset 4-week mode across year change
      fourWeekMode = 0;
    
      // Force same origin + same pathname (professional-grade predictability)
      // Use the current location pathname as the canonical endpoint.
      // (So even if someone changes data-urls, this still navigates correctly.)
      try {
        var u = new URL(window.location.href);
        u.search = '';     // wipe all params
        u.hash = '';       // wipe fragments
    
        // enforce our query contract only
        u.searchParams.set('ys', ys);
        u.searchParams.set('m', String(state.selectedMonth));
    
        window.location.href = u.toString();
      } catch (e) {
        // Absolute minimal fallback
        var path = (window.location && window.location.pathname) ? window.location.pathname : '/';
        window.location.href =
          path + '?ys=' + encodeURIComponent(ys) +
          '&m=' + encodeURIComponent(String(state.selectedMonth));
      }
    });



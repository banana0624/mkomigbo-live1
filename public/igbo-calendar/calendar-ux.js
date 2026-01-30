/* /public/igbo-calendar/calendar-ux.js
 * UX controller:
 * - Default to the month that contains "today" (via data-iso match)
 * - Show only 4 rows at a time: rows 1-4 or 5-8 depending where today lands
 * - Keep everything accessible (user can switch months / expand all)
 */
(() => {
  "use strict";

  const SEL = {
    month: ".igcal-month[data-ig-month]",
    row: ".igcal-row[data-ig-row]",
    day: ".igcal-day[data-iso]"
  };

  function qs(sel, root = document) { return root.querySelector(sel); }
  function qsa(sel, root = document) { return Array.from(root.querySelectorAll(sel)); }

  function todayIsoUTC() {
    // Use UTC ISO date to match your server-side UTC rendering
    const d = new Date();
    const y = d.getUTCFullYear();
    const m = String(d.getUTCMonth() + 1).padStart(2, "0");
    const day = String(d.getUTCDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
  }

  function show(el) { if (el) el.style.display = ""; }
  function hide(el) { if (el) el.style.display = "none"; }

  function setActiveMonth(activeMonthEl) {
    const months = qsa(SEL.month);
    months.forEach(m => (m === activeMonthEl ? show(m) : hide(m)));
  }

  function setRowWindow(monthEl, startRow, endRow) {
    const rows = qsa(SEL.row, monthEl);
    rows.forEach(r => {
      const n = parseInt(r.getAttribute("data-ig-row") || "0", 10);
      if (!n) { show(r); return; } // fail-open
      if (n >= startRow && n <= endRow) show(r);
      else hide(r);
    });
  }

  function clearRowWindow(monthEl) {
    qsa(SEL.row, monthEl).forEach(show);
  }

  function findMonthByTodayIso() {
    const iso = todayIsoUTC();
    const dayEl = qs(`${SEL.day}[data-iso="${iso}"]`);
    if (!dayEl) return null;
    return dayEl.closest(SEL.month);
  }

  function inferRowFromToday(monthEl) {
    const iso = todayIsoUTC();
    const dayEl = qs(`${SEL.day}[data-iso="${iso}"]`, monthEl);
    if (!dayEl) return null;

    const rowEl = dayEl.closest(SEL.row);
    if (!rowEl) return null;

    const n = parseInt(rowEl.getAttribute("data-ig-row") || "0", 10);
    return n || null;
  }

  function buildControls(hostEl) {
    // Minimal controls: Month selector + Expand/Collapse
    const months = qsa(SEL.month);
    if (!months.length) return;

    const bar = document.createElement("div");
    bar.className = "mk-card mk-card--soft igcal-controls";
    bar.style.marginTop = "12px";
    bar.style.padding = "10px";
    bar.style.display = "flex";
    bar.style.gap = "10px";
    bar.style.flexWrap = "wrap";
    bar.style.alignItems = "center";

    const sel = document.createElement("select");
    sel.className = "mk-input";
    sel.setAttribute("aria-label", "Select month");

    months.forEach(m => {
      const opt = document.createElement("option");
      opt.value = m.getAttribute("data-ig-month") || "";
      opt.textContent = m.getAttribute("aria-label") || ("Month " + opt.value);
      sel.appendChild(opt);
    });

    const btnCompact = document.createElement("button");
    btnCompact.className = "btn";
    btnCompact.type = "button";
    btnCompact.textContent = "Show current 4 rows";

    const btnAll = document.createElement("button");
    btnAll.className = "btn btn--ghost";
    btnAll.type = "button";
    btnAll.textContent = "Show full month";

    const btnYear = document.createElement("button");
    btnYear.className = "btn btn--ghost";
    btnYear.type = "button";
    btnYear.textContent = "Show all months";

    bar.appendChild(sel);
    bar.appendChild(btnCompact);
    bar.appendChild(btnAll);
    bar.appendChild(btnYear);

    hostEl.insertBefore(bar, hostEl.firstChild);

    return { sel, btnCompact, btnAll, btnYear, months };
  }

  function init() {
    const app = document.querySelector(".igbo-calendar-app");
    if (!app) return;

    const months = qsa(SEL.month);
    if (!months.length) return;

    const ui = buildControls(app);
    if (!ui) return;

    // Choose default month
    let active = findMonthByTodayIso() || months[0];
    setActiveMonth(active);
    ui.sel.value = active.getAttribute("data-ig-month") || ui.sel.value;

    // Default row window: rows 1-4 or 5-8 depending on today row
    const row = inferRowFromToday(active);
    if (row) {
      const start = (row <= 4) ? 1 : 5;
      setRowWindow(active, start, start + 3);
    } else {
      // fallback: show first 4 rows
      setRowWindow(active, 1, 4);
    }

    ui.sel.addEventListener("change", () => {
      const v = ui.sel.value;
      const next = months.find(m => (m.getAttribute("data-ig-month") || "") === v) || months[0];
      active = next;
      setActiveMonth(active);

      const r = inferRowFromToday(active);
      if (r) {
        const start = (r <= 4) ? 1 : 5;
        setRowWindow(active, start, start + 3);
      } else {
        setRowWindow(active, 1, 4);
      }
    });

    ui.btnCompact.addEventListener("click", () => {
      const r = inferRowFromToday(active);
      const start = r ? ((r <= 4) ? 1 : 5) : 1;
      setRowWindow(active, start, start + 3);
    });

    ui.btnAll.addEventListener("click", () => {
      clearRowWindow(active);
    });

    ui.btnYear.addEventListener("click", () => {
      months.forEach(show);
      // When showing all months, also show all rows
      months.forEach(clearRowWindow);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

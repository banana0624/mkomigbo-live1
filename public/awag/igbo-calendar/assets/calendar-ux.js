(function () {
  'use strict';

  if (window.__igcalUxBooted) return;
  window.__igcalUxBooted = true;

  function q(sel, root) {
    return (root || document).querySelector(sel);
  }

  function qa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function text(el) {
    return String((el && (el.innerText || el.textContent)) || '').trim();
  }

  function escHtml(s) {
    return String(s || '').replace(/[&<>]/g, function (m) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m];
    });
  }

  function cleanActionText(s) {
    return String(s || '')
      .replace(/\b(Copy|Share|Print|Previous Day|Today|Next Day|Previous Month|This Month|Next Month|Previous Year|This Year|Next Year)\b/g, '')
      .replace(/[ \t]+\n/g, '\n')
      .replace(/\n{3,}/g, '\n\n')
      .trim();
  }

  function copyText(value) {
    var textValue = String(value || '').trim();
    if (!textValue) return;

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(textValue).catch(function () {
        legacyCopy(textValue);
      });
      return;
    }
    legacyCopy(textValue);
  }

  function legacyCopy(value) {
    var ta = document.createElement('textarea');
    ta.value = value;
    ta.setAttribute('readonly', 'readonly');
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    ta.style.top = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
  }

  function shareOrCopy(value) {
    var textValue = String(value || '').trim();
    if (!textValue) return;

    if (navigator.share) {
      navigator.share({ text: textValue }).catch(function () {
        copyText(textValue);
      });
    } else {
      copyText(textValue);
    }
  }

  function printHtml(title, bodyHtml) {
    var win = window.open('', '_blank', 'width=960,height=760');
    if (!win) return;

    win.document.open();
    win.document.write(
      '<!doctype html><html><head><meta charset="utf-8">' +
      '<title>' + escHtml(title || 'Print') + '</title>' +
      '<style>' +
      'body{font:16px/1.55 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;padding:24px;color:#111;}' +
      '.mk-view-actions,.mk-view-nav,button{display:none!important;}' +
      'pre{white-space:pre-wrap;font:inherit;}' +
      '</style>' +
      '</head><body>' + bodyHtml + '</body></html>'
    );
    win.document.close();
    win.focus();
    setTimeout(function () {
      try { win.print(); } catch (e) {}
    }, 180);
  }

  function bindOnce(el, type, handler, key) {
    if (!el) return;
    var attr = 'data-mk-bound-' + key;
    if (el.getAttribute(attr) === '1') return;
    el.setAttribute(attr, '1');
    el.addEventListener(type, handler);
  }

  function currentYear() {
    var sel = q('#igcal-year-select');
    if (sel && sel.value) {
      var y = parseInt(sel.value, 10);
      if (!isNaN(y)) return y;
    }

    var current = q('#igcal-current-year-label');
    if (current) {
      var cy = parseInt(text(current), 10);
      if (!isNaN(cy)) return cy;
    }

    return new Date().getFullYear();
  }

  function currentMonthLabel() {
    var el = q('#igcal-current-month-label');
    return el ? text(el) : '';
  }

  function currentDayLabel() {
    var el = q('#igcal-current-day-label');
    return el ? text(el) : '';
  }

  function summaryText(selector, fallbackTitle, fallbackBody) {
    var box = q(selector);
    var t = box ? cleanActionText(text(box)) : '';
    if (t) return t;

    var parts = [];
    if (fallbackTitle) parts.push(fallbackTitle);
    if (fallbackBody) parts.push(fallbackBody);
    return parts.join('\n\n').trim();
  }

  function viewedDayText() {
    return summaryText('#igcal-viewed-day-summary', 'Viewed Day', currentDayLabel());
  }

  function viewedMonthText() {
    return summaryText('#igcal-viewed-month-summary', 'Viewed Month', currentMonthLabel());
  }

  function viewedYearText() {
    return summaryText('#igcal-viewed-year-summary', 'Viewed Year', 'Year: ' + currentYear());
  }

  function yearRangeText() {
    var explicit = summaryText('#igcal-year-range-summary', '', '');
    if (explicit) return explicit;

    var sel = q('#igcal-gregorian-range');
    if (sel && sel.options && sel.selectedIndex >= 0) {
      var opt = sel.options[sel.selectedIndex];
      var label = text(opt);
      if (label) return 'Year Start / End\n\n' + label;
    }

    return 'Year Start / End';
  }

  function setSelectValueByNumber(selectEl, target) {
    if (!selectEl) return false;
    var wanted = parseInt(target, 10);
    if (isNaN(wanted)) return false;

    for (var i = 0; i < selectEl.options.length; i++) {
      if (parseInt(selectEl.options[i].value, 10) === wanted) {
        selectEl.selectedIndex = i;
        try {
          selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (e) {}
        return true;
      }
    }
    return false;
  }

  function click(el) {
    if (!el) return false;
    el.click();
    return true;
  }

  function syncRangeToYear() {
    var year = currentYear();
    var sel = q('#igcal-gregorian-range');
    if (!sel) return;

    var targetIndex = -1;
    for (var i = 0; i < sel.options.length; i++) {
      var value = String(sel.options[i].value || '');
      var label = String(sel.options[i].textContent || '');
      if (value.indexOf(String(year)) !== -1 || label.indexOf(String(year)) !== -1) {
        targetIndex = i;
        break;
      }
    }

    if (targetIndex >= 0 && sel.selectedIndex !== targetIndex) {
      sel.selectedIndex = targetIndex;
      try {
        sel.dispatchEvent(new Event('change', { bubbles: true }));
      } catch (e) {}
    }
  }

  function applyYearDelta(delta) {
    var yearSel = q('#igcal-year-select');
    var goBtn = q('#igcal-apply-year');
    if (!yearSel) return;

    var y = currentYear() + delta;
    if (!setSelectValueByNumber(yearSel, y)) return;

    click(goBtn);
    setTimeout(syncRangeToYear, 100);
    setTimeout(syncRangeToYear, 350);
  }

  function jumpToCurrentYear() {
    var yearSel = q('#igcal-year-select');
    var goBtn = q('#igcal-apply-year');
    if (!yearSel) return;

    if (!setSelectValueByNumber(yearSel, new Date().getFullYear())) return;

    click(goBtn);
    setTimeout(syncRangeToYear, 100);
    setTimeout(syncRangeToYear, 350);
  }

  function applySelectAction(selectSelector, buttonSelector, delta) {
    var sel = q(selectSelector);
    var btn = q(buttonSelector);
    if (!sel) return;

    var nextIndex = sel.selectedIndex + delta;
    if (nextIndex < 0 || nextIndex >= sel.options.length) return;

    sel.selectedIndex = nextIndex;
    try {
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    } catch (e) {}

    click(btn);
  }

  function jumpSelectToToday(selectSelector, buttonSelector, todayMatcher) {
    var sel = q(selectSelector);
    var btn = q(buttonSelector);
    if (!sel) return;

    var targetIndex = -1;
    for (var i = 0; i < sel.options.length; i++) {
      if (todayMatcher(sel.options[i])) {
        targetIndex = i;
        break;
      }
    }

    if (targetIndex < 0) return;

    sel.selectedIndex = targetIndex;
    try {
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    } catch (e) {}

    click(btn);
  }

  function printTextBlock(title, value) {
    printHtml(title, '<pre>' + escHtml(String(value || '').trim()) + '</pre>');
  }

  function bindYearControls() {
    bindOnce(q('#igcal-year-copy'), 'click', function () {
      copyText(viewedYearText());
    }, 'year-copy');

    bindOnce(q('#igcal-year-share'), 'click', function () {
      shareOrCopy(viewedYearText());
    }, 'year-share');

    bindOnce(q('#igcal-year-print'), 'click', function () {
      printTextBlock('Viewed Year', viewedYearText());
    }, 'year-print');

    bindOnce(q('#igcal-range-copy'), 'click', function () {
      copyText(yearRangeText());
    }, 'range-copy');

    bindOnce(q('#igcal-range-share'), 'click', function () {
      shareOrCopy(yearRangeText());
    }, 'range-share');

    bindOnce(q('#igcal-range-print'), 'click', function () {
      printTextBlock('Year Start / End', yearRangeText());
    }, 'range-print');

    bindOnce(q('#igcal-year-prev'), 'click', function () {
      applyYearDelta(-1);
    }, 'year-prev');

    bindOnce(q('#igcal-year-current'), 'click', function () {
      jumpToCurrentYear();
    }, 'year-current');

    bindOnce(q('#igcal-year-next'), 'click', function () {
      applyYearDelta(1);
    }, 'year-next');

    var yearSel = q('#igcal-year-select');
    bindOnce(yearSel, 'change', function () {
      setTimeout(syncRangeToYear, 80);
      setTimeout(syncRangeToYear, 260);
    }, 'year-select-sync');
  }

  function ensureActionBar(card, key, textGetter, title) {
    if (!card) return;

    var existing = q('.mk-view-actions[data-mk-key="' + key + '"]', card);
    if (existing) return;

    var wrap = document.createElement('div');
    wrap.className = 'mk-view-actions';
    wrap.setAttribute('data-mk-key', key);

    function makeBtn(label, handler) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'mk-mini-btn';
      b.textContent = label;
      b.addEventListener('click', handler);
      return b;
    }

    wrap.appendChild(makeBtn('Copy', function () {
      copyText(textGetter());
    }));
    wrap.appendChild(makeBtn('Share', function () {
      shareOrCopy(textGetter());
    }));
    wrap.appendChild(makeBtn('Print', function () {
      printTextBlock(title, textGetter());
    }));

    var anchor = q('.igcal-panel-head', card) || card.firstElementChild || card.firstChild;
    if (anchor && anchor.parentNode === card) {
      if (anchor.nextSibling) card.insertBefore(wrap, anchor.nextSibling);
      else card.appendChild(wrap);
    } else {
      card.insertBefore(wrap, card.firstChild);
    }
  }

  function ensureNavBar(card, key, defs) {
    if (!card) return;

    var existing = q('.mk-view-nav[data-mk-key="' + key + '"]', card);
    if (existing) return;

    var wrap = document.createElement('div');
    wrap.className = 'mk-view-nav';
    wrap.setAttribute('data-mk-key', key);

    defs.forEach(function (def) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'mk-mini-btn';
      b.textContent = def.label;
      b.addEventListener('click', def.onClick);
      wrap.appendChild(b);
    });

    var actions = q('.mk-view-actions', card);
    if (actions && actions.parentNode === card) {
      if (actions.nextSibling) card.insertBefore(wrap, actions.nextSibling);
      else card.appendChild(wrap);
    } else {
      card.insertBefore(wrap, card.firstChild);
    }
  }

  function bindDayMonthEnhancements() {
    var viewedDayCard = q('[data-ig-viewed-day="1"]');
    var viewedMonthCard = q('[data-ig-viewed-month="1"]') || q('#igcal-viewed-month-summary') && q('#igcal-viewed-month-summary').closest('.igcal-card');

    if (viewedDayCard) {
      ensureActionBar(viewedDayCard, 'viewed-day-inline-clean', viewedDayText, 'Viewed Day');
    }

    if (viewedMonthCard) {
      ensureActionBar(viewedMonthCard, 'viewed-month-inline-clean', viewedMonthText, 'Viewed Month');
    }

    var dayPrev = q('#igcal-day-prev');
    var dayToday = q('#igcal-day-current');
    var dayNext = q('#igcal-day-next');
    if (!dayPrev && !dayToday && !dayNext && viewedDayCard) {
      var daySel = q('#igcal-day-select');
      var dayGo = q('#igcal-apply-day');
      if (daySel && dayGo) {
        ensureNavBar(viewedDayCard, 'viewed-day-nav-clean', [
          { label: 'Previous Day', onClick: function () { applySelectAction('#igcal-day-select', '#igcal-apply-day', -1); } },
          { label: 'Today', onClick: function () {
            var todayIso = (q('#igcal-app') && q('#igcal-app').getAttribute('data-today-iso')) || '';
            jumpSelectToToday('#igcal-day-select', '#igcal-apply-day', function (opt) {
              return String(opt.value || '') === todayIso || String(opt.textContent || '').indexOf(todayIso) !== -1;
            });
          } },
          { label: 'Next Day', onClick: function () { applySelectAction('#igcal-day-select', '#igcal-apply-day', 1); } }
        ]);
      }
    }

    var monthPrev = q('#igcal-month-prev');
    var monthCurrent = q('#igcal-month-current');
    var monthNext = q('#igcal-month-next');
    if (!monthPrev && !monthCurrent && !monthNext && viewedMonthCard) {
      var monthSel = q('#igcal-month-select');
      var monthGo = q('#igcal-apply-month');
      if (monthSel && monthGo) {
        ensureNavBar(viewedMonthCard, 'viewed-month-nav-clean', [
          { label: 'Previous Month', onClick: function () { applySelectAction('#igcal-month-select', '#igcal-apply-month', -1); } },
          { label: 'This Month', onClick: function () {
            var currentLabel = currentMonthLabel();
            jumpSelectToToday('#igcal-month-select', '#igcal-apply-month', function (opt) {
              return currentLabel && String(opt.textContent || '').indexOf(currentLabel) !== -1;
            });
          } },
          { label: 'Next Month', onClick: function () { applySelectAction('#igcal-month-select', '#igcal-apply-month', 1); } }
        ]);
      }
    }
  }

  function boot() {
    bindYearControls();
    bindDayMonthEnhancements();
    syncRangeToYear();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.addEventListener('load', boot);
  setTimeout(boot, 0);
  setTimeout(boot, 250);
})();
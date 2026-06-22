/* awag-ijaw-calendar.js
 * Ijaw Calendar integration for AWAG Ijaw skin
 *
 * Calendar: 4-day market week (Ari / Beni / Tibi / Oki)
 * Market cycle anchor: UNCONFIRMED — community confirmation requested
 * This module displays the market week structure and requests
 * community members to confirm the current day anchor via feedback.
 *
 * Only runs on the Ijaw skin page.
 */
(function () {
  'use strict';

  if (!/\/awag\/skins\/ijaw\//.test(window.location.pathname)) return;

  var MARKET_DAYS = ['Ari', 'Beni', 'Tibi', 'Oki'];
  var MARKET_COLORS = ['#ff6030', '#3090ff', '#30a050', '#a0c0ff'];
  var MARKET_MEANINGS = [
    'First market — major trading day',
    'Second market — fishing goods',
    'Third market — craft and produce',
    'Fourth market — livestock and rest'
  ];

  /* ── Anchor status ───────────────────────────────────────────── */
  // UNCONFIRMED: We do not yet know which Gregorian date maps to Ari.
  // The module displays the cycle structure but marks the anchor as
  // community-pending. Once a community member confirms via feedback,
  // the anchor will be hardcoded and this prompt removed.
  var ANCHOR_CONFIRMED = false;
  // Placeholder anchor — will be replaced once community confirms
  // Set to null to indicate unconfirmed state
  var ANCHOR_ARI_UTC = null;

  function getMarketDay(date) {
    if (!ANCHOR_ARI_UTC) return null;
    var utc = Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());
    var diff = Math.round((utc - ANCHOR_ARI_UTC) / (24 * 3600 * 1000));
    var idx = ((diff % 4) + 4) % 4;
    return { name: MARKET_DAYS[idx], index: idx, color: MARKET_COLORS[idx], meaning: MARKET_MEANINGS[idx] };
  }

  function injectCSS() {
    var css =
      '.ijaw-cal{background:rgba(30,90,150,.08);border:1px solid rgba(30,90,150,.25);border-radius:14px;padding:18px 20px;margin:20px 0;font-family:inherit}' +
      '.ijaw-cal__title{font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:rgba(232,240,232,.4);margin-bottom:12px}' +
      '.ijaw-cal__row{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:10px}' +
      '.ijaw-cal__item{flex:1;min-width:130px;background:rgba(0,0,0,.25);border-radius:9px;padding:10px 13px}' +
      '.ijaw-cal__label{font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:rgba(232,240,232,.35);margin-bottom:3px}' +
      '.ijaw-cal__val{font-size:.95rem;font-weight:800;color:#e8f0e8;line-height:1.3}' +
      '.ijaw-cal__sub{font-size:.72rem;color:rgba(232,240,232,.45);margin-top:2px}' +
      '.ijaw-cal__confirm{background:rgba(255,160,30,.07);border:1px solid rgba(255,160,30,.25);border-radius:10px;padding:14px 16px;margin-top:12px}' +
      '.ijaw-cal__confirm-title{font-size:.78rem;font-weight:800;color:#ffa01e;margin-bottom:6px}' +
      '.ijaw-cal__confirm-text{font-size:.75rem;color:rgba(232,240,232,.5);line-height:1.6;margin-bottom:10px}' +
      '.ijaw-cal__days{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}' +
      '.ijaw-cal__day{padding:6px 14px;border-radius:20px;font-size:.82rem;font-weight:700;border:1px solid;cursor:pointer;background:transparent;font-family:inherit}' +
      '.ijaw-cal__day:hover{opacity:.8}' +
      '.ijaw-cal__sent{font-size:.78rem;color:#60c840;display:none;margin-top:6px}' +
      '.ijaw-cal__note{font-size:.75rem;color:rgba(232,240,232,.4);margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.06);line-height:1.6}';
    var s = document.createElement('style');
    s.textContent = css;
    document.head.appendChild(s);
  }

  function sendConfirmation(dayName) {
    var today = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    var payload = {
      type: 'correction',
      skin: 'ijaw',
      month: 0,
      module: 'Market Days',
      field: 'anchor',
      current: 'Unconfirmed',
      correct: today + ' is ' + dayName,
      source: 'Community member confirmation via AWAG widget',
      notes: 'Ijaw market day anchor confirmation: ' + today + ' = ' + dayName
    };
    fetch('/awag/feedback.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).catch(function () {});
  }

  function render() {
    var today = new Date();
    var todayStr = today.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

    var html =
      '<div class="ijaw-cal">' +
      '<div class="ijaw-cal__title">🗓 Ijaw Market Week — Ụbọchị Ahịa</div>' +
      '<div class="ijaw-cal__row">';

    MARKET_DAYS.forEach(function (day, i) {
      html +=
        '<div class="ijaw-cal__item">' +
        '<div class="ijaw-cal__label">Day ' + (i + 1) + '</div>' +
        '<div class="ijaw-cal__val" style="color:' + MARKET_COLORS[i] + '">' + day + '</div>' +
        '<div class="ijaw-cal__sub">' + MARKET_MEANINGS[i] + '</div>' +
        '</div>';
    });

    html += '</div>';

    // Community confirmation prompt
    html +=
      '<div class="ijaw-cal__confirm">' +
      '<div class="ijaw-cal__confirm-title">📍 Help us anchor the Ijaw market calendar</div>' +
      '<div class="ijaw-cal__confirm-text">' +
      'Today is <strong>' + todayStr + '</strong>. ' +
      'If you are from an Ijaw community, please tell us which market day today is. ' +
      'Your confirmation will allow us to calculate the correct market day for every date.' +
      '</div>' +
      '<div class="ijaw-cal__days">';

    MARKET_DAYS.forEach(function (day, i) {
      html +=
        '<button class="ijaw-cal__day" ' +
        'style="color:' + MARKET_COLORS[i] + ';border-color:' + MARKET_COLORS[i] + '44" ' +
        'onclick="ijawConfirm(\'' + day + '\',this)">' +
        'Today is ' + day +
        '</button>';
    });

    html +=
      '</div>' +
      '<div class="ijaw-cal__sent" id="ijaw-sent">✅ Thank you. Your confirmation has been sent for review.</div>' +
      '</div>' +
      '<div class="ijaw-cal__note">' +
      'The Ijaw 4-day market week (Ari · Beni · Tibi · Oki) governs trading, fishing, and community gathering ' +
      'across the Niger Delta. Each day carries specific spiritual and commercial significance.' +
      '</div>' +
      '</div>';

    window.ijawConfirm = function (dayName, btn) {
      sendConfirmation(dayName);
      document.getElementById('ijaw-sent').style.display = 'block';
      var btns = btn.parentNode.querySelectorAll('.ijaw-cal__day');
      btns.forEach(function (b) { b.disabled = true; b.style.opacity = '.4'; });
    };

    var mount = document.querySelector('.modules');
    if (mount) {
      var div = document.createElement('div');
      div.className = 'wrap';
      div.innerHTML = html;
      mount.parentNode.insertBefore(div, mount);
    }
  }

  injectCSS();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', render);
  } else {
    render();
  }

})();
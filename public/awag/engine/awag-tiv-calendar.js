/* awag-tiv-calendar.js
 * Tiv Calendar integration for AWAG Tiv skin
 *
 * Calendar: 4-day market week (Aôndo / Gbaa / Shighe / Ikyaa)
 * Market cycle anchor: UNCONFIRMED — community confirmation requested
 *
 * Only runs on the Tiv skin page.
 */
(function () {
  'use strict';

  if (!/\/awag\/skins\/tiv\//.test(window.location.pathname)) return;

  var MARKET_DAYS = ['Aôndo', 'Gbaa', 'Shighe', 'Ikyaa'];
  var MARKET_COLORS = ['#e8a020', '#3090ff', '#c040c0', '#30a050'];
  var MARKET_MEANINGS = [
    'Sky day — major market, God\'s day',
    'Second market — grain and livestock',
    'Third market — craft and medicine',
    'Fourth market — rest and family'
  ];

  var ANCHOR_CONFIRMED = false;
  var ANCHOR_AONDO_UTC = null;

  function getMarketDay(date) {
    if (!ANCHOR_AONDO_UTC) return null;
    var utc = Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());
    var diff = Math.round((utc - ANCHOR_AONDO_UTC) / (24 * 3600 * 1000));
    var idx = ((diff % 4) + 4) % 4;
    return { name: MARKET_DAYS[idx], index: idx, color: MARKET_COLORS[idx], meaning: MARKET_MEANINGS[idx] };
  }

  function injectCSS() {
    var css =
      '.tiv-cal{background:rgba(232,160,32,.06);border:1px solid rgba(232,160,32,.2);border-radius:14px;padding:18px 20px;margin:20px 0;font-family:inherit}' +
      '.tiv-cal__title{font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:rgba(232,240,232,.4);margin-bottom:12px}' +
      '.tiv-cal__row{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:10px}' +
      '.tiv-cal__item{flex:1;min-width:130px;background:rgba(0,0,0,.25);border-radius:9px;padding:10px 13px}' +
      '.tiv-cal__label{font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:rgba(232,240,232,.35);margin-bottom:3px}' +
      '.tiv-cal__val{font-size:.95rem;font-weight:800;color:#e8f0e8;line-height:1.3}' +
      '.tiv-cal__sub{font-size:.72rem;color:rgba(232,240,232,.45);margin-top:2px}' +
      '.tiv-cal__confirm{background:rgba(232,160,32,.07);border:1px solid rgba(232,160,32,.22);border-radius:10px;padding:14px 16px;margin-top:12px}' +
      '.tiv-cal__confirm-title{font-size:.78rem;font-weight:800;color:#e8a020;margin-bottom:6px}' +
      '.tiv-cal__confirm-text{font-size:.75rem;color:rgba(232,240,232,.5);line-height:1.6;margin-bottom:10px}' +
      '.tiv-cal__days{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}' +
      '.tiv-cal__day{padding:6px 14px;border-radius:20px;font-size:.82rem;font-weight:700;border:1px solid;cursor:pointer;background:transparent;font-family:inherit}' +
      '.tiv-cal__day:hover{opacity:.8}' +
      '.tiv-cal__sent{font-size:.78rem;color:#60c840;display:none;margin-top:6px}' +
      '.tiv-cal__note{font-size:.75rem;color:rgba(232,240,232,.4);margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.06);line-height:1.6}';
    var s = document.createElement('style');
    s.textContent = css;
    document.head.appendChild(s);
  }

  function sendConfirmation(dayName) {
    var today = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    var payload = {
      type: 'correction',
      skin: 'tiv',
      month: 0,
      module: 'Market Days',
      field: 'anchor',
      current: 'Unconfirmed',
      correct: today + ' is ' + dayName,
      source: 'Community member confirmation via AWAG widget',
      notes: 'Tiv market day anchor confirmation: ' + today + ' = ' + dayName
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
      '<div class="tiv-cal">' +
      '<div class="tiv-cal__title">🗓 Tiv Market Week</div>' +
      '<div class="tiv-cal__row">';

    MARKET_DAYS.forEach(function (day, i) {
      html +=
        '<div class="tiv-cal__item">' +
        '<div class="tiv-cal__label">Day ' + (i + 1) + '</div>' +
        '<div class="tiv-cal__val" style="color:' + MARKET_COLORS[i] + '">' + day + '</div>' +
        '<div class="tiv-cal__sub">' + MARKET_MEANINGS[i] + '</div>' +
        '</div>';
    });

    html += '</div>';

    html +=
      '<div class="tiv-cal__confirm">' +
      '<div class="tiv-cal__confirm-title">📍 Help us anchor the Tiv market calendar</div>' +
      '<div class="tiv-cal__confirm-text">' +
      'Today is <strong>' + todayStr + '</strong>. ' +
      'If you are from a Tiv community, please tell us which market day today is. ' +
      'Your confirmation will allow us to calculate the correct market day for every date.' +
      '</div>' +
      '<div class="tiv-cal__days">';

    MARKET_DAYS.forEach(function (day, i) {
      html +=
        '<button class="tiv-cal__day" ' +
        'style="color:' + MARKET_COLORS[i] + ';border-color:' + MARKET_COLORS[i] + '44" ' +
        'onclick="tivConfirm(\'' + day + '\',this)">' +
        'Today is ' + day +
        '</button>';
    });

    html +=
      '</div>' +
      '<div class="tiv-cal__sent" id="tiv-sent">✅ Thank you. Your confirmation has been sent for review.</div>' +
      '</div>' +
      '<div class="tiv-cal__note">' +
      'The Tiv 4-day market week (Aôndo · Gbaa · Shighe · Ikyaa) structures community life along the Benue River valley. ' +
      'Aôndo — the sky and God — gives the first day its sacred character. ' +
      'Kwagh-hir storytelling festivals follow the market week cycle.' +
      '</div>' +
      '</div>';

    window.tivConfirm = function (dayName, btn) {
      sendConfirmation(dayName);
      document.getElementById('tiv-sent').style.display = 'block';
      var btns = btn.parentNode.querySelectorAll('.tiv-cal__day');
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
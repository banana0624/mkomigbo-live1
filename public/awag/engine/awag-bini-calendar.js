/* awag-bini-calendar.js
 * Bini (Benin Kingdom) Calendar integration for AWAG Bini skin
 *
 * Calendar: 4-day market week (Ekioba / Ekiogbe / Ekioso / Ekiudu)
 * Market cycle anchor: UNCONFIRMED — community confirmation requested
 *
 * Only runs on the Bini skin page.
 */
(function () {
  'use strict';

  if (!/\/awag\/skins\/bini\//.test(window.location.pathname)) return;

  var MARKET_DAYS = ['Ekioba', 'Ekiogbe', 'Ekioso', 'Ekiudu'];
  var MARKET_COLORS = ['#c0392b', '#8e44ad', '#16a085', '#d35400'];
  var MARKET_MEANINGS = [
    'First market — royal tribute day',
    'Second market — craft guilds active',
    'Third market — farm produce',
    'Fourth market — rest and ancestral rites'
  ];

  var ANCHOR_CONFIRMED = false;
  var ANCHOR_EKIOBA_UTC = null;

  function getMarketDay(date) {
    if (!ANCHOR_EKIOBA_UTC) return null;
    var utc = Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());
    var diff = Math.round((utc - ANCHOR_EKIOBA_UTC) / (24 * 3600 * 1000));
    var idx = ((diff % 4) + 4) % 4;
    return { name: MARKET_DAYS[idx], index: idx, color: MARKET_COLORS[idx], meaning: MARKET_MEANINGS[idx] };
  }

  function injectCSS() {
    var css =
      '.bini-cal{background:rgba(192,57,43,.06);border:1px solid rgba(192,57,43,.2);border-radius:14px;padding:18px 20px;margin:20px 0;font-family:inherit}' +
      '.bini-cal__title{font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:rgba(232,240,232,.4);margin-bottom:12px}' +
      '.bini-cal__row{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:10px}' +
      '.bini-cal__item{flex:1;min-width:130px;background:rgba(0,0,0,.25);border-radius:9px;padding:10px 13px}' +
      '.bini-cal__label{font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:rgba(232,240,232,.35);margin-bottom:3px}' +
      '.bini-cal__val{font-size:.95rem;font-weight:800;color:#e8f0e8;line-height:1.3}' +
      '.bini-cal__sub{font-size:.72rem;color:rgba(232,240,232,.45);margin-top:2px}' +
      '.bini-cal__confirm{background:rgba(192,57,43,.08);border:1px solid rgba(192,57,43,.25);border-radius:10px;padding:14px 16px;margin-top:12px}' +
      '.bini-cal__confirm-title{font-size:.78rem;font-weight:800;color:#e74c3c;margin-bottom:6px}' +
      '.bini-cal__confirm-text{font-size:.75rem;color:rgba(232,240,232,.5);line-height:1.6;margin-bottom:10px}' +
      '.bini-cal__days{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}' +
      '.bini-cal__day{padding:6px 14px;border-radius:20px;font-size:.82rem;font-weight:700;border:1px solid;cursor:pointer;background:transparent;font-family:inherit}' +
      '.bini-cal__day:hover{opacity:.8}' +
      '.bini-cal__sent{font-size:.78rem;color:#60c840;display:none;margin-top:6px}' +
      '.bini-cal__note{font-size:.75rem;color:rgba(232,240,232,.4);margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.06);line-height:1.6}';
    var s = document.createElement('style');
    s.textContent = css;
    document.head.appendChild(s);
  }

  function sendConfirmation(dayName) {
    var today = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    var payload = {
      type: 'correction',
      skin: 'bini',
      month: 0,
      module: 'Market Days',
      field: 'anchor',
      current: 'Unconfirmed',
      correct: today + ' is ' + dayName,
      source: 'Community member confirmation via AWAG widget',
      notes: 'Bini market day anchor confirmation: ' + today + ' = ' + dayName
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
      '<div class="bini-cal">' +
      '<div class="bini-cal__title">🗓 Bini Market Week — Benin Kingdom</div>' +
      '<div class="bini-cal__row">';

    MARKET_DAYS.forEach(function (day, i) {
      html +=
        '<div class="bini-cal__item">' +
        '<div class="bini-cal__label">Day ' + (i + 1) + '</div>' +
        '<div class="bini-cal__val" style="color:' + MARKET_COLORS[i] + '">' + day + '</div>' +
        '<div class="bini-cal__sub">' + MARKET_MEANINGS[i] + '</div>' +
        '</div>';
    });

    html += '</div>';

    html +=
      '<div class="bini-cal__confirm">' +
      '<div class="bini-cal__confirm-title">📍 Help us anchor the Bini market calendar</div>' +
      '<div class="bini-cal__confirm-text">' +
      'Today is <strong>' + todayStr + '</strong>. ' +
      'If you are from Benin City or a Bini community, please tell us which market day today is. ' +
      'Your knowledge of the Oba\'s court calendar will allow us to calculate the correct market day for every date.' +
      '</div>' +
      '<div class="bini-cal__days">';

    MARKET_DAYS.forEach(function (day, i) {
      html +=
        '<button class="bini-cal__day" ' +
        'style="color:' + MARKET_COLORS[i] + ';border-color:' + MARKET_COLORS[i] + '44" ' +
        'onclick="biniConfirm(\'' + day + '\',this)">' +
        'Today is ' + day +
        '</button>';
    });

    html +=
      '</div>' +
      '<div class="bini-cal__sent" id="bini-sent">✅ Thank you. Your confirmation has been sent for review.</div>' +
      '</div>' +
      '<div class="bini-cal__note">' +
      'The Bini 4-day market week (Ekioba · Ekiogbe · Ekioso · Ekiudu) has governed trade and court life ' +
      'in Benin Kingdom for centuries. The Oba\'s palace ceremonies follow this cycle. ' +
      'Bronze casting guilds, the Igun Eronmwon, schedule their work around market days.' +
      '</div>' +
      '</div>';

    window.biniConfirm = function (dayName, btn) {
      sendConfirmation(dayName);
      document.getElementById('bini-sent').style.display = 'block';
      var btns = btn.parentNode.querySelectorAll('.bini-cal__day');
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
/* awag-feedback.js — Community Correction & Feedback Widget
 * Adds "Correct this page" button to all AWAG skin pages
 * Users can correct facts or suggest new communities
 */
(function () {
  'use strict';

  function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  function getSkinId() {
    var m = window.location.pathname.match(/\/awag\/skins\/([^\/]+)\//);
    return m ? m[1] : '';
  }

  function getMonthNo() {
    var m = window.location.search.match(/[?&]m=(\d+)/);
    return m ? parseInt(m[1],10) : 1;
  }

  function injectCSS() {
    var css = `
.awag-fb-bar{background:rgba(245,217,122,.06);border-top:1px solid rgba(245,217,122,.10);padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:32px}
.awag-fb-bar__text{font-size:.8rem;color:rgba(232,224,208,.5);line-height:1.5}
.awag-fb-bar__text strong{color:rgba(245,217,122,.7);display:block;margin-bottom:2px}
.awag-fb-btn{padding:8px 16px;border-radius:9px;border:1px solid rgba(245,217,122,.3);background:rgba(245,217,122,.08);color:#f5d97a;font-size:.84rem;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;white-space:nowrap}
.awag-fb-btn:hover{background:rgba(245,217,122,.16);border-color:rgba(245,217,122,.5)}

.awag-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:1000;align-items:center;justify-content:center;padding:16px}
.awag-modal-overlay.open{display:flex}
.awag-modal{background:#1a0e04;border:1px solid rgba(245,217,122,.2);border-radius:18px;max-width:520px;width:100%;max-height:90vh;overflow-y:auto;padding:24px;position:relative}
.awag-modal__close{position:absolute;top:14px;right:16px;background:none;border:none;font-size:1.4rem;color:rgba(232,224,208,.5);cursor:pointer;line-height:1;padding:4px}
.awag-modal__close:hover{color:#f5d97a}
.awag-modal__title{font-size:1.1rem;font-weight:900;color:#f5d97a;margin-bottom:6px}
.awag-modal__sub{font-size:.82rem;color:rgba(232,224,208,.5);margin-bottom:18px;line-height:1.5}
.awag-tabs2{display:flex;gap:0;border-bottom:1px solid rgba(245,217,122,.12);margin-bottom:18px}
.awag-tab2{padding:8px 16px;font-size:.84rem;font-weight:700;color:rgba(232,224,208,.45);cursor:pointer;border:none;border-bottom:2px solid transparent;margin-bottom:-1px;background:none;font-family:inherit;transition:all .15s}
.awag-tab2.active{color:#f5d97a;border-bottom-color:#f5d97a}
.awag-form{display:flex;flex-direction:column;gap:12px}
.awag-form__panel{display:none}
.awag-form__panel.active{display:flex;flex-direction:column;gap:12px}
.awag-field{display:flex;flex-direction:column;gap:4px}
.awag-field label{font-size:.75rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:rgba(245,217,122,.45)}
.awag-field input,.awag-field textarea,.awag-field select{background:rgba(0,0,0,.3);border:1px solid rgba(245,217,122,.18);border-radius:9px;color:#e8e0d0;padding:9px 12px;font-size:.88rem;font-family:inherit;resize:vertical;transition:border-color .15s;width:100%}
.awag-field input:focus,.awag-field textarea:focus,.awag-field select:focus{outline:none;border-color:rgba(245,217,122,.5)}
.awag-field select option{background:#1a0e04}
.awag-submit{padding:11px;background:#2d6a1f;color:#fff;border:none;border-radius:10px;font-size:.9rem;font-weight:700;cursor:pointer;font-family:inherit;margin-top:4px;transition:background .15s}
.awag-submit:hover{background:#3d8a2f}
.awag-submit:disabled{background:#1a3a10;cursor:default;opacity:.6}
.awag-success{text-align:center;padding:20px;color:#60c840}
.awag-success__icon{font-size:2.5rem;margin-bottom:10px}
.awag-success__title{font-size:1rem;font-weight:900;margin-bottom:6px}
.awag-success__sub{font-size:.82rem;color:rgba(232,224,208,.55);line-height:1.5}
.awag-error-msg{color:#ff6060;font-size:.82rem;padding:6px 0}
`;
    var s = document.createElement('style');
    s.textContent = css;
    document.head.appendChild(s);
  }

  function createModal(skinId, monthNo) {
    var modal = document.createElement('div');
    modal.className = 'awag-modal-overlay';
    modal.id = 'awag-feedback-modal';
    modal.innerHTML = `
<div class="awag-modal">
  <button class="awag-modal__close" id="awag-close-btn">&times;</button>
  <div class="awag-modal__title">📝 Community Feedback</div>
  <div class="awag-modal__sub">Help us keep AWAG accurate. Correct a fact, add missing information, or tell us about your community.</div>

  <div class="awag-tabs2">
    <button class="awag-tab2 active" data-panel="correction">Correct a Fact</button>
    <button class="awag-tab2" data-panel="community">Add Community</button>
    <button class="awag-tab2" data-panel="general">General Feedback</button>
  </div>

  <!-- Correction form -->
  <div class="awag-form__panel active" id="panel-correction">
    <div class="awag-field">
      <label>Module / Section</label>
      <select id="fb-module">
        <option value="">Select module...</option>
        <option>Market Days</option>
        <option>Farming</option>
        <option>Fishing</option>
        <option>Trading</option>
        <option>Herding</option>
        <option>Healing</option>
        <option>Weather</option>
        <option>Traditional Rulers</option>
        <option>Moon Phase</option>
        <option>Tides</option>
        <option>Winds</option>
        <option>Rainfall</option>
        <option>Month Names</option>
        <option>Other</option>
      </select>
    </div>
    <div class="awag-field">
      <label>What is currently shown (incorrect)</label>
      <textarea id="fb-current" rows="2" placeholder="What does the app currently say?"></textarea>
    </div>
    <div class="awag-field">
      <label>What it should say (correct)</label>
      <textarea id="fb-correct" rows="3" placeholder="What is the correct information?"></textarea>
    </div>
    <div class="awag-field">
      <label>Your source or reason</label>
      <input type="text" id="fb-source" placeholder="e.g. Elder knowledge, local experience, published source...">
    </div>
    <div class="awag-field">
      <label>Your name (optional)</label>
      <input type="text" id="fb-name-c" placeholder="Your name or community role">
    </div>
    <div class="awag-field">
      <label>Email (optional — for follow-up)</label>
      <input type="email" id="fb-email-c" placeholder="your@email.com">
    </div>
    <button class="awag-submit" id="submit-correction">Send Correction</button>
    <div class="awag-error-msg" id="error-correction"></div>
  </div>

  <!-- New community form -->
  <div class="awag-form__panel" id="panel-community">
    <div class="awag-modal__sub" style="margin:0 0 8px">Tell us about a community not yet in AWAG. We will build a skin for them.</div>
    <div class="awag-field">
      <label>Community name</label>
      <input type="text" id="fb-community" placeholder="e.g. Ijaw, Tiv, Bini, Zulu...">
    </div>
    <div class="awag-field">
      <label>Region / Ecological zone</label>
      <select id="fb-region">
        <option value="">Select region...</option>
        <option>Guinea Coast</option>
        <option>Sahel & Savanna</option>
        <option>East African Rift</option>
        <option>Indian Ocean Coast</option>
        <option>Congo Basin</option>
        <option>Southern Africa</option>
        <option>North Africa</option>
      </select>
    </div>
    <div class="awag-field">
      <label>Geographic area / Country</label>
      <input type="text" id="fb-area" placeholder="e.g. Niger Delta, Rivers State, Nigeria">
    </div>
    <div class="awag-field">
      <label>Language</label>
      <input type="text" id="fb-language" placeholder="e.g. Ijaw / Izon">
    </div>
    <div class="awag-field">
      <label>Key facts (market week, calendar system, festivals)</label>
      <textarea id="fb-notes" rows="4" placeholder="Tell us about their calendar, market days, seasonal practices, festivals..."></textarea>
    </div>
    <div class="awag-field">
      <label>Your name and connection to this community</label>
      <input type="text" id="fb-name-n" placeholder="e.g. Elder, researcher, community member...">
    </div>
    <div class="awag-field">
      <label>Email (optional)</label>
      <input type="email" id="fb-email-n" placeholder="your@email.com">
    </div>
    <button class="awag-submit" id="submit-community">Submit Community</button>
    <div class="awag-error-msg" id="error-community"></div>
  </div>

  <!-- General feedback form -->
  <div class="awag-form__panel" id="panel-general">
    <div class="awag-field">
      <label>Your feedback</label>
      <textarea id="fb-general" rows="5" placeholder="Any feedback, suggestions, praise or concerns about AWAG..."></textarea>
    </div>
    <div class="awag-field">
      <label>Your name (optional)</label>
      <input type="text" id="fb-name-g" placeholder="Your name">
    </div>
    <div class="awag-field">
      <label>Email (optional)</label>
      <input type="email" id="fb-email-g" placeholder="your@email.com">
    </div>
    <button class="awag-submit" id="submit-general">Send Feedback</button>
    <div class="awag-error-msg" id="error-general"></div>
  </div>

  <div class="awag-success" id="awag-success" style="display:none">
    <div class="awag-success__icon">✅</div>
    <div class="awag-success__title">Thank you!</div>
    <div class="awag-success__sub">Your contribution has been received. Our team will review it and update the calendar. Community knowledge makes AWAG better for everyone.</div>
  </div>
</div>`;
    document.body.appendChild(modal);

    // Close button
    document.getElementById('awag-close-btn').addEventListener('click', function(){
      modal.classList.remove('open');
    });
    modal.addEventListener('click', function(e){
      if (e.target === modal) modal.classList.remove('open');
    });

    // Tab switching
    modal.querySelectorAll('.awag-tab2').forEach(function(btn){
      btn.addEventListener('click', function(){
        modal.querySelectorAll('.awag-tab2').forEach(function(b){b.classList.remove('active');});
        modal.querySelectorAll('.awag-form__panel').forEach(function(p){p.classList.remove('active');});
        btn.classList.add('active');
        var panel = document.getElementById('panel-'+btn.dataset.panel);
        if (panel) panel.classList.add('active');
      });
    });

    // Submit handlers
    function post(payload, errId, btnId) {
      var btn = document.getElementById(btnId);
      var err = document.getElementById(errId);
      btn.disabled = true;
      btn.textContent = 'Sending...';
      err.textContent = '';
      fetch('/awag/feedback.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      }).then(function(r){return r.json();}).then(function(d){
        if (d.success) {
          modal.querySelectorAll('.awag-form__panel,.awag-tabs2').forEach(function(el){el.style.display='none';});
          document.getElementById('awag-success').style.display = 'block';
        } else {
          err.textContent = d.error || 'Something went wrong. Please try again.';
          btn.disabled = false;
          btn.textContent = 'Try again';
        }
      }).catch(function(){
        err.textContent = 'Network error. Please check your connection.';
        btn.disabled = false;
        btn.textContent = 'Try again';
      });
    }

    document.getElementById('submit-correction').addEventListener('click', function(){
      var correct = document.getElementById('fb-correct').value.trim();
      if (!correct) { document.getElementById('error-correction').textContent='Please enter the correct information.'; return; }
      post({
        type:'correction', skin:skinId, month:monthNo,
        module:document.getElementById('fb-module').value,
        current:document.getElementById('fb-current').value.trim(),
        correct:correct,
        source:document.getElementById('fb-source').value.trim(),
        name:document.getElementById('fb-name-c').value.trim(),
        email:document.getElementById('fb-email-c').value.trim(),
      }, 'error-correction', 'submit-correction');
    });

    document.getElementById('submit-community').addEventListener('click', function(){
      var community = document.getElementById('fb-community').value.trim();
      if (!community) { document.getElementById('error-community').textContent='Please enter a community name.'; return; }
      post({
        type:'new_community',
        community:community,
        region:document.getElementById('fb-region').value,
        area:document.getElementById('fb-area').value.trim(),
        language:document.getElementById('fb-language').value.trim(),
        notes:document.getElementById('fb-notes').value.trim(),
        name:document.getElementById('fb-name-n').value.trim(),
        email:document.getElementById('fb-email-n').value.trim(),
      }, 'error-community', 'submit-community');
    });

    document.getElementById('submit-general').addEventListener('click', function(){
      var notes = document.getElementById('fb-general').value.trim();
      if (!notes) { document.getElementById('error-general').textContent='Please enter your feedback.'; return; }
      post({
        type:'general',
        notes:notes,
        name:document.getElementById('fb-name-g').value.trim(),
        email:document.getElementById('fb-email-g').value.trim(),
      }, 'error-general', 'submit-general');
    });

    return modal;
  }

  function init() {
    var skinId  = getSkinId();
    var monthNo = getMonthNo();
    if (!skinId) return;

    injectCSS();

    // Add feedback bar before footer
    var footer = document.querySelector('.site-footer, footer');
    if (!footer) return;

    var bar = document.createElement('div');
    bar.className = 'awag-fb-bar';
    bar.innerHTML = `
      <div class="awag-fb-bar__text">
        <strong>📍 Know your community better?</strong>
        Help us improve — correct a fact, add missing information, or tell us about your community.
      </div>
      <button class="awag-fb-btn" id="awag-open-feedback">✏️ Correct / Add</button>
    `;
    footer.parentNode.insertBefore(bar, footer);

    var modal = createModal(skinId, monthNo);

    document.getElementById('awag-open-feedback').addEventListener('click', function(){
      document.getElementById('awag-success').style.display = 'none';
      modal.querySelectorAll('.awag-form__panel').forEach(function(p){p.style.display='';p.classList.remove('active');});
      modal.querySelectorAll('.awag-tabs2').forEach(function(t){t.style.display='';});
      modal.querySelectorAll('.awag-tab2').forEach(function(b){b.classList.remove('active');});
      modal.querySelector('.awag-tab2').classList.add('active');
      modal.querySelector('.awag-form__panel').classList.add('active');
      modal.classList.add('open');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
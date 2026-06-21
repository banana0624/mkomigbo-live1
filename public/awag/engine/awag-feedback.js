/* awag-feedback.js — Community Correction & Feedback Widget */
(function(){
'use strict';
function getSkinId(){var m=window.location.pathname.match(/\/awag\/skins\/([^\/]+)\//);return m?m[1]:'';}
function getMonthNo(){var m=window.location.search.match(/[?&]m=(\d+)/);return m?parseInt(m[1],10):1;}
function injectCSS(){
var css='.awag-fb-bar{background:rgba(245,217,122,.06);border-top:1px solid rgba(245,217,122,.10);padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:0}'
+'.awag-fb-bar__text{font-size:.8rem;color:rgba(232,224,208,.5);line-height:1.5}'
+'.awag-fb-bar__text strong{color:rgba(245,217,122,.7);display:block;margin-bottom:2px}'
+'.awag-fb-btn{padding:8px 16px;border-radius:9px;border:1px solid rgba(245,217,122,.3);background:rgba(245,217,122,.08);color:#f5d97a;font-size:.84rem;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap}'
+'.awag-fb-btn:hover{background:rgba(245,217,122,.18)}'
+'.awag-ov{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;padding:16px}'
+'.awag-ov.open{display:flex}'
+'.awag-mod{background:#1a0e04;border:1px solid rgba(245,217,122,.22);border-radius:18px;max-width:520px;width:100%;max-height:88vh;overflow-y:auto;padding:24px;position:relative}'
+'.awag-mod__x{position:absolute;top:12px;right:14px;background:none;border:none;font-size:1.4rem;color:rgba(232,224,208,.4);cursor:pointer;line-height:1}'
+'.awag-mod__x:hover{color:#f5d97a}'
+'.awag-mod__title{font-size:1.05rem;font-weight:900;color:#f5d97a;margin-bottom:5px}'
+'.awag-mod__sub{font-size:.8rem;color:rgba(232,224,208,.45);margin-bottom:16px;line-height:1.5}'
+'.awag-ttabs{display:flex;border-bottom:1px solid rgba(245,217,122,.12);margin-bottom:16px;gap:0}'
+'.awag-ttab{padding:8px 14px;font-size:.82rem;font-weight:700;color:rgba(232,224,208,.4);cursor:pointer;border:none;border-bottom:2px solid transparent;margin-bottom:-1px;background:none;font-family:inherit;white-space:nowrap}'
+'.awag-ttab.on{color:#f5d97a;border-bottom-color:#f5d97a}'
+'.awag-pnl{display:none;flex-direction:column;gap:11px}'
+'.awag-pnl.on{display:flex}'
+'.awag-fl{display:flex;flex-direction:column;gap:3px}'
+'.awag-fl label{font-size:.7rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:rgba(245,217,122,.38)}'
+'.awag-fl input,.awag-fl textarea,.awag-fl select{background:rgba(0,0,0,.35);border:1px solid rgba(245,217,122,.15);border-radius:8px;color:#e8e0d0;padding:8px 11px;font-size:.86rem;font-family:inherit;resize:vertical;width:100%}'
+'.awag-fl input:focus,.awag-fl textarea:focus,.awag-fl select:focus{outline:none;border-color:rgba(245,217,122,.45)}'
+'.awag-fl select option{background:#1a0e04}'
+'.awag-sub{padding:10px;background:#2d6a1f;color:#fff;border:none;border-radius:9px;font-size:.88rem;font-weight:700;cursor:pointer;font-family:inherit;margin-top:4px}'
+'.awag-sub:hover{background:#3d8a2f}.awag-sub:disabled{opacity:.5;cursor:default}'
+'.awag-err{color:#ff6060;font-size:.8rem;min-height:1em}'
+'.awag-ok{text-align:center;padding:24px 0}'
+'.awag-ok__icon{font-size:2.5rem;margin-bottom:10px}'
+'.awag-ok__title{font-size:1rem;font-weight:900;color:#60c840;margin-bottom:6px}'
+'.awag-ok__sub{font-size:.82rem;color:rgba(232,224,208,.5);line-height:1.5}';
var s=document.createElement('style');s.textContent=css;document.head.appendChild(s);
}
function makeModal(skinId,monthNo){
var el=document.createElement('div');el.className='awag-ov';el.id='awag-ov';
el.innerHTML='<div class="awag-mod">'
+'<button class="awag-mod__x" id="awag-x">&times;</button>'
+'<div class="awag-mod__title">📝 Community Feedback</div>'
+'<div class="awag-mod__sub">Correct a fact, add missing information, or tell us about your community. Your knowledge makes AWAG better for everyone.</div>'
+'<div class="awag-ttabs">'
+'<button class="awag-ttab on" data-p="corr">✏️ Correct a Fact</button>'
+'<button class="awag-ttab" data-p="comm">➕ Add Community</button>'
+'<button class="awag-ttab" data-p="gen">💬 General</button>'
+'</div>'
+'<div class="awag-pnl on" id="p-corr">'
+'<div class="awag-fl"><label>Module / Section</label><select id="fb-mod"><option value="">Select...</option><option>Market Days</option><option>Farming</option><option>Fishing</option><option>Trading</option><option>Herding</option><option>Healing</option><option>Weather</option><option>Traditional Rulers</option><option>Moon Phase</option><option>Tides</option><option>Winds</option><option>Rainfall</option><option>Month Names</option><option>Other</option></select></div>'
+'<div class="awag-fl"><label>What it currently says (wrong)</label><textarea id="fb-cur" rows="2" placeholder="What does the app say?"></textarea></div>'
+'<div class="awag-fl"><label>What it should say (correct) *</label><textarea id="fb-cor" rows="3" placeholder="The correct information..."></textarea></div>'
+'<div class="awag-fl"><label>Your source or reason</label><input type="text" id="fb-src" placeholder="Elder knowledge, local experience, published source..."></div>'
+'<div class="awag-fl"><label>Your name (optional)</label><input type="text" id="fb-nc" placeholder="Name or community role"></div>'
+'<div class="awag-fl"><label>Email (optional)</label><input type="email" id="fb-ec" placeholder="your@email.com"></div>'
+'<button class="awag-sub" id="sub-corr">Send Correction</button><div class="awag-err" id="err-corr"></div>'
+'</div>'
+'<div class="awag-pnl" id="p-comm">'
+'<div class="awag-fl"><label>Community name *</label><input type="text" id="fb-com" placeholder="e.g. Ijaw, Tiv, Bini, Zulu..."></div>'
+'<div class="awag-fl"><label>Ecological region</label><select id="fb-reg"><option value="">Select...</option><option>Guinea Coast</option><option>Sahel & Savanna</option><option>East African Rift</option><option>Indian Ocean Coast</option><option>Congo Basin</option><option>Southern Africa</option><option>North Africa</option></select></div>'
+'<div class="awag-fl"><label>Geographic area / Country</label><input type="text" id="fb-area" placeholder="e.g. Niger Delta, Rivers State, Nigeria"></div>'
+'<div class="awag-fl"><label>Language</label><input type="text" id="fb-lang" placeholder="e.g. Ijaw / Izon"></div>'
+'<div class="awag-fl"><label>Key facts — market week, calendar, festivals *</label><textarea id="fb-note" rows="4" placeholder="Market days, seasonal practices, festivals, calendar system..."></textarea></div>'
+'<div class="awag-fl"><label>Your name and connection</label><input type="text" id="fb-nn" placeholder="Elder, researcher, community member..."></div>'
+'<div class="awag-fl"><label>Email (optional)</label><input type="email" id="fb-en" placeholder="your@email.com"></div>'
+'<button class="awag-sub" id="sub-comm">Submit Community</button><div class="awag-err" id="err-comm"></div>'
+'</div>'
+'<div class="awag-pnl" id="p-gen">'
+'<div class="awag-fl"><label>Your feedback *</label><textarea id="fb-gen" rows="5" placeholder="Suggestions, corrections, praise or concerns about AWAG..."></textarea></div>'
+'<div class="awag-fl"><label>Your name (optional)</label><input type="text" id="fb-ng" placeholder="Your name"></div>'
+'<div class="awag-fl"><label>Email (optional)</label><input type="email" id="fb-eg" placeholder="your@email.com"></div>'
+'<button class="awag-sub" id="sub-gen">Send Feedback</button><div class="awag-err" id="err-gen"></div>'
+'</div>'
+'<div class="awag-ok" id="awag-ok" style="display:none">'
+'<div class="awag-ok__icon">✅</div>'
+'<div class="awag-ok__title">Thank you for your contribution!</div>'
+'<div class="awag-ok__sub">Our team will review and update the calendar. Community knowledge makes AWAG accurate for everyone in Africa.</div>'
+'</div>'
+'</div>';
document.body.appendChild(el);

document.getElementById('awag-x').onclick=function(){el.classList.remove('open');};
el.onclick=function(e){if(e.target===el)el.classList.remove('open');};

el.querySelectorAll('.awag-ttab').forEach(function(b){
b.onclick=function(){
el.querySelectorAll('.awag-ttab').forEach(function(x){x.classList.remove('on');});
el.querySelectorAll('.awag-pnl').forEach(function(x){x.classList.remove('on');});
b.classList.add('on');
var p=document.getElementById('p-'+b.dataset.p);if(p)p.classList.add('on');
};});

function post(payload,errId,btnId){
var btn=document.getElementById(btnId),err=document.getElementById(errId);
btn.disabled=true;btn.textContent='Sending...';err.textContent='';
fetch('/awag/feedback.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
.then(function(r){return r.json();}).then(function(d){
if(d.success){
el.querySelectorAll('.awag-ttabs,.awag-pnl').forEach(function(x){x.style.display='none';});
document.getElementById('awag-ok').style.display='block';
}else{err.textContent=d.error||'Something went wrong.';btn.disabled=false;btn.textContent='Try again';}
}).catch(function(){err.textContent='Network error. Please check connection.';btn.disabled=false;btn.textContent='Try again';});
}

document.getElementById('sub-corr').onclick=function(){
var v=document.getElementById('fb-cor').value.trim();
if(!v){document.getElementById('err-corr').textContent='Please enter the correct information.';return;}
post({type:'correction',skin:skinId,month:monthNo,module:document.getElementById('fb-mod').value,current:document.getElementById('fb-cur').value.trim(),correct:v,source:document.getElementById('fb-src').value.trim(),name:document.getElementById('fb-nc').value.trim(),email:document.getElementById('fb-ec').value.trim()},'err-corr','sub-corr');};

document.getElementById('sub-comm').onclick=function(){
var v=document.getElementById('fb-com').value.trim();
if(!v){document.getElementById('err-comm').textContent='Please enter a community name.';return;}
post({type:'new_community',community:v,region:document.getElementById('fb-reg').value,area:document.getElementById('fb-area').value.trim(),language:document.getElementById('fb-lang').value.trim(),notes:document.getElementById('fb-note').value.trim(),name:document.getElementById('fb-nn').value.trim(),email:document.getElementById('fb-en').value.trim()},'err-comm','sub-comm');};

document.getElementById('sub-gen').onclick=function(){
var v=document.getElementById('fb-gen').value.trim();
if(!v){document.getElementById('err-gen').textContent='Please enter your feedback.';return;}
post({type:'general',notes:v,name:document.getElementById('fb-ng').value.trim(),email:document.getElementById('fb-eg').value.trim()},'err-gen','sub-gen');};

return el;
}

function init(){
var skinId=getSkinId(),monthNo=getMonthNo();
if(!skinId)return;
injectCSS();
var footer=document.querySelector('.site-footer, .ftr, footer');
if(!footer)return;
var bar=document.createElement('div');bar.className='awag-fb-bar';
bar.innerHTML='<div class="awag-fb-bar__text"><strong>📍 Know your community better?</strong>Help us improve — correct a fact, add missing information, or tell us about a community not yet in AWAG.</div><button class="awag-fb-btn" id="awag-open">✏️ Correct / Add</button>';
footer.parentNode.insertBefore(bar,footer);
var modal=makeModal(skinId,monthNo);
document.getElementById('awag-open').onclick=function(){
document.getElementById('awag-ok').style.display='none';
modal.querySelectorAll('.awag-ttabs,.awag-pnl').forEach(function(x){x.style.display='';x.classList.remove('on');});
modal.querySelector('.awag-ttab').classList.add('on');
modal.querySelector('.awag-pnl').classList.add('on');
modal.classList.add('open');};
}
if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',init);}else{init();}
})();
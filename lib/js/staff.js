/* /lib/js/staff.js
 * Staff UI helpers
 * - Copy-to-clipboard for signed links or any data-copy attribute
 */

(function () {
  'use strict';

  async function copyText(text) {
    // Prefer modern clipboard API when available and permitted
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
      await navigator.clipboard.writeText(text);
      return true;
    }

    // Fallback: temporary textarea (works in more locked-down contexts)
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    ta.style.top = '0';
    document.body.appendChild(ta);
    ta.select();

    let ok = false;
    try {
      ok = document.execCommand('copy');
    } catch (e) {
      ok = false;
    }

    document.body.removeChild(ta);
    return ok;
  }

  function absoluteUrl(maybeRelative) {
    const u = (maybeRelative || '').trim();
    if (!u) return '';
    // If it's already absolute, keep it
    if (/^[a-zA-Z][a-zA-Z0-9+\-.]*:\/\//.test(u)) return u;
    // Otherwise make it absolute to current origin
    return window.location.origin.replace(/\/$/, '') + (u.startsWith('/') ? u : ('/' + u));
  }

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;

    const raw = btn.getAttribute('data-copy') || '';
    const url = absoluteUrl(raw);
    if (!url) return;

    const originalText = (btn.textContent || '').trim();
    const copiedText = btn.getAttribute('data-copied-text') || 'Copied';
    const resetDelay = parseInt(btn.getAttribute('data-copy-reset-ms') || '1200', 10);

    try {
      const ok = await copyText(url);
      if (!ok) throw new Error('copy failed');

      btn.textContent = copiedText;
      window.setTimeout(() => {
        btn.textContent = originalText || btn.textContent;
      }, isFinite(resetDelay) ? resetDelay : 1200);
    } catch (err) {
      // Last-resort fallback: prompt
      window.prompt('Copy this link:', url);
    }
  });
})();

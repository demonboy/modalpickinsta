document.addEventListener('DOMContentLoaded', () => {
  if (!window.AUTH_GUARD || !!AUTH_GUARD.isLoggedIn) return;

  function isInternalLink(a) {
    const href = a.getAttribute('href') || '';
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;
    try {
      const url = new URL(href, window.location.origin);
      return url.origin === window.location.origin;
    } catch (_) {
      return false;
    }
  }

  function openAuthModal() {
    const modal = document.getElementById('universal-modal');
    if (modal) modal.dataset.profileOpen = '1';
    if (window.openModal) window.openModal('<div class="loading">Loading…</div>');
    fetch(AUTH_GUARD.ajax_url, {
      method: 'POST',
      body: new URLSearchParams({ action: 'get_auth_modal', nonce: AUTH_GUARD.nonce }),
      credentials: 'same-origin'
    })
      .then(r => r.json())
      .then(data => {
        const body = document.querySelector('#universal-modal .modal-body');
        if (data && data.success && body) { body.innerHTML = data.data.html; }
        try { history.pushState({ profileModal:true, auth:true }, '', '#auth/login'); } catch(_) {}
      });
  }

  document.body.addEventListener('click', (e) => {
    const a = e.target.closest('a');
    if (!a) return;
    if (a.hasAttribute('data-no-auth') || a.classList.contains('no-auth')) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || a.target === '_blank') return;
    if (!isInternalLink(a)) return;

    // Store intended URL and open Auth
    e.preventDefault();
    try { sessionStorage.setItem('intended_url', new URL(a.getAttribute('href'), window.location.origin).toString()); } catch(_) {}
    openAuthModal();
  }, true);
});



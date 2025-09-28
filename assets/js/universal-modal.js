document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('universal-modal');
  const modalBackdrop = modal.querySelector('.modal-backdrop');
  const modalContent = modal.querySelector('.modal-content');
  const modalBody = modal.querySelector('.modal-body');
  const modalClose = modal.querySelector('.modal-close');
  
  // Create modal header if it doesn't exist
  let modalHeader = modal.querySelector('.modal-header');
  if (!modalHeader) {
    modalHeader = document.createElement('div');
    modalHeader.className = 'modal-header';
    const backSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 19L8 12L15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    modalHeader.innerHTML = '<button class="modal-back" aria-label="Back" title="Back">' + backSvg + '</button><h2 class="modal-title" id="modal-title"></h2><button class="modal-close" aria-label="Close" title="Close">×</button>';
    modalContent.insertBefore(modalHeader, modalBody);
    // Remove any legacy close buttons outside the new header to avoid duplicates
    try {
      modal.querySelectorAll('.modal-close').forEach(btn => { if (!modalHeader.contains(btn)) btn.remove(); });
    } catch(_) {}
  }

  function openModal(contentHTML) {
    // Simply display the content - let CSS handle form positioning
    modalBody.innerHTML = contentHTML;
    
    // Populate current user's avatar
    const avatarContainer = modalBody.querySelector('#current-user-avatar');
    if (avatarContainer) {
      // Get current user's avatar from WordPress AJAX
      fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: new URLSearchParams({
          action: 'get_current_user_avatar'
        })
      })
      .then(response => response.text())
      .then(avatarHTML => {
        avatarContainer.innerHTML = avatarHTML;
      })
      .catch(error => {
        console.log('Could not load user avatar:', error);
      });
    }
    
    modal.style.display = 'flex';
    modal.classList.remove('show');
    modal.offsetHeight;
    requestAnimationFrame(() => {
      modal.classList.add('show');
    });
    
    // Prevent background scroll
    document.body.classList.add('modal-open');
  }

  // Active modal registry (neutral hooks)
  function setActiveModal(name) {
    try {
      if (name) { modal.dataset.activeModal = String(name); }
      else { delete modal.dataset.activeModal; }
    } catch(_) {}
  }
  function getActiveModal() {
    try { return modal.dataset.activeModal || ''; } catch(_) { return ''; }
  }

  // Simple route stack for modal navigation
  const routeStack = [];
  function updateTitleFromTop() {
    const top = routeStack[routeStack.length - 1];
    setModalTitle(top && top.title ? top.title : '');
  }
  function pushModalRoute(route) {
    const r = Object.assign({ module: '', view: '', title: '', hash: location.hash }, route || {});
    routeStack.push(r);
    updateTitleFromTop();
    try { history.pushState(r, '', r.hash); } catch(_) {}
  }
  function replaceModalRoute(route) {
    const r = Object.assign({ module: '', view: '', title: '', hash: location.hash }, route || {});
    if (routeStack.length) { routeStack[routeStack.length - 1] = r; } else { routeStack.push(r); }
    updateTitleFromTop();
    try { history.replaceState(r, '', r.hash); } catch(_) {}
  }
  function popModalRoute() {
    // Do not mutate the stack here. Let popstate be the single place that
    // reconciles history → stack and updates title/state.
    try { history.back(); } catch(_) {}
  }

  // Keep stack in sync with browser back/forward
  window.addEventListener('popstate', () => {
    // Drop one route when navigating back/forward
    if (routeStack.length > 0) { routeStack.pop(); }

    if (routeStack.length > 0) {
      const top = routeStack[routeStack.length - 1];
      setModalTitle(top && top.title ? top.title : '');
      // Reactivate owning module flags
      try {
        setActiveModal(top.module || '');
        if (top.module === 'profile') {
          modal.dataset.profileOpen = '1';
          delete modal.dataset.socialOpen;
        } else if (top.module === 'social') {
          modal.dataset.socialOpen = '1';
          delete modal.dataset.profileOpen;
        } else {
          delete modal.dataset.profileOpen;
          delete modal.dataset.socialOpen;
        }
      } catch(_) {}
    } else {
      // No routes left → close modal safely
      delete modal.dataset.profileOpen;
      delete modal.dataset.socialOpen;
      setActiveModal('');
      closeModal();
    }
  });

  function setModalTitle(title) {
    try {
      const titleEl = modal.querySelector('.modal-title');
      if (!titleEl) return;
      if (title) {
        titleEl.textContent = title;
        modal.setAttribute('aria-labelledby', 'modal-title');
      } else {
        titleEl.textContent = '';
        modal.removeAttribute('aria-labelledby');
      }
    } catch (_) {}
  }

  function closeModal() {
    modalContent.classList.remove('slide-in', 'show');
    modal.style.display = 'none';
    modalBody.innerHTML = '';
    
    // Re-enable background scroll
    document.body.classList.remove('modal-open');
    // Clear modal title
    setModalTitle('');
    // Safety net: clear profile deep-link hash so refresh won't reopen
    try {
      if (String(location.hash).startsWith('#profile/')) {
        history.replaceState(null, '', location.pathname + location.search);
      }
    } catch(_) {}
  }

  // Make openModal globally available for other scripts
  window.openModal = openModal;
  window.closeModal = closeModal;
  window.setModalTitle = setModalTitle;
  window.setActiveModal = setActiveModal;
  window.getActiveModal = getActiveModal;
  window.pushModalRoute = pushModalRoute;
  window.replaceModalRoute = replaceModalRoute;
  window.popModalRoute = popModalRoute;

  // Event listeners for modal controls
  const headerCloseButton = modalHeader.querySelector('.modal-close');
  const headerBackButton = modalHeader.querySelector('.modal-back');
  // Dispatch neutral events so modules can bind without global coupling
  if (headerBackButton) { headerBackButton.addEventListener('click', () => popModalRoute()); }
  if (headerCloseButton) {
    headerCloseButton.addEventListener('click', () => {
      try { modal.dispatchEvent(new CustomEvent('universal-modal-close', { bubbles: true })); } catch(_) {}
    }, { once: false });
  }
  headerCloseButton.addEventListener('click', closeModal);
  modalBackdrop.addEventListener('click', closeModal);

  // Close modal on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.style.display === 'flex') {
      closeModal();
    }
  });
});
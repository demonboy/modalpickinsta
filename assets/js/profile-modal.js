document.addEventListener('DOMContentLoaded', () => {
  // Helper: set modal header to user's public display name (fallback to profile-modal-name)
  function setHeaderToDisplayName() {
    try {
      const el = document.querySelector('#universal-modal [data-user-display]') || document.querySelector('#universal-modal .profile-modal-name');
      const titleText = el && el.textContent ? el.textContent.trim() : 'Profile';
      if (window.setModalTitle) window.setModalTitle(titleText);
    } catch(_) {}
  }
  // Open profile modal for current user or nicename
  function openProfileModalFor(userSlug) {
    const modal = document.getElementById('universal-modal');
    if (modal) { modal.dataset.profileOpen = '1'; delete modal.dataset.socialOpen; }
    try { if (window.setActiveModal) window.setActiveModal('profile'); } catch(_) {}
    if (window.openModal) window.openModal('<div class="loading">Loading…</div>');
    // Push route for top-level profile
    try {
      const routeTitle = (String(userSlug || 'me') === 'me') ? 'You' : 'Profile';
      if (window.pushModalRoute) window.pushModalRoute({ module:'profile', profileModal:true, user:String(userSlug||'me'), view:'top', title: routeTitle, hash:'#profile/' + String(userSlug || 'me') });
    } catch(_) {}

    fetch(ajax_object.ajax_url, {
      method: 'POST',
      body: new URLSearchParams({ action: 'get_profile_modal', nonce: ajax_object.nonce, user: String(userSlug || 'me') }),
      credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
      if (!data || !data.success) throw new Error('Failed to load profile');
      const body = document.querySelector('#universal-modal .modal-body');
      if (body) body.classList.add('is-loading');
      if (body) {
        body.innerHTML = data.data.html;
        body.scrollTop = 0;
        requestAnimationFrame(() => { requestAnimationFrame(() => { body.classList.remove('is-loading'); }); });
      }
      // Ensure non-edit state on initial open
      try {
        const m = document.getElementById('universal-modal');
        if (m) m.classList.remove('is-profile-edit');
        const slot = document.getElementById('avatar-editor-slot');
        if (slot) { slot.innerHTML=''; slot.style.display='none'; }
      } catch(_) {}
      // Ensure bio visible on initial profile view
      const bioInit = document.querySelector('.profile-modal-bio');
      if (bioInit) { bioInit.style.display = ''; }
      // Set modal title to public display name (for own and other users)
      setHeaderToDisplayName();
      // Decide self vs other based on injected DOM user id
      try {
        const wrap = document.querySelector('#universal-modal .profile-modal-content');
        const viewedId = wrap ? parseInt(wrap.getAttribute('data-user-id') || '0', 10) : 0;
        const selfId = parseInt((window.ajax_object && window.ajax_object.current_user_id) || 0, 10);
        if (viewedId && selfId && viewedId !== selfId) {
          const tabs = document.querySelector('#universal-modal .profile-modal-tabs');
          if (tabs && tabs.parentNode) {
            const actions = document.createElement('div');
            actions.className = 'profile-modal-actions';
            const nameEl = document.querySelector('#universal-modal .profile-modal-name');
            const uname = (nameEl && nameEl.textContent) ? nameEl.textContent.trim() : 'user';
            actions.innerHTML = '\n              <button type="button" class="button profile-action-message" data-action="message" aria-label="Message ' + uname.replace(/"/g, '&quot;') + '">Message</button>\n              <button type="button" class="button profile-action-block" data-action="block" aria-label="Block ' + uname.replace(/"/g, '&quot;') + '">Block</button>\n            ';
            tabs.replaceWith(actions);
          }
        }
      } catch(_) {}

      // For other users: replace tabs with Message/Block actions
      try {
        const isMe = (String(userSlug || 'me') === 'me');
        if (!isMe) {
          const tabs = document.querySelector('#universal-modal .profile-modal-tabs');
          if (tabs && tabs.parentNode) {
            const actions = document.createElement('div');
            actions.className = 'profile-modal-actions';
            const nameEl = document.querySelector('#universal-modal .profile-modal-name');
            const uname = (nameEl && nameEl.textContent) ? nameEl.textContent.trim() : 'user';
            actions.innerHTML = '\n              <button type="button" class="button profile-action-message" data-action="message" aria-label="Message ' + uname.replace(/"/g, '&quot;') + '">Message</button>\n              <button type="button" class="button profile-action-block" data-action="block" aria-label="Block ' + uname.replace(/"/g, '&quot;') + '">Block</button>\n            ';
            tabs.replaceWith(actions);
          }
        }
      } catch(_) {}

      // Push hash for history
      // history handled by route stack

      // Wire tabs to fetch sections
      const sectionWrap = document.getElementById('profile-modal-section');
      document.querySelectorAll('.profile-modal-tabs .profile-tab').forEach(btn => {
        btn.addEventListener('click', () => {
          const section = btn.getAttribute('data-section');
          if (!sectionWrap) return;
          // Pre-toggle is-gear before content loads to avoid flicker
          try { const m = document.getElementById('universal-modal'); if (m) m.classList.toggle('is-gear', section === 'gear'); } catch(_) {}
          const bodyEl = document.querySelector('#universal-modal .modal-body');
          if (bodyEl) bodyEl.classList.add('is-loading');
          sectionWrap.innerHTML = '<div class="loading">Loading…</div>';
          fetch(ajax_object.ajax_url, {
            method:'POST',
            body:new URLSearchParams({ action:'get_profile_section', nonce: ajax_object.nonce, section, user: String(userSlug || 'me') }),
            credentials:'same-origin'
          }).then(r=>r.json()).then(json=>{
            if (json && json.success) {
              sectionWrap.innerHTML = json.data.html;
              // Toggle modal class for Gear to control header visibility via CSS
              try { const m = document.getElementById('universal-modal'); if (m) m.classList.toggle('is-gear', section === 'gear'); } catch(_) {}
              if (bodyEl) {
                bodyEl.scrollTop = 0;
                requestAnimationFrame(() => { requestAnimationFrame(() => { bodyEl.classList.remove('is-loading'); }); });
              }
              // Title per section (Edit Profile/Gear); default to display name for others
              if (section === 'profile') { try { if (window.setModalTitle) window.setModalTitle('Edit Profile'); } catch(_) {} }
              else if (section === 'gear') { try { if (window.setModalTitle) window.setModalTitle('Gear'); } catch(_) {} }
              else { setHeaderToDisplayName(); }
              // Initialize any ACF UI in newly injected content
              if (window.acf && typeof window.acf.doAction === 'function') {
                try { window.acf.doAction('append', sectionWrap); } catch(_) {}
              }
              // Toggle bio visibility for Gear section
              const bio = document.querySelector('.profile-modal-bio');
              if (bio) { bio.style.display = (section === 'gear' || section === 'profile') ? 'none' : ''; }
              // Update title per section
              try { if (window.replaceModalRoute) {
                const map = { gear:'Gear', profile:'Edit Profile', edit:'Edit' };
                window.replaceModalRoute({ module:'profile', profileModal:true, user:String(userSlug||'me'), view:section, title: map[section] || 'Profile', hash:'#profile/' + String(userSlug || 'me') + '/' + section });
              }} catch(_) {}
              // Ensure section title persists after route update
              if (section === 'profile') { try { if (window.setModalTitle) window.setModalTitle('Edit Profile'); } catch(_) {} }
              else if (section === 'gear') { try { if (window.setModalTitle) window.setModalTitle('Gear'); } catch(_) {} }
              else { setHeaderToDisplayName(); }
              // Toggle avatar edit state class on the modal for overlay/clickable control
              const modalEl = document.getElementById('universal-modal');
              if (modalEl) {
                if (section === 'profile') { modalEl.classList.add('is-profile-edit'); }
                else { modalEl.classList.remove('is-profile-edit'); }
              }
              // Bind ACF profile editor AJAX submit if present
              const form = sectionWrap.querySelector('#acf-frontend-profile-form');
              if (form) {
                form.addEventListener('submit', (ev) => {
                  ev.preventDefault();
                  const msg = form.querySelector('#acf-frontend-profile-message');
                  if (msg) { msg.textContent=''; msg.classList.remove('is-success','is-error'); msg.style.display='none'; }
                  const fd = new FormData(form);
                  // Ensure nonce and action are present
                  if (!fd.has('action')) fd.set('action', 'save_acf_frontend_profile');
                  if (!fd.has('acf_frontend_profile_nonce_field')) fd.set('acf_frontend_profile_nonce_field', (ajax_object && ajax_object.nonce) || '');
                  fetch(ajax_object.ajax_url, { method:'POST', body: fd, credentials:'same-origin' })
                    .then(r => r.json())
                    .then(res => {
                      if (msg) {
                        msg.style.display='block';
                        if (res && res.success) {
                          msg.textContent='Profile updated successfully!';
                          msg.classList.add('is-success');
                          msg.classList.remove('is-error');
                          // Update visible names inline
                          const data = res.data || {};
                          const nameEl = document.querySelector('#universal-modal .profile-modal-name');
                          if (nameEl && data.display_name) nameEl.textContent = data.display_name;
                          // Optional: update any elements marked with data-user-display
                          document.querySelectorAll('[data-user-display]').forEach(el => {
                            if (data.display_name) el.textContent = data.display_name;
                          });
                          // Update post meta author display for current user
                          if (ajax_object.current_user_id) {
                            document.querySelectorAll('[data-post-author-id]')
                              .forEach(el => {
                                if (String(el.getAttribute('data-post-author-id')) === String(ajax_object.current_user_id)) {
                                  const nameNode = el.querySelector('[data-author-display]');
                                  if (nameNode && data.display_name) nameNode.textContent = data.display_name;
                                }
                              });
                          }
                        } else {
                          msg.textContent=(res && res.data && res.data.message) || 'Save failed';
                          msg.classList.add('is-error');
                          msg.classList.remove('is-success');
                        }
                      }
                    })
                    .catch(() => {
                      if (msg) { msg.style.display='block'; msg.textContent='Save failed'; msg.classList.add('is-error'); msg.classList.remove('is-success'); }
                    });
                });
              }
              try { history.pushState({ profileModal:true, user:String(userSlug||'me'), section }, '', '#profile/' + String(userSlug||'me') + '/' + section); } catch(_) {}
            } else {
              sectionWrap.innerHTML = '<div class="error">Unable to load section.</div>';
            }
          }).catch(()=>{ sectionWrap.innerHTML = '<div class="error">Unable to load section.</div>'; });
        });
      });
    })
    .catch(() => {
      const body = document.querySelector('#universal-modal .modal-body');
      if (body) { body.innerHTML = '<div class="error">Unable to load profile.</div>'; }
    });
  }

  // Shortcode trigger
  document.body.addEventListener('click', (e) => {
    const btn = e.target.closest('.profile-modal-link');
    if (!btn) return;
    e.preventDefault();
    const user = btn.getAttribute('data-user') || 'me';
    if (user === 'auth') {
      const modal = document.getElementById('universal-modal');
      if (modal) modal.dataset.profileOpen = '1';
      if (window.openModal) window.openModal('<div class="loading">Loading…</div>');
      fetch(ajax_object.ajax_url, { method:'POST', body: new URLSearchParams({ action:'get_auth_modal', nonce: ajax_object.nonce }), credentials:'same-origin' })
        .then(r=>r.json()).then(data=>{
          const body = document.querySelector('#universal-modal .modal-body');
          if (data && data.success && body) { body.innerHTML = data.data.html; }
          try { history.pushState({ profileModal:true, auth:true }, '', '#auth/login'); } catch(_) {}
        });
      return;
    }
    openProfileModalFor(user);
  });

  // Intercept author links site-wide to open Profile modal for that user
  document.body.addEventListener('click', (e) => {
    const a = e.target && (e.target.closest ? e.target.closest('a') : null);
    if (!a) return;
    // If another handler already handled this click, do nothing
    if (e.defaultPrevented) return;
    // Ignore clicks coming from the profile menu trigger itself
    if (a.closest && a.closest('.profile-modal-link')) return;
    // Allow author links in post meta to navigate to archive (no modal)
    if ((a.closest && a.closest('.wp-block-post-author-name')) || a.hasAttribute('data-no-profile-modal')) return;
    // Allow normal navigation for links inside the profile modal header stats
    if (a.closest && a.closest('#universal-modal .profile-header-stats')) return;
    // Respect modifiers / new-tab
    if (e.metaKey || e.ctrlKey || e.altKey || e.shiftKey || a.target === '_blank' || e.button === 1) return;
    try {
      const url = new URL(a.href, window.location.origin);
      // Match /author/{nicename}/ pattern
      if (url.origin === window.location.origin) {
        const parts = url.pathname.replace(/^\/+|\/+$/g, '').split('/');
        // Expect [ 'author', '{nicename}' ]
        if (parts.length >= 2 && (parts[0] === 'author' || parts[0] === 'photographer')) {
          const nicename = (parts[1] || '').toLowerCase();
          if (nicename) {
            e.preventDefault();
            const selfNicename = ((window.ajax_object && window.ajax_object.current_user_nicename) || '').toLowerCase();
            if (selfNicename && nicename === selfNicename) {
              openProfileModalFor('me');
            } else {
              openProfileModalFor(nicename);
            }
          }
        } else if (url.searchParams && url.searchParams.has('author')) {
          const authorId = String(url.searchParams.get('author') || '');
          const selfId = String((window.ajax_object && window.ajax_object.current_user_id) || '');
          if (authorId && selfId && authorId === selfId) {
            e.preventDefault();
            openProfileModalFor('me');
          }
        } else if (url.searchParams && url.searchParams.has('author_name')) {
          const authorName = (String(url.searchParams.get('author_name') || '')).toLowerCase();
          const selfName = ((window.ajax_object && window.ajax_object.current_user_nicename) || '').toLowerCase();
          if (authorName && selfName && authorName === selfName) {
            e.preventDefault();
            openProfileModalFor('me');
          }
        }
      }
    } catch(_) {}
  });

  // Header counters (smaller link) open follow modal
  document.body.addEventListener('click', (e) => {
    const link = e.target && e.target.closest && e.target.closest('.profile-head-link');
    if (!link) return;
    // Allow real links inside modal header stats to navigate normally (e.g., Posts → author archive)
    const isHeaderStat = link.closest && link.closest('#universal-modal .profile-header-stats');
    const href = link.getAttribute('href') || '';
    if (isHeaderStat && href && href !== '#') {
      return;
    }
    e.preventDefault();
    const section = link.getAttribute('data-section');
    const uid = link.getAttribute('data-user-id') || (ajax_object.current_user_id||'0');
    if (typeof openFollowModal === 'function') { openFollowModal(uid, section); }
  });

  // Global delegated handler for all profile modal tabs, regardless of origin
  document.body.addEventListener('click', (e) => {
    const tab = e.target && (e.target.closest ? e.target.closest('.profile-modal-tabs .profile-tab') : null);
    if (!tab) return;
    e.preventDefault();
    const section = tab.getAttribute('data-section') || '';
    // Feed Settings opens feed modal
    if (section === 'feed-settings') {
      if (typeof window.openFeedSettings === 'function') { window.openFeedSettings(); }
      return;
    }
    // If social tab, open Social modal via its own opener to keep responsibilities split
    if (section === 'social') {
      const fake = document.querySelector('.profile-social-link');
      if (fake) fake.click();
      return;
    }
    // Follows opens follow modal (default to 'following' view for current user)
    if (section === 'follows') {
      const uid = (window.ajax_object && window.ajax_object.current_user_id) || 0;
      if (typeof window.openFollowModal === 'function' && uid) { window.openFollowModal(uid, 'following'); }
      return;
    }
    // Otherwise ensure Profile modal is open and go to desired section
    openProfileModalFor('me');
    if (section && section !== 'top') {
      const tryLoad = () => {
        const t = document.querySelector('.profile-modal-tabs .profile-tab[data-section="' + section + '"]');
        if (t) { t.dispatchEvent(new Event('click')); } else { setTimeout(tryLoad, 80); }
      };
      tryLoad();
    }
  });

  // Auth submit handler
  document.body.addEventListener('submit', (e) => {
    const form = e.target.closest('.auth-login-form');
    if (!form) return;
    e.preventDefault();
    const data = new FormData(form);
    data.append('action', 'auth_login');
    fetch(ajax_object.ajax_url, { method:'POST', body: data, credentials:'same-origin' })
      .then(r=>r.json()).then(json=>{
        if (!json || !json.success) {
          alert((json && json.data && json.data.message) || 'Login failed');
          return;
        }
        // After login, close modal and go to homepage
        try { if (window.closeModal) window.closeModal(); } catch(_) {}
        window.location.href = ajax_object.home_url;
      });
  });

  // Back event scoped to Profile modal only
  // No direct back handler; universal stack handles back

  // UI close: if profile modal is open, clear hash and close
  document.body.addEventListener('click', (e) => {
    if (!e.target.classList.contains('modal-close')) return;
    const modal = document.getElementById('universal-modal');
    if (modal && modal.dataset.profileOpen === '1') {
      // Always clear any profile hash so refresh won't reopen modal
      try { history.replaceState(null, '', location.pathname + location.search); } catch(_) {}
      try { if (window.closeModal) window.closeModal(); } catch(_) {}
      delete modal.dataset.profileOpen;
      try { if (window.setActiveModal) window.setActiveModal(''); } catch(_) {}
    }
  });

  // Close behavior with history
  window.addEventListener('popstate', (e) => {
    const modal = document.getElementById('universal-modal');
    if (!modal) return;
    const state = e.state;
    // If profile modal is open and state changed to non-profile, close it
    if (modal.dataset.profileOpen === '1' && (!state || !(state.profileModal || state.module === 'profile'))) {
      delete modal.dataset.profileOpen;
      try { if (window.closeModal) window.closeModal(); } catch(_) {}
      return;
    }
    // If staying within profile modal history, load the correct view/section
    if (state && state.profileModal) {
      const body = document.querySelector('#universal-modal .modal-body');
      if (!body) return;
      const userSlug = String(state.user || 'me');
      if (state.section) {
        // Load section without pushing history again
        const sectionWrap = document.getElementById('profile-modal-section');
        if (sectionWrap) {
          sectionWrap.innerHTML = '<div class="loading">Loading…</div>';
          fetch(ajax_object.ajax_url, {
            method:'POST',
            body:new URLSearchParams({ action:'get_profile_section', nonce: ajax_object.nonce, section: state.section, user: userSlug }),
            credentials:'same-origin'
          }).then(r=>r.json()).then(json=>{
            if (json && json.success) {
              sectionWrap.innerHTML = json.data.html;
              // Toggle modal class for Gear on history navigation
              try { const m2 = document.getElementById('universal-modal'); if (m2) m2.classList.toggle('is-gear', state.section === 'gear'); } catch(_) {}
              if (window.acf && typeof window.acf.doAction === 'function') {
                try { window.acf.doAction('append', sectionWrap); } catch(_) {}
              }
              // Toggle bio visibility for Gear/Profile on history navigation
              const bio2 = document.querySelector('.profile-modal-bio');
              if (bio2) { bio2.style.display = (state.section === 'gear' || state.section === 'profile') ? 'none' : ''; }
              const modalEl2 = document.getElementById('universal-modal');
              if (modalEl2) {
                if (state.section === 'profile') { modalEl2.classList.add('is-profile-edit'); }
                else { modalEl2.classList.remove('is-profile-edit'); }
              }
              // bind form submit if present
              const form = sectionWrap.querySelector('#acf-frontend-profile-form');
              if (form) {
                form.addEventListener('submit', (ev) => {
                  ev.preventDefault();
                  const msg = form.querySelector('#acf-frontend-profile-message');
                  if (msg) { msg.textContent=''; msg.classList.remove('is-success','is-error'); msg.style.display='none'; }
                  const fd = new FormData(form);
                  if (!fd.has('action')) fd.set('action', 'save_acf_frontend_profile');
                  fetch(ajax_object.ajax_url, { method:'POST', body: fd, credentials:'same-origin' })
                    .then(r => r.json())
                    .then(res => {
                      if (msg) {
                        msg.style.display='block';
                        if (res && res.success) { msg.textContent='Profile updated successfully!'; msg.classList.add('is-success'); msg.classList.remove('is-error'); }
                        else { msg.textContent='Save failed'; msg.classList.add('is-error'); msg.classList.remove('is-success'); }
                      }
                    })
                    .catch(() => { if (msg) { msg.style.display='block'; msg.textContent='Save failed'; msg.classList.add('is-error'); msg.classList.remove('is-success'); } });
                });
              }
            } else {
              sectionWrap.innerHTML = '<div class="error">Unable to load section.</div>';
            }
          });
        }
      } else {
        // Load main profile view
        body.innerHTML = '<div class="loading">Loading…</div>';
        fetch(ajax_object.ajax_url, {
          method:'POST',
          body:new URLSearchParams({ action:'get_profile_modal', nonce: ajax_object.nonce, user: userSlug }),
          credentials:'same-origin'
        }).then(r=>r.json()).then(data=>{
          if (data && data.success) {
            body.innerHTML = data.data.html;
            // Set modal header to public display name
            setHeaderToDisplayName();
            // Rebind tab clicks
            const sectionWrap2 = document.getElementById('profile-modal-section');
            // Return to non-edit state on top-level
            try {
              const m2 = document.getElementById('universal-modal');
              if (m2) m2.classList.remove('is-profile-edit');
              const slot2 = document.getElementById('avatar-editor-slot');
              if (slot2) { slot2.innerHTML=''; slot2.style.display='none'; }
            } catch(_) {}
            document.querySelectorAll('.profile-modal-tabs .profile-tab').forEach(btn => {
              btn.addEventListener('click', () => {
                const section = btn.getAttribute('data-section');
                if (!sectionWrap2) return;
                sectionWrap2.innerHTML = '<div class="loading">Loading…</div>';
                fetch(ajax_object.ajax_url, {
                  method:'POST',
                  body:new URLSearchParams({ action:'get_profile_section', nonce: ajax_object.nonce, section, user: userSlug }),
                  credentials:'same-origin'
                }).then(r=>r.json()).then(json=>{
                  if (json && json.success) {
                    sectionWrap2.innerHTML = json.data.html;
                    // Keep header as display name after section load
                    setHeaderToDisplayName();
                    const form = sectionWrap2.querySelector('#acf-frontend-profile-form');
                    if (form) {
                      form.addEventListener('submit', (ev) => {
                        ev.preventDefault();
                        const msg = form.querySelector('#acf-frontend-profile-message');
                        if (msg) { msg.textContent=''; msg.classList.remove('is-success','is-error'); msg.style.display='none'; }
                        const fd = new FormData(form);
                        if (!fd.has('action')) fd.set('action', 'save_acf_frontend_profile');
                        fetch(ajax_object.ajax_url, { method:'POST', body: fd, credentials:'same-origin' })
                          .then(r => r.json())
                          .then(res => {
                            if (msg) {
                              msg.style.display='block';
                              if (res && res.success) {
                                msg.textContent='Profile updated successfully!'; msg.classList.add('is-success'); msg.classList.remove('is-error');
                                const data = res.data || {};
                                const nameEl = document.querySelector('#universal-modal .profile-modal-name');
                                if (nameEl && data.display_name) nameEl.textContent = data.display_name;
                                document.querySelectorAll('[data-user-display]').forEach(el => {
                                  if (data.display_name) el.textContent = data.display_name;
                                });
                              } else {
                                msg.textContent='Save failed'; msg.classList.add('is-error'); msg.classList.remove('is-success');
                              }
                            }
                          })
                          .catch(() => { if (msg) { msg.style.display='block'; msg.textContent='Save failed'; msg.classList.add('is-error'); msg.classList.remove('is-success'); } });
                      });
                    }
                  } else {
                    sectionWrap2.innerHTML = '<div class="error">Unable to load section.</div>';
                  }
                });
              });
            });
            // If viewing another user on top-level reload, replace tabs with Message/Block
            try {
              const isMe = (String(userSlug || 'me') === 'me');
              if (!isMe) {
                const tabs = document.querySelector('#universal-modal .profile-modal-tabs');
                if (tabs && tabs.parentNode) {
                  const actions = document.createElement('div');
                  actions.className = 'profile-modal-actions';
                  const nameEl = document.querySelector('#universal-modal .profile-modal-name');
                  const uname = (nameEl && nameEl.textContent) ? nameEl.textContent.trim() : 'user';
                  actions.innerHTML = '\n              <button type="button" class="button profile-action-message" data-action="message" aria-label="Message ' + uname.replace(/"/g, '&quot;') + '">Message</button>\n              <button type="button" class="button profile-action-block" data-action="block" aria-label="Block ' + uname.replace(/"/g, '&quot;') + '">Block</button>\n            ';
                  tabs.replaceWith(actions);
                }
              }
            } catch(_) {}
          } else {
            body.innerHTML = '<div class="error">Unable to load profile.</div>';
          }
        });
      }
    }
  });

  // After full page reload (ACF acf_form return), reopen profile modal based on hash
  window.addEventListener('load', () => {
    const h = String(window.location.hash || '');
    if (h.startsWith('#profile/')) {
      const parts = h.replace('#profile/', '').split('/');
      const user = parts[0] || 'me';
      const section = parts[1] || '';
      openProfileModalFor(user);
      // Section will be loaded by tab click wiring; optionally trigger it
      if (section) {
        const tryLoad = () => {
          const btn = document.querySelector('.profile-modal-tabs .profile-tab[data-section="' + section + '"]');
          if (btn) { btn.click(); }
          else { setTimeout(tryLoad, 100); }
        };
        tryLoad();
      }
    }
  });
});



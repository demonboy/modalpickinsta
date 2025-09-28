document.addEventListener('DOMContentLoaded', () => {
  // Open Social modal from Profile view
  document.body.addEventListener('click', (e) => {
    const btn = e.target && (e.target.closest ? e.target.closest('.profile-social-link') : null);
    if (!btn) return;
    e.preventDefault();
    const modal = document.getElementById('universal-modal');
    const wasProfile = !!(modal && modal.dataset.profileOpen === '1');
    if (modal) { modal.dataset.socialOpen = '1'; }
    try { if (window.setActiveModal) window.setActiveModal('social'); } catch(_) {}
    if (window.openModal) window.openModal('<div class="loading">Loading…</div>');
    // Ensure there is always a baseline route beneath Social so Back remains inside the modal
    try {
      if (!wasProfile) {
        // Seed Profile top as baseline when not coming from Profile
        window.pushModalRoute && window.pushModalRoute({ module:'profile', profileModal:true, user:'me', view:'top', title:'Profile', hash:'#profile/me' });
      }
      // Now push Social on top
      window.pushModalRoute && window.pushModalRoute({ module:'social', socialModal:true, view:'top', title:'Social', hash:'#profile/me/social' });
    } catch(_) {}
    fetch(ajax_object.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: new URLSearchParams({ action: 'get_social_modal', nonce: ajax_object.nonce })
    }).then(r=>r.json()).then(json=>{
      const body = document.querySelector('#universal-modal .modal-body');
      if (body) body.classList.add('is-loading');
      if (json && json.success && body) {
        body.innerHTML = json.data.html;
        body.scrollTop = 0;
        requestAnimationFrame(() => { requestAnimationFrame(() => { body.classList.remove('is-loading'); }); });
        if (window.setModalTitle) window.setModalTitle('Social');
        if (window.acf && typeof window.acf.doAction === 'function') {
          try { window.acf.doAction('append', body); } catch(_) {}
        }
        // Build compact selection panel with checkboxes and up/down order (no DOM replacement)
        const wrap = body.querySelector('#social-fields-wrap');
        if (wrap) {
          const selectedHidden = body.querySelector('#social-selected');
          const heading = body.querySelector('.social-reorder-heading');
          const activeRow = body.querySelector('#social-icons-active');
          const inactiveRow = body.querySelector('#social-icons-inactive');
          const fieldNames = ['instagram','threads','x','facebook','tiktok','youtube','vimeo','flickr','fivehundredpx','pinterest','reddit','linkedin','behance','dribbble','deviantart','tumblr','bluesky','mastodon','vsco','substack'];

          const readState = () => {
            const items = [];
            fieldNames.forEach(name => {
              const el = wrap.querySelector('[data-name="' + name + '"]');
              if (!el) return;
              const input = el.querySelector('input,select,textarea');
              const labelEl = el.querySelector('.acf-label label');
              const label = labelEl ? labelEl.textContent.trim() : name;
              const val = input ? String(input.value||'').trim() : '';
              items.push({ key:name, label, value:val });
            });
            return items;
          };

          const getSelected = (items) => {
            // Backend edit view: show ALL filled platforms, not capped; order by saved CSV if present
            const filled = items.filter(i=>i.value).map(i=>i.key);
            const savedCsv = (wrap.getAttribute('data-selected')||'').split(',').map(s=>s.trim()).filter(Boolean);
            let ordered = [];
            if (savedCsv.length) {
              savedCsv.forEach(k => { if (filled.includes(k)) ordered.push(k); });
              filled.forEach(k => { if (!ordered.includes(k)) ordered.push(k); });
            } else {
              ordered = filled.slice();
            }
            return ordered; // no cap here; front-end caps to 6
          };

          // Cache icon SVGs to avoid layout thrash per keystroke
          const iconCache = {};
          const inlineSvgFor = (key) => {
            if (iconCache[key]) return Promise.resolve(iconCache[key]);
            const src = (ajax_object && ajax_object.theme_uri ? ajax_object.theme_uri : '') + '/assets/icons/social/' + key + '.svg';
            return fetch(src, { credentials:'same-origin' })
              .then(r => r.ok ? r.text() : '')
              .then(txt => { iconCache[key] = (txt || '').replace(/\n|\r|\t/g,''); return iconCache[key]; })
              .catch(() => '');
          };

          const renderLive = () => {
            const items = readState();
            const selected = getSelected(items);
            if (selectedHidden) selectedHidden.value = selected.slice(0,9).join(',');

            // Clear rows
            if (activeRow) activeRow.innerHTML = '';
            if (inactiveRow) inactiveRow.innerHTML = '';

            const filled = items.filter(i=>i.value).map(i=>i.key);
            const activeSet = new Set(selected.slice(0,9));

            const buildIcon = (key, row) => inlineSvgFor(key).then(svg => {
              const icon = document.createElement('div');
              icon.className = 'social-icon social-icon--' + key;
              icon.setAttribute('draggable','true');
              icon.dataset.platform = key;
              icon.innerHTML = (svg || '').replace(/\n|\r|\t/g,'');
              row.appendChild(icon);
            });

            const builds = [];
            selected.slice(0,9).forEach(k => { if (activeRow) builds.push(buildIcon(k, activeRow)); });
            filled.filter(k => !activeSet.has(k)).forEach(k => { if (inactiveRow) builds.push(buildIcon(k, inactiveRow)); });

            Promise.all(builds).then(()=>{
              // Toggle heading visibility based on whether any icon exists
              if (heading) heading.hidden = !(activeRow && activeRow.children.length) && !(inactiveRow && inactiveRow.children.length);
              let dragEl = null;
              const onDragStart = e => { const el=e.target.closest('.social-icon'); if(!el) return; dragEl=el; e.dataTransfer.effectAllowed='move'; };
              const onDragOver = row => e => { if(!dragEl) return; e.preventDefault(); const over=e.target.closest('.social-icon'); if(!over||over===dragEl) return; const rect=over.getBoundingClientRect(); const after=(e.clientX-rect.left)/rect.width>.5; over.parentNode.insertBefore(dragEl, after ? over.nextSibling : over); };
              const onDragEnd = () => {
                if (!dragEl) return; dragEl=null;
                // Enforce cap 9 on active
                if (activeRow && inactiveRow) {
                  while (activeRow.children.length > 9) {
                    inactiveRow.appendChild(activeRow.lastElementChild);
                  }
                  const order=[...activeRow.querySelectorAll('.social-icon')].map(i=>i.dataset.platform);
                  if (selectedHidden) selectedHidden.value = order.join(',');
                }
                // Refresh front-end blocks
                const blocks = document.querySelectorAll('[data-social-icons="1"]');
                blocks.forEach(el => {
                  const uid = el.getAttribute('data-user-id') || '';
                  fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: new URLSearchParams({ action:'render_social_icons', user_id: uid }) })
                    .then(r=>r.json()).then(j=>{ if (j && j.success && typeof j.data.html === 'string') { el.outerHTML = j.data.html; } });
                });
              };
              [activeRow,inactiveRow].forEach(row => {
                if (!row) return;
                row.addEventListener('dragstart', onDragStart);
                row.addEventListener('dragover', onDragOver(row));
                row.addEventListener('dragend', onDragEnd);
              });
            });
          };

          // Initial render and debounced input changes to prevent bounce
          renderLive();
          let t;
          wrap.addEventListener('input', (e) => {
            if (!(e.target && (e.target.matches('input,select,textarea')))) return;
            clearTimeout(t);
            t = setTimeout(renderLive, 200);
          });
        }
        // focus form
        const form = body.querySelector('#acf-social-form');
        if (form) form.querySelector('input,select,textarea')?.focus?.();
        // Replace the top Social route with final state (avoid stacking duplicates)
        try { window.replaceModalRoute && window.replaceModalRoute({ module:'social', socialModal:true, view:'top', title:'Social', hash:'#profile/me/social' }); } catch(_) {}
      } else if (body) {
        body.innerHTML = '<div class="error">Unable to load social profiles.</div>';
        body.classList.remove('is-loading');
      }
    }).catch(()=>{
      const body = document.querySelector('#universal-modal .modal-body');
      if (body) { body.innerHTML = '<div class="error">Unable to load social profiles.</div>'; requestAnimationFrame(() => { requestAnimationFrame(() => { body.classList.remove('is-loading'); }); }); }
    });
  });

  // Tabs inside Social modal now handled centrally in profile-modal.js

  // Submit handler (AJAX)
  document.body.addEventListener('submit', (e) => {
    const form = e.target && e.target.closest ? e.target.closest('#acf-social-form') : null;
    if (!form) return;
    e.preventDefault();
    const msg = form.querySelector('#acf-social-message');
    if (msg) { msg.style.display='none'; msg.textContent=''; msg.classList.remove('is-success','is-error'); }
    const fd = new FormData(form);
    fd.append('action', 'save_social_profiles');
    fd.append('nonce', ajax_object.nonce);
    fetch(ajax_object.ajax_url, { method:'POST', body: fd, credentials:'same-origin' })
      .then(r=>r.json())
      .then(res => {
        if (msg) {
          msg.style.display='block';
          if (res && res.success) { msg.textContent='Saved'; msg.classList.add('is-success'); msg.classList.remove('is-error'); }
          else { msg.textContent=(res && res.data && res.data.message) || 'Save failed'; msg.classList.add('is-error'); msg.classList.remove('is-success'); }
        }
        // Refresh any front-end [social_icons] blocks without hard reload
        if (res && res.success) {
          const blocks = document.querySelectorAll('[data-social-icons="1"]');
          blocks.forEach(el => {
            const uid = el.getAttribute('data-user-id') || '';
            fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: new URLSearchParams({ action:'render_social_icons', user_id: uid }) })
              .then(r=>r.json()).then(j=>{ if (j && j.success && typeof j.data.html === 'string') { el.outerHTML = j.data.html; } });
          });
        }
      })
      .catch(() => { if (msg){ msg.style.display='block'; msg.textContent='Save failed'; msg.classList.add('is-error'); msg.classList.remove('is-success'); } });
  });

  // Back handling via universal event, scoped to Social
  // Ensure back stays inside modal even if baseline route wasn't present originally
  window.addEventListener('popstate', (e) => {
    const state = e.state;
    const modal = document.getElementById('universal-modal');
    if (!modal) return;
    const body = modal.querySelector('.modal-body');
    // If landing on a Profile route, make sure Profile content is loaded
    if (state && (state.profileModal || state.module === 'profile')) {
      if (body) { body.innerHTML = '<div class="loading">Loading…</div>'; }
      fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: new URLSearchParams({ action:'get_profile_modal', nonce: ajax_object.nonce, user: 'me' }) })
        .then(r=>r.json()).then(data=>{
          const b = document.querySelector('#universal-modal .modal-body');
          if (data && data.success && b) {
            b.innerHTML = data.data.html;
            try { if (window.setActiveModal) window.setActiveModal('profile'); } catch(_) {}
            try { modal.dataset.profileOpen = '1'; delete modal.dataset.socialOpen; } catch(_) {}
          }
        });
      return;
    }
    // If stack becomes empty and modal was closed by universal, reopen a baseline Profile instead of exiting
    if (!state) {
      if (modal.style.display !== 'flex') {
        if (window.openModal) window.openModal('<div class="loading">Loading…</div>');
        try { window.pushModalRoute && window.pushModalRoute({ module:'profile', profileModal:true, user:'me', view:'top', title:'Profile', hash:'#profile/me' }); } catch(_) {}
        fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: new URLSearchParams({ action:'get_profile_modal', nonce: ajax_object.nonce, user: 'me' }) })
          .then(r=>r.json()).then(data=>{
            const b = document.querySelector('#universal-modal .modal-body');
            if (data && data.success && b) {
              b.innerHTML = data.data.html;
              try { if (window.setActiveModal) window.setActiveModal('profile'); } catch(_) {}
              try { modal.dataset.profileOpen = '1'; delete modal.dataset.socialOpen; } catch(_) {}
            }
          });
      }
    }
  });

  // Ensure history navigation to/from Social restores appropriate view
  // No popstate logic; stack drives navigation
});



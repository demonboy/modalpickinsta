(function(){
  function init(){
  const openFollowModal = (userId, view) => {
    if (window.openModal) window.openModal('<div class="loading">Loading…</div>');
    try { if (window.pushModalRoute) window.pushModalRoute({ module:'follow', view: view||'followers', title: view==='following'?'Following':(view==='blocked'?'Blocked':(view==='followers'?'Followers':'Follows')) }); } catch(_){ }
    fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: new URLSearchParams({ action:'get_follow_modal', user_id:String(userId), view: view||'followers' }) })
      .then(r=>r.json()).then(json=>{
        const body = document.querySelector('#universal-modal .modal-body');
        if (body) body.classList.add('is-loading');
        if (json && json.success && body) {
          body.innerHTML = json.data.html;
          body.scrollTop = 0;
          requestAnimationFrame(() => { requestAnimationFrame(() => { body.classList.remove('is-loading'); }); });
          if (window.setModalTitle) window.setModalTitle(view==='following'?'Following':(view==='blocked'?'Blocked':(view==='followers'?'Followers':'Follows')));
          const containers = {
            followers: body.querySelector('#follow-list-followers'),
            following: body.querySelector('#follow-list-following')
          };
          const toggles = {
            followers: body.querySelector('#followers-toggle'),
            following: body.querySelector('#following-toggle')
          };
          const loadMoreBtns = {
            followers: body.querySelector('#follow-load-more-followers'),
            following: body.querySelector('#follow-load-more-following')
          };
          const loaded = { followers:false, following:false };
          const pages  = { followers:1, following:1 };
          const header = document.querySelector('#universal-modal .profile-modal-stats');
          const setHeaderCounts = (counts) => {
            if (!header) return;
            if (counts && typeof counts.followers === 'number') {
              const fc = header.querySelector('[data-followers-count]'); if (fc) fc.textContent = String(counts.followers);
            }
            if (counts && typeof counts.following === 'number') {
              const gc = header.querySelector('[data-following-count]'); if (gc) gc.textContent = String(counts.following);
            }
          };
          const refreshHeaderCounts = () => {
            fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: new URLSearchParams({ action:'get_follow_counts', user_id:String(userId) }) })
              .then(r=>r.json()).then(res=>{ if (res && res.success) setHeaderCounts(res.data); });
          };
          const load = (type, page=1, search='') => {
            const bodyEl = document.querySelector('#universal-modal .modal-body');
            const target = containers[type];
            const btn = loadMoreBtns[type];
            if (!target) return;
            if (bodyEl) bodyEl.classList.add('is-loading');
            fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: new URLSearchParams({ action:'get_follow_list', type: type, user_id:String(userId), page:String(page), search }) })
            .then(r=>r.json()).then(res=>{
              if (res && res.success) {
                if (page === 1) { target.innerHTML = res.data.html; }
                else {
                  const temp = document.createElement('div'); temp.innerHTML = res.data.html;
                  while (temp.firstChild) target.appendChild(temp.firstChild);
                }
                loaded[type] = true;
                pages[type] = res.data.page || page;
                if (btn) {
                  btn.style.display = res.data.has_more ? 'inline-block' : 'none';
                  btn.dataset.page = String(res.data.page || page);
                  btn.dataset.search = search;
                }
              }
            })
            .finally(()=>{
              if (bodyEl) {
                bodyEl.scrollTop = 0;
                requestAnimationFrame(() => { requestAnimationFrame(() => { bodyEl.classList.remove('is-loading'); }); });
              }
            });
          };

          function setExpanded(type, expand){
            const tog = toggles[type];
            const panel = containers[type];
            if (!tog || !panel) return;
            tog.setAttribute('aria-expanded', expand ? 'true' : 'false');
            panel.hidden = !expand;
            if (expand && !loaded[type]) { load(type, 1, ''); }
          }

          // Bind toggle buttons (tap-friendly)
          ['followers','following'].forEach((type)=>{
            const tog = toggles[type];
            if (!tog) return;
            tog.addEventListener('click', ()=>{
              const expanded = tog.getAttribute('aria-expanded') === 'true';
              setExpanded(type, !expanded);
            });
          });

          // Bind load-more buttons per section
          ['followers','following'].forEach((type)=>{
            const btn = loadMoreBtns[type];
            if (!btn) return;
            btn.addEventListener('click', (e)=>{
              e.preventDefault();
              const next = (parseInt(btn.dataset.page||'1',10) + 1) || 2;
              const search = btn.dataset.search || '';
              load(type, next, search);
            });
          });

          // Initial state depending on requested view
          if (view === 'followers') { setExpanded('followers', true); try { if (window.setModalTitle) window.setModalTitle('Followers'); } catch(_){} }
          else if (view === 'following') { setExpanded('following', true); try { if (window.setModalTitle) window.setModalTitle('Following'); } catch(_){} }
          else { /* Follows tab: both collapsed */ try { if (window.setModalTitle) window.setModalTitle('Follows'); } catch(_){} }

          body.addEventListener('click', (e) => {
            const tab = e.target && e.target.closest && e.target.closest('.follow-tab');
            if (tab) {
              e.preventDefault();
              const v = tab.getAttribute('data-view');
              openFollowModal(userId, v);
              return;
            }
            const blockedLink = e.target && e.target.closest && e.target.closest('.follow-blocked-link');
            if (blockedLink) { e.preventDefault(); openFollowModal(userId, 'blocked'); return; }

            const unfollowBtn = e.target && e.target.closest && e.target.closest('.btn-unfollow');
            if (unfollowBtn) {
              e.preventDefault();
              const row = unfollowBtn.closest('.follow-row');
              const targetId = row && row.getAttribute('data-user-id');
              if (!targetId) return;
              const fd = new URLSearchParams({ action:'toggle_follow', nonce: ajax_object.nonce, following_id: String(targetId) });
              fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: fd })
                .then(r=>r.json()).then(res=>{
                  if (res && res.success) {
                    row.remove();
                    refreshHeaderCounts();
                    // Also update any visible follow shortcode counters for this author
                    const newCount = (res.data && typeof res.data.followers_count === 'number') ? res.data.followers_count : null;
                    if (newCount !== null) {
                      document.querySelectorAll('.follow-author-btn[data-author-id="' + String(targetId) + '"]').forEach(btn => {
                        const cnt = btn.querySelector('.follow-count');
                        if (cnt) cnt.textContent = String(newCount);
                        btn.classList.remove('is-following');
                      });
                    }
                  }
                });
              return;
            }

            const blockBtn = e.target && e.target.closest && e.target.closest('.btn-block');
            if (blockBtn) {
              e.preventDefault();
              const row = blockBtn.closest('.follow-row');
              const targetId = row && row.getAttribute('data-user-id');
              if (!targetId) return;
              const fd = new URLSearchParams({ action:'block_user', nonce: ajax_object.nonce, blocked_id: String(targetId) });
              fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: fd })
                .then(r=>r.json()).then(res=>{ if (res && res.success) { row.remove(); setHeaderCounts({ followers: res.data.followers_count }); } });
              return;
            }

            const unblockBtn = e.target && e.target.closest && e.target.closest('.btn-unblock');
            if (unblockBtn) {
              e.preventDefault();
              const row = unblockBtn.closest('.follow-row');
              const targetId = row && row.getAttribute('data-user-id');
              if (!targetId) return;
              const fd = new URLSearchParams({ action:'unblock_user', nonce: ajax_object.nonce, blocked_id: String(targetId) });
              fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: fd })
                .then(r=>r.json()).then(res=>{ if (res && res.success) { row.remove(); } });
              return;
            }

            // existing single load-more path removed; handled per-section above
          });

          const searchInput = body.querySelector('#follow-search-input');
          if (searchInput) {
            let t; searchInput.addEventListener('input', ()=>{
              clearTimeout(t);
              t=setTimeout(()=>{
                // Apply search to whichever section is expanded; default followers
                const followersExpanded = toggles.followers && toggles.followers.getAttribute('aria-expanded') === 'true';
                const targetType = followersExpanded ? 'followers' : 'following';
                load(targetType, 1, searchInput.value||'');
              }, 250);
            });
          }
        }
      });
  };
  // Expose opener globally so Profile tabs can trigger the Follows modal
  try { window.openFollowModal = openFollowModal; } catch(_){}

  // Header counters (small link) -> open modal
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
    const userId = link.getAttribute('data-user-id') || (ajax_object.current_user_id||'0');
    if (!userId) return;
    if (section === 'following' || section === 'followers') {
      openFollowModal(userId, section);
    } else if (section === 'blocked') {
      openFollowModal(userId, 'blocked');
    }
  });

  // Shortcode follow button (toggle via AJAX, update icon + count)
  document.body.addEventListener('click', (e) => {
    const btn = e.target && e.target.closest && e.target.closest('.follow-author-btn');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    // Prevent rapid double clicks while a request is in-flight
    if (btn.classList.contains('is-busy')) return;
    btn.classList.add('is-busy');
    const authorId = btn.getAttribute('data-author-id');
    if (!authorId) return;
    // Optimistic UI: toggle all instances immediately
    const currentlyFollowing = btn.classList.contains('is-following');
    const optimisticFollowing = !currentlyFollowing;
    const statSel = '.profile-header-stats a.profile-head-link[data-section="followers"][data-user-id="' + String(authorId) + '"] .stat-count';
    document.querySelectorAll('.follow-author-btn[data-author-id="' + String(authorId) + '"]').forEach(function(el){
      el.classList.toggle('is-following', optimisticFollowing);
      el.setAttribute('aria-pressed', optimisticFollowing ? 'true' : 'false');
      const label = el.querySelector && el.querySelector('.follow-label');
      if (label) {
        label.textContent = optimisticFollowing ? 'FOLLOWING' : 'FOLLOW';
      } else if (el.classList && (el.classList.contains('wp-element-button') || el.classList.contains('wp-block-button__link'))) {
        // Only text-variant (core button) should have its inner text replaced
        el.textContent = optimisticFollowing ? 'FOLLOWING' : 'FOLLOW';
      }
      const c = el.querySelector && el.querySelector('.follow-count');
      if (c) {
        const n0 = parseInt(c.textContent||'0',10) || 0;
        const n1 = n0 + (optimisticFollowing ? 1 : -1);
        c.textContent = String(Math.max(0, n1));
        if (n1 <= 0) { c.classList.add('is-zero'); } else { c.classList.remove('is-zero'); }
      }
    });
    // Optimistically adjust header followers stat if present
    document.querySelectorAll(statSel).forEach(function(el){
      const cur = parseInt(el.textContent||'0',10) || 0;
      const nxt = cur + (optimisticFollowing ? 1 : -1);
      el.textContent = String(Math.max(0, nxt));
    });
    const fd = new URLSearchParams({ action:'toggle_follow', nonce: (window.ajax_object && ajax_object.nonce) || '', following_id: String(authorId) });
    fetch((window.ajax_object && ajax_object.ajax_url) || '/wp-admin/admin-ajax.php', { method:'POST', credentials:'same-origin', body: fd })
      .then(r=>r.json()).then(res=>{
        if (!res || !res.success) return;
        const nowFollowing = !!(res.data && (res.data.following ?? res.data.is_following));
        const newCount = (res.data && typeof res.data.followers_count === 'number') ? res.data.followers_count : null;
        // Update ALL follow buttons targeting this author
        document.querySelectorAll('.follow-author-btn[data-author-id="' + String(authorId) + '"]').forEach(function(el){
          // Only reconcile if different from optimistic state to avoid flicker
          const curFollowing = el.classList.contains('is-following');
          if (curFollowing !== nowFollowing) {
            el.classList.toggle('is-following', nowFollowing);
            el.setAttribute('aria-pressed', nowFollowing ? 'true' : 'false');
          }
          // Only replace text for the text button variant (has core button classes)
          const label = el.querySelector && el.querySelector('.follow-label');
          if (label) {
            if (label.textContent !== (nowFollowing ? 'FOLLOWING' : 'FOLLOW')) {
              label.textContent = nowFollowing ? 'FOLLOWING' : 'FOLLOW';
            }
          } else if (el.classList && (el.classList.contains('wp-element-button') || el.classList.contains('wp-block-button__link'))) {
            if (el.textContent !== (nowFollowing ? 'FOLLOWING' : 'FOLLOW')) {
              el.textContent = nowFollowing ? 'FOLLOWING' : 'FOLLOW';
            }
          }
          const c = el.querySelector && el.querySelector('.follow-count');
          if (c && newCount !== null) {
            c.textContent = String(newCount);
            if (newCount === 0) { c.classList.add('is-zero'); } else { c.classList.remove('is-zero'); }
          }
        });
        // Reconcile header followers stat with authoritative count
        if (newCount !== null) {
          document.querySelectorAll(statSel).forEach(function(el){ el.textContent = String(newCount); });
        }
      })
      .catch(function(){ /* no-op; optimistic UI already updated */ })
      .finally(function(){ btn.classList.remove('is-busy'); });
  }, true);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();



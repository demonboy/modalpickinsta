(function(){
  'use strict';

  function qs(s, r){ return (r||document).querySelector(s); }
  function qsa(s, r){ return (r||document).querySelectorAll(s); }

  function openFeedModal(){
    if (window.openModal) window.openModal('<div class="loading">Loading…</div>');
    fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: new URLSearchParams({ action:'get_feed_modal' }) })
      .then(r=>r.json()).then(json=>{
        const body = qs('#universal-modal .modal-body');
        if (body) body.classList.add('is-loading');
        if (json && json.success && body) {
          body.innerHTML = json.data.html;
          body.scrollTop = 0;
          requestAnimationFrame(() => { requestAnimationFrame(() => { body.classList.remove('is-loading'); }); });
          try { if (window.setModalTitle) window.setModalTitle('Feed Settings'); } catch(_){ }
          bind(body, json.data);
        }
      });
  }

  function getFormData(root){
    // Assemble params per 6-step UI → URL params
    const uYes = (qs('input[name="u_yesno"]:checked', root)||{}).value === 'yes';
    const u_scope = uYes ? ((qs('input[name="u_scope"]:checked', root)||{}).value || 'everyone') : 'everyone';
    const likesYes = (qs('input[name="likes_yesno"]:checked', root)||{}).value === 'yes';
    const likes_order = likesYes ? ((qs('input[name="likes_order"]:checked', root)||{}).value || 'most') : '';
    let date_order = (qs('input[name="date_order"]:checked', root)||{}).value || 'latest';
    // When sorting by likes, omit date completely so backend does not apply a date order
    if (likesYes) { date_order = ''; }
    const catsYes = (qs('input[name="cats_yesno"]:checked', root)||{}).value === 'yes';
    // If Option 4 = No, clear both preferred and excluded
    const cats = catsYes ? ((qs('#feed-cats-selected', root)||{}).value || '') : '';
    // Always read excluded from hidden input, irrespective of the Yes/No toggle
    const excludeYes = (qs('input[name="exclude_yesno"]:checked', root)||{}).value === 'yes';
    const cats_exclude = catsYes ? ((qs('#feed-cats-exclude-selected', root)||{}).value || '') : '';
    const ptypeYes = (qs('input[name="ptype_yesno"]:checked', root)||{}).value === 'yes';
    const ptype = ptypeYes ? ((qs('input[name="ptype"]:checked', root)||{}).value || '') : '';
    return { u_scope, likes_order, date_order, cats, cats_exclude, ptype };
  }

  function applySoft(root){
    const { u_scope, likes_order, date_order, cats, cats_exclude, ptype } = getFormData(root);
    const url = new URL(location.href);
    // Build URL params per new schema
    url.searchParams.delete('view');
    url.searchParams.set('u_scope', u_scope);
    if (likes_order) url.searchParams.set('likes', likes_order); else url.searchParams.delete('likes');
    if (date_order) url.searchParams.set('date', date_order); else url.searchParams.delete('date');
    if (cats) url.searchParams.set('cats', cats); else url.searchParams.delete('cats');
    if (cats_exclude) url.searchParams.set('cats_exclude', cats_exclude); else url.searchParams.delete('cats_exclude');
    if (ptype) url.searchParams.set('ptype', ptype); else url.searchParams.delete('ptype');

    // Soft-replace Query Loop content
    // Save current selections as default, then soft-apply
    const fd = new URLSearchParams({ action:'save_feed_defaults', nonce:(ajax_object&&ajax_object.nonce)||'', mode:'', u_scope, likes:likes_order, date:date_order, cats, cats_exclude, ptype });
    fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: fd })
      .finally(function(){
        fetch(url.toString(), { credentials:'same-origin' })
          .then(r=>r.text()).then(html=>{
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newQuery = doc.querySelector('.wp-block-query');
            const curQuery = document.querySelector('.wp-block-query');
            if (newQuery && curQuery) {
              curQuery.innerHTML = newQuery.innerHTML;
              try { history.pushState(null, '', url.toString()); } catch(_){ }
            } else {
              location.href = url.toString();
            }
          });
      });
  }

  function saveDefaults(root, silent){
    const { u_scope, likes_order, date_order, cats, cats_exclude, ptype } = getFormData(root);
    // Persist all selections as the new defaults
    const mode = ''; // legacy unused
    const fd = new URLSearchParams({ action:'save_feed_defaults', nonce:(ajax_object&&ajax_object.nonce)||'', mode, u_scope, likes:likes_order, date:date_order, cats, cats_exclude, ptype });
    fetch(ajax_object.ajax_url, { method:'POST', credentials:'same-origin', body: fd })
      .then(r=>r.json()).then(res=>{
        if (!silent) {
          const live = qs('.feed-modal .sr-only', root) || qs('#universal-modal .sr-only');
          if (live) live.textContent = (res && res.success) ? 'Saved' : 'Save failed';
        }
        if (!silent) { applySoft(root); }
      });
  }

  // New category chips UI (Available/Preferred/Excluded)
  function renderCategoryChips(root, cats, defaults){
    function setExcludeToggle(on){
      const yes = qs('input[name="exclude_yesno"][value="yes"]', root);
      const no  = qs('input[name="exclude_yesno"][value="no"]', root);
      const bodyEl = root.querySelector('[data-body="exclude"]');
      if (on) { if (yes) yes.checked = true; if (bodyEl) bodyEl.hidden = false; }
      else { if (no) no.checked = true; /* body can remain hidden */ }
    }
    const prefHidden = qs('#feed-cats-selected', root);
    const exclHidden = qs('#feed-cats-exclude-selected', root);
    const avail = qs('#cats-available', root);
    const pref  = qs('#cats-preferred', root);
    const excl  = qs('#cats-excluded', root);
    if (!prefHidden || !exclHidden || !avail || !pref || !excl) return;

    const preferredIds = new Set((prefHidden.value||'').split(',').filter(Boolean).map(Number));
    const excludedIds  = new Set((exclHidden.value||'').split(',').filter(Boolean).map(Number));

    function syncHidden(){
      const getIds = (bucket)=>Array.prototype.map.call(bucket.querySelectorAll('.cat-chip'),b=>Number(b.dataset.id));
      prefHidden.value = getIds(pref).join(',');
      exclHidden.value = getIds(excl).join(',');
    }

    function makeChip(item){
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'cat-chip';
      chip.textContent = item.name;
      chip.dataset.id = String(item.id);
      chip.draggable = true;
      // Tap moves chip to next logical bucket: avail->pref, pref->avail, excl->avail
      chip.addEventListener('click', (e)=>{
        const cur = e.currentTarget.parentElement;
        if (cur === avail) { pref.appendChild(chip); excludedIds.delete(item.id); preferredIds.add(item.id); }
        else if (cur === pref) { avail.appendChild(chip); preferredIds.delete(item.id); }
        else if (cur === excl) { avail.appendChild(chip); excludedIds.delete(item.id); }
        syncHidden();
        // Maintain exclude toggle state
        if (e.currentTarget.parentElement === excl) {
          // moved out of excl; if empty now, turn off
          if (!excl.querySelector('.cat-chip')) setExcludeToggle(false);
        }
      });
      // Drag and drop (desktop)
      chip.addEventListener('dragstart', (e)=>{ e.dataTransfer.setData('text/plain', String(item.id)); });
      return chip;
    }

    function allowDrop(bucket){
      bucket.addEventListener('dragover', (e)=>{ e.preventDefault(); });
      bucket.addEventListener('drop', (e)=>{
        e.preventDefault();
        const id = Number(e.dataTransfer.getData('text/plain'));
        const chip = root.querySelector('.cat-chip[data-id="' + String(id) + '"]');
        if (!chip) return;
        // Enforce mutual exclusivity
        if (bucket === pref) { excludedIds.delete(id); preferredIds.add(id); }
        else if (bucket === excl) { preferredIds.delete(id); excludedIds.add(id); setExcludeToggle(true); }
        else { preferredIds.delete(id); excludedIds.delete(id); }
        bucket.appendChild(chip);
        syncHidden();
      });
    }

    // Initial layout
    avail.innerHTML = pref.innerHTML = excl.innerHTML = '';
    cats.forEach(item => {
      const chip = makeChip(item);
      if (preferredIds.has(item.id)) pref.appendChild(chip);
      else if (excludedIds.has(item.id)) excl.appendChild(chip);
      else avail.appendChild(chip);
    });

    // Enable DnD
    [avail, pref, excl].forEach(allowDrop);
    syncHidden();
  }

  function getParamsFromUrl(){
    const url = new URL(location.href);
    const p = Object.create(null);
    p.u_scope = url.searchParams.get('u_scope') || '';
    p.likes_order = url.searchParams.get('likes') || '';
    p.date_order = url.searchParams.get('date') || '';
    p.cats = (url.searchParams.get('cats') || '');
    p.cats_exclude = (url.searchParams.get('cats_exclude') || '');
    p.ptype = url.searchParams.get('ptype') || '';
    return p;
  }

  function bind(root, data){
    const body = root.querySelector('.feed-modal') || root;
    function lockDateControls() {
      const likesOn = (qs('input[name="likes_yesno"]:checked', body)||{}).value === 'yes';
      const dateRadios = qsa('input[name="date_order"]', body);
      if (likesOn) {
        // Force Latest and disable all date radios
        const latest = qs('input[name="date_order"][value="latest"]', body);
        if (latest) { latest.checked = true; }
        Array.prototype.forEach.call(dateRadios, function(r){ r.disabled = true; r.setAttribute('aria-disabled','true'); });
      } else {
        Array.prototype.forEach.call(dateRadios, function(r){ r.disabled = false; r.removeAttribute('aria-disabled'); });
      }
    }
    body.addEventListener('change', function(e){
      const t = e.target;
      if (!t) return;
      // Step toggles reveal bodies
      if (t.name === 'u_yesno') body.querySelector('[data-body="user"]').hidden = (t.value !== 'yes');
      if (t.name === 'likes_yesno') body.querySelector('[data-body="likes"]').hidden = (t.value !== 'yes');
      if (t.name === 'cats_yesno') body.querySelector('[data-body="cats"]').hidden = (t.value !== 'yes');
      if (t.name === 'exclude_yesno') body.querySelector('[data-body="exclude"]').hidden = (t.value !== 'yes');
      if (t.name === 'ptype_yesno') {
        const bodyEl = body.querySelector('[data-body="ptype"]');
        if (bodyEl) bodyEl.hidden = (t.value !== 'yes');
        // When switching to Yes, default-select 1hrphoto if none selected
        if (t.value === 'yes') {
          const cur = qs('input[name="ptype"]:checked', body);
          if (!cur) {
            const def = qs('input[name="ptype"][value="1hrphoto"]', body);
            if (def) def.checked = true;
          }
        }
      }
      // If a post type is selected, auto-enable Yes for Option 6
      if (t.name === 'ptype') {
        const y = qs('input[name="ptype_yesno"][value="yes"]', body);
        if (y) { y.checked = true; body.querySelector('[data-body="ptype"]').hidden = false; }
      }
      // If switching Option 6 back to No, clear any selected post type
      if (t.name === 'ptype_yesno' && t.value === 'no') {
        const checked = qs('input[name="ptype"]:checked', body);
        if (checked) checked.checked = false;
      }
      // Likes/date mutual exclusion
      if (t.name === 'likes_yesno' || t.name === 'likes_order' || t.name === 'date_order'){
        lockDateControls();
      }
    });
    const btnApply = body.querySelector('.btn-apply');
    if (btnApply) btnApply.addEventListener('click', function(){
      // Persist defaults, apply immediately, update My Feed links, and announce success
      saveDefaults(body, false); // not silent → will call applySoft after save
      updateMyFeedLinks();
      const summary = body.querySelector('.summary');
      if (summary) summary.textContent = 'Feed settings updated successfully.';
    });

    // Initialize category pickers
    const cats = (data && data.categories) ? data.categories : [];
    const defaults = (data && data.defaults) ? data.defaults : {};
    const params = getParamsFromUrl();
    // Merge with precedence: saved defaults first; URL only if the option is enabled
    const initial = {
      u_scope: params.u_scope || defaults.u_scope || 'everyone',
      likes_order: params.likes_order || defaults.likes_order || '',
      date_order: params.date_order || defaults.date_order || 'latest',
      cats: (params.cats ? params.cats.split(',').filter(Boolean).map(Number) : (defaults.cats||[])),
      cats_exclude: (params.cats_exclude ? params.cats_exclude.split(',').filter(Boolean).map(Number) : (defaults.cats_exclude||[])),
      ptype: (defaults.ptype_enabled ? (params.ptype || defaults.ptype || '') : ''),
    };
    // Seed hidden values from defaults/params
    const selPref = body.querySelector('#feed-cats-selected');
    const selEx = body.querySelector('#feed-cats-exclude-selected');
    if (selPref && Array.isArray(initial.cats)) selPref.value = initial.cats.join(',');
    if (selEx && Array.isArray(initial.cats_exclude)) selEx.value = initial.cats_exclude.join(',');
    renderCategoryChips(body, cats, defaults);

    // Reveal bodies based on defaults (no persisted yes/no, infer from presence)
    if (initial.u_scope && initial.u_scope !== 'everyone') {
      const y = qs('input[name="u_yesno"][value="yes"]', body); if (y) { y.checked = true; body.querySelector('[data-body="user"]').hidden = false; }
      const s = qs('input[name="u_scope"][value="' + initial.u_scope + '"]', body); if (s) s.checked = true;
    }
    if (Array.isArray(initial.cats) && initial.cats.length) {
      const y = qs('input[name="cats_yesno"][value="yes"]', body); if (y) { y.checked = true; body.querySelector('[data-body="cats"]').hidden = false; }
    }
    if (Array.isArray(initial.cats_exclude) && initial.cats_exclude.length) {
      const y = qs('input[name="exclude_yesno"][value="yes"]', body); if (y) { y.checked = true; body.querySelector('[data-body="exclude"]').hidden = false; }
    }
    if (initial.likes_order) {
      const y = qs('input[name="likes_yesno"][value="yes"]', body); if (y) { y.checked = true; body.querySelector('[data-body="likes"]').hidden = false; }
      const s = qs('input[name="likes_order"][value="' + initial.likes_order + '"]', body); if (s) s.checked = true;
    }
    if (initial.date_order) {
      const s = qs('input[name="date_order"][value="' + initial.date_order + '"]', body);
      if (s) s.checked = true;
      // Enforce likes/date mutual exclusion on hydration
      lockDateControls();
    }
    if (initial.ptype) {
      const y = qs('input[name="ptype_yesno"][value="yes"]', body); if (y) { y.checked = true; body.querySelector('[data-body="ptype"]').hidden = false; }
      const s = qs('input[name="ptype"][value="' + initial.ptype + '"]', body); if (s) s.checked = true;
    } else {
      // Prefer saved ptype_enabled default for hydration; if absent, fall back to No.
      const enable = !!defaults.ptype_enabled;
      const yes = qs('input[name="ptype_yesno"][value="yes"]', body);
      const no  = qs('input[name="ptype_yesno"][value="no"]', body);
      const step= body.querySelector('[data-body\="ptype\"]');
      if (enable && yes) {
        yes.checked = true; if (step) step.hidden = false;
        // Default select 1hrphoto if none selected when enabled by defaults
        const cur = qs('input[name="ptype"]:checked', body);
        if (!cur) { const def = qs('input[name="ptype"][value="1hrphoto"]', body); if (def) def.checked = true; }
      }
      else if (no) { no.checked = true; if (step) step.hidden = true; }
    }

    // After save/apply, update any My Feed links to reflect new params immediately
    function updateMyFeedLinks(){
      const { u_scope, likes_order, date_order, cats, cats_exclude, ptype } = getFormData(body);
      const base = (ajax_object && ajax_object.home_url) || '/';
      const url = new URL(base, location.origin);
      if (u_scope) url.searchParams.set('u_scope', u_scope);
      if (likes_order) url.searchParams.set('likes', likes_order);
      if (date_order) url.searchParams.set('date', date_order);
      if (cats) url.searchParams.set('cats', cats); else url.searchParams.delete('cats');
      if (cats_exclude) url.searchParams.set('cats_exclude', cats_exclude); else url.searchParams.delete('cats_exclude');
      if (ptype) url.searchParams.set('ptype', ptype); else url.searchParams.delete('ptype');
      document.querySelectorAll('a.my-feed-link').forEach(a=>{ a.href = url.toString(); });
      // Normalize current URL (avoid stale ptype etc.)
      try { history.replaceState(null, '', url.toString()); } catch(_){ }
    }
    // Update links already handled in the unified Save handler above

    // Dev: log ptype being saved to verify Option 6
    body.addEventListener('click', function(e){
      const btn = e.target && e.target.closest && e.target.closest('.btn-save-default, .btn-apply');
      if (!btn) return;
      const pt = (qs('input[name="ptype_yesno"]:checked', body)||{}).value === 'yes' ? ((qs('input[name="ptype"]:checked', body)||{}).value || '') : '';
      try { console.log('[feed-modal] saving ptype=', pt); } catch(_){ }
    });
  }

  // Expose a tiny opener; you can wire this to any button
  window.openFeedSettings = openFeedModal;
})();



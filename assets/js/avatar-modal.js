document.addEventListener('DOMContentLoaded', () => {
  // Open avatar editor when clicking own avatar inside profile modal
  document.body.addEventListener('click', (e) => {
    const img = e.target.closest('img[data-avatar-user-id]');
    if (!img) return;
    const modal = document.getElementById('universal-modal');
    // Only allow editing if profile modal edit state is active and this is current user
    const uid = Number(img.getAttribute('data-avatar-user-id')) || 0;
    if (!modal || modal.dataset.profileOpen !== '1' || !modal.classList.contains('is-profile-edit') || !ajax_object || !ajax_object.current_user_id || Number(ajax_object.current_user_id) !== uid) return;
    e.preventDefault();
    const slot = document.getElementById('avatar-editor-slot');
    if (slot && slot.childElementCount) { slot.innerHTML = ''; slot.style.display='none'; return; }
    if (slot) { slot.style.display='block'; slot.innerHTML = '<div class="loading">Loading…</div>'; }
    fetch(ajax_object.ajax_url, { method:'POST', body:new URLSearchParams({ action:'get_edit_avatar_modal', nonce: ajax_object.nonce }), credentials:'same-origin' })
      .then(r=>r.json()).then(json=>{
        if (json && json.success && slot) slot.innerHTML = json.data.html;
      });
  });

  // Handle upload/delete inside avatar modal
  document.body.addEventListener('click', (e) => {
    if (e.target && e.target.classList && e.target.classList.contains('avatar-upload-btn')) {
      e.preventDefault();
      const input = document.getElementById('avatar-file');
      if (input) input.click();
    }
    if (e.target && e.target.classList && e.target.classList.contains('avatar-delete-btn')) {
      e.preventDefault();
      const err = document.getElementById('avatar-error');
      if (err) { err.style.display='none'; err.textContent=''; }
      const fd = new URLSearchParams({ action:'delete_avatar', nonce: ajax_object.nonce });
      fetch(ajax_object.ajax_url, { method:'POST', body: fd, credentials:'same-origin' })
        .then(r=>r.json()).then(res=>{
          if (!res || !res.success) {
            if (err) { err.textContent = (res && res.data && res.data.message) || 'Delete failed'; err.style.display='block'; }
            return;
          }
          // Remove custom avatar across page (force refresh via cache-bust not available; rely on Gravatar fallback)
          document.querySelectorAll('img[data-avatar-user-id="' + ajax_object.current_user_id + '"]').forEach(el => {
            // remove cache-bust, allow fallback update on reload; optionally set to default placeholder
            el.src = el.src; // no-op to keep current until next load
          });
        });
    }
  });

  document.body.addEventListener('change', (e) => {
    if (e.target && e.target.id === 'avatar-file') {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      const err = document.getElementById('avatar-error');
      if (err) { err.style.display='none'; err.textContent=''; }
      const allowed = ['image/jpeg','image/jpg','image/gif','image/webp'];
      if (!allowed.includes(file.type)) { if (err){ err.textContent='Invalid file type'; err.style.display='block'; } return; }
      if (file.size > 500*1024) { if (err){ err.textContent='File exceeds 500KB'; err.style.display='block'; } return; }
      const fd = new FormData();
      fd.append('action','upload_avatar'); fd.append('nonce', ajax_object.nonce); fd.append('file', file);
      fetch(ajax_object.ajax_url, { method:'POST', body: fd, credentials:'same-origin' })
        .then(r=>r.json()).then(res=>{
          if (!res || !res.success) {
            if (err) { err.textContent = (res && res.data && res.data.message) || 'Upload failed'; err.style.display='block'; }
            return;
          }
          const url = res.data && res.data.url;
          if (url) {
            // Update all visible avatars for current user
            document.querySelectorAll('img[data-avatar-user-id="' + ajax_object.current_user_id + '"]').forEach(el => {
              try {
                el.removeAttribute('srcset');
                el.removeAttribute('sizes');
              } catch(_) {}
              el.src = url;
            });
            // Hide editor slot after successful update
            const slot = document.getElementById('avatar-editor-slot');
            if (slot) { slot.innerHTML=''; slot.style.display='none'; }
          }
        }).catch(()=>{ if (err){ err.textContent='Upload failed'; err.style.display='block'; } });
    }
  });
});



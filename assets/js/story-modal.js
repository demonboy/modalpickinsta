document.addEventListener('DOMContentLoaded', () => {
    function openStoryModalFor(postId) {
        if (!postId) return;
        const modal = document.getElementById('universal-modal');
        if (modal) modal.classList.add('modal--full');
        if (window.openModal) window.openModal('<div class="loading">Loading…</div>');

        fetch(ajax_object.ajax_url, {
            method: 'POST',
            body: new URLSearchParams({ action: 'get_story_modal', nonce: ajax_object.nonce, post_id: String(postId) }),
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) throw new Error('Failed to load story');
            const body = document.querySelector('#universal-modal .modal-body');
            if (body) { body.innerHTML = data.data.html; }

            // Ensure external links open in new tab
            if (body) {
                body.querySelectorAll('a[href^="http"]').forEach(a => { a.target = '_blank'; a.rel = 'noopener'; });
            }

            // Push a history state so Back closes modal and returns to page view
            try {
                const hash = '#story-' + String(postId);
                if (location.hash !== hash) {
                    history.pushState({ storyModal: true, postId: String(postId) }, '', hash);
                } else {
                    history.replaceState({ storyModal: true, postId: String(postId) }, '', hash);
                }
            } catch(_) {}
        })
        .catch(() => {
            const body = document.querySelector('#universal-modal .modal-body');
            if (body) { body.innerHTML = '<div class="error">Unable to load story.</div>'; }
        });
    }

    // Intercept clicks in homepage loop for story titles/images/read-more links
    // DISABLED: Story posts now open as regular WordPress posts instead of modal
    /*
    document.body.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link) return;
        const postId = link.getAttribute('data-story-id');
        if (!postId) return;
        // Respect modifier keys or target=_blank
        if (e.metaKey || e.ctrlKey || e.shiftKey || link.target === '_blank') return;
        e.preventDefault();
        openStoryModalFor(postId);
    });
    */

    // Remove full modifier on close (UI close)
    document.body.addEventListener('click', (e) => {
        if (e.target.classList && e.target.classList.contains('modal-close')) {
            const modal = document.getElementById('universal-modal');
            if (modal) modal.classList.remove('modal--full');
            // If we pushed a state, go back so Back returns to page view
            if (history.state && history.state.storyModal) {
                try { history.back(); } catch(_) {}
            }
        }
    });

    // Back button / navigation: close modal if story modal state is popped
    window.addEventListener('popstate', (e) => {
        const modal = document.getElementById('universal-modal');
        if (!modal) return;
        const isOpen = modal.classList.contains('modal--full');
        const state = e.state;
        // If modal open and state went away (or non-modal state), close it
        if (isOpen && (!state || !state.storyModal)) {
            modal.classList.remove('modal--full');
            try { if (window.closeModal) window.closeModal(); } catch(_) {}
        }
    });

    // Optional deep link: if URL has #story-<id>, open it
    if (location.hash && /^#story-\d+$/.test(location.hash)) {
        const id = location.hash.replace('#story-','');
        openStoryModalFor(id);
    }
});



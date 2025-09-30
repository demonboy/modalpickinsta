/* Standalone Share Modal (no dependency on universal-modal) */
(function(){
  if (typeof window === 'undefined') return;

  // Utilities
  function qs(sel, root){ return (root||document).querySelector(sel); }
  function qsa(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }
  function createEl(tag, cls){ var el = document.createElement(tag); if(cls){ el.className = cls; } return el; }

  // Build modal shell once
  var modal, dialog, backdrop, closeBtn, railWrap, rail, leftBtn, rightBtn, linkInput, copyBtn, toast;
  function ensureModal(){
    if (modal) return;
    modal = createEl('div', 'onehr-share-modal');
    backdrop = createEl('div', 'onehr-share-backdrop');
    dialog = createEl('div', 'onehr-share-dialog');
    var header = createEl('div', 'onehr-share-header');
    var title = createEl('h3', 'onehr-share-title');
    title.textContent = (window.ONEHR_SHARE && ONEHR_SHARE.strings && ONEHR_SHARE.strings.share) || 'Share';
    closeBtn = createEl('button', 'onehr-share-close');
    closeBtn.setAttribute('aria-label', (ONEHR_SHARE && ONEHR_SHARE.strings && ONEHR_SHARE.strings.close) || 'Close');
    closeBtn.innerHTML = '&times;';
    header.appendChild(title); header.appendChild(closeBtn);

    // Rail + scroll buttons
    railWrap = createEl('div', 'onehr-share-railwrap');
    leftBtn = createEl('button', 'onehr-share-scroll onehr-share-scroll--left');
    leftBtn.type = 'button'; leftBtn.setAttribute('aria-label','Scroll left'); leftBtn.textContent = '‹';
    rightBtn = createEl('button', 'onehr-share-scroll onehr-share-scroll--right');
    rightBtn.type = 'button'; rightBtn.setAttribute('aria-label','Scroll right'); rightBtn.textContent = '›';
    rail = createEl('div', 'onehr-share-rail onehr-share-rail--icons');
    railWrap.appendChild(leftBtn); railWrap.appendChild(rail); railWrap.appendChild(rightBtn);

    var linkWrap = createEl('div', 'onehr-share-linkwrap');
    linkInput = createEl('input', 'onehr-share-link');
    linkInput.type = 'text'; linkInput.readOnly = true;
    copyBtn = createEl('button', 'onehr-share-copy');
    copyBtn.textContent = (ONEHR_SHARE && ONEHR_SHARE.strings && ONEHR_SHARE.strings.copy) || 'Copy';
    linkWrap.appendChild(linkInput); linkWrap.appendChild(copyBtn);

    dialog.setAttribute('role','dialog');
    dialog.setAttribute('aria-modal','true');
    dialog.appendChild(header);
    dialog.appendChild(railWrap);
    dialog.appendChild(linkWrap);
    modal.appendChild(backdrop);
    modal.appendChild(dialog);
    document.body.appendChild(modal);

    // events
    backdrop.addEventListener('click', close);
    closeBtn.addEventListener('click', close);
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') close(); });
    copyBtn.addEventListener('click', function(){
      linkInput.select();
      try {
        var done = false;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(linkInput.value).then(function(){ notifyCopied(); });
          done = true;
        }
        if (!done) {
          document.execCommand('copy');
          notifyCopied();
        }
      } catch(err) {}
    });

    function updateArrows(){
      if (!rail) return;
      var canScrollLeft = rail.scrollLeft > 0;
      var canScrollRight = (rail.scrollLeft + rail.clientWidth) < (rail.scrollWidth - 1);
      leftBtn.classList.toggle('is-visible', canScrollLeft);
      rightBtn.classList.toggle('is-visible', canScrollRight);
    }
    rail.addEventListener('scroll', updateArrows, { passive: true });
    window.addEventListener('resize', updateArrows);
    leftBtn.addEventListener('click', function(){ rail.scrollBy({ left: -Math.max(200, rail.clientWidth * 0.8), behavior: 'smooth' }); });
    rightBtn.addEventListener('click', function(){ rail.scrollBy({ left: Math.max(200, rail.clientWidth * 0.8), behavior: 'smooth' }); });
    setTimeout(updateArrows, 0);
  }

  function notifyCopied(){
    if (!toast){ toast = createEl('div', 'onehr-share-toast'); document.body.appendChild(toast); }
    toast.textContent = (ONEHR_SHARE && ONEHR_SHARE.strings && ONEHR_SHARE.strings.copied) || 'Copied';
    toast.classList.add('is-visible');
    setTimeout(function(){ toast.classList.remove('is-visible'); }, 1200);
  }

  function open(data){
    ensureModal();
    // Fill rail
    rail.innerHTML = '';
    var list = buildNetworks(data.title, data.url);
    list.forEach(function(item){
      var btn = createEl('button', 'onehr-share-item onehr-share-' + item.id);
      btn.type = 'button'; btn.title = item.label; btn.setAttribute('aria-label', 'Share to ' + item.label);
      var icon = createEl('span', 'onehr-share-icon');
      icon.innerHTML = getBrandSvg(item.id);
      var lbl = createEl('span', 'onehr-share-label');
      lbl.textContent = item.label;
      btn.appendChild(icon); btn.appendChild(lbl);
      btn.addEventListener('click', function(){
        // Progressive enhancement: try Web Share first for the first click
        if (navigator.share) {
          navigator.share({ title: data.title, url: data.url }).catch(function(){
            window.open(item.href, '_blank', 'noopener');
          });
        } else {
          window.open(item.href, '_blank', 'noopener');
        }
      });
      rail.appendChild(btn);
    });
    linkInput.value = data.url;
    modal.classList.add('is-open');
    // Focus trap minimal: focus close first
    closeBtn.focus();
    setTimeout(function(){ var evt = new Event('resize'); window.dispatchEvent(evt); }, 0);
  }

  function close(){ if(modal){ modal.classList.remove('is-open'); } }

  function buildNetworks(title, url){
    try {
      // Pull server-rendered URLs for consistency if available via global
      // Otherwise reconstruct in JS (matches PHP helpers)
      var encUrl = encodeURIComponent(url);
      var encTitle = encodeURIComponent(title);
      return [
        { id:'facebook', label:'Facebook', href:'https://www.facebook.com/sharer/sharer.php?u=' + encUrl },
        { id:'x', label:'X', href:'https://twitter.com/intent/tweet?url=' + encUrl + '&text=' + encTitle },
        { id:'whatsapp', label:'WhatsApp', href:'https://wa.me/?text=' + encTitle + '%20' + encUrl },
        { id:'linkedin', label:'LinkedIn', href:'https://www.linkedin.com/sharing/share-offsite/?url=' + encUrl },
        { id:'reddit', label:'Reddit', href:'https://www.reddit.com/submit?url=' + encUrl + '&title=' + encTitle },
        { id:'telegram', label:'Telegram', href:'https://t.me/share/url?url=' + encUrl + '&text=' + encTitle },
        { id:'email', label:'Email', href:'mailto:?subject=' + encTitle + '&body=' + encUrl }
      ];
    } catch(e){ return []; }
  }

  // Brand glyphs with currentColor for easy theming
  function getBrandSvg(id){
    switch(id){
      case 'facebook':
        return '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M22 12.06C22 6.48 17.52 2 11.94 2 6.36 2 1.88 6.48 1.88 12.06c0 5.02 3.66 9.19 8.44 9.96v-7.04H7.9v-2.92h2.42V9.43c0-2.4 1.43-3.73 3.62-3.73 1.05 0 2.16.19 2.16.19v2.38h-1.22c-1.2 0-1.57.74-1.57 1.5v1.79h2.68l-.43 2.92h-2.25v7.04c4.78-.77 8.42-4.94 8.42-9.96z"/></svg>';
      case 'x':
        return '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M3 3h3.7l4.54 6.62L16.76 3H21l-6.9 9.1L21 21h-3.7l-4.9-7.13L7.24 21H3l7.03-9.28L3 3z"/></svg>';
      case 'whatsapp':
        return '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M20.52 3.49A11.82 11.82 0 0 0 12.01.15C5.52.15.3 5.36.3 11.85c0 2.08.54 4.08 1.58 5.87L.16 24l6.42-1.66a11.69 11.69 0 0 0 5.44 1.38h.01c6.49 0 11.7-5.21 11.7-11.7 0-3.14-1.22-6.09-3.21-8.53ZM12.03 21.2h-.01c-1.74 0-3.46-.47-4.97-1.36l-.36-.21-3.81.98 1.02-3.71-.24-.38a9.68 9.68 0 0 1-1.5-5.11c0-5.35 4.35-9.7 9.7-9.7 2.59 0 5.03 1.01 6.86 2.85a9.62 9.62 0 0 1 2.84 6.86c0 5.35-4.35 9.68-9.69 9.68Zm5.63-7.26c-.31-.16-1.86-.91-2.14-1.02-.28-.1-.48-.16-.68.16-.2.31-.78 1.02-.95 1.23-.18.2-.35.23-.65.08-.31-.16-1.3-.48-2.48-1.53-.92-.82-1.54-1.84-1.72-2.14-.18-.31-.02-.47.14-.62.15-.15.31-.35.47-.53.16-.18.2-.31.31-.51.1-.2.05-.39-.03-.55-.08-.16-.68-1.63-.93-2.23-.25-.6-.5-.51-.68-.51l-.58-.01c-.2 0-.5.07-.76.39-.26.31-.99.97-.99 2.36 0 1.39 1.02 2.74 1.16 2.93.15.2 2.01 3.06 4.87 4.29.68.29 1.21.47 1.63.6.68.22 1.3.19 1.79.12.55-.08 1.86-.76 2.12-1.49.26-.74.26-1.37.18-1.49-.08-.12-.28-.2-.6-.35Z"/></svg>';
      case 'linkedin':
        return '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M4.98 3.5A2.5 2.5 0 1 1 0 3.5a2.5 2.5 0 0 1 4.98 0zM.5 8.5h4.95V24H.5zM8.5 8.5h4.74v2.11h.07c.66-1.26 2.27-2.59 4.66-2.59 4.99 0 5.91 3.29 5.91 7.57V24h-4.95v-6.74c0-1.61-.03-3.69-2.25-3.69-2.26 0-2.6 1.76-2.6 3.57V24H8.5z"/></svg>';
      case 'reddit':
        return '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M22 10.34c0-1.16-.94-2.1-2.1-2.1-.62 0-1.17.27-1.56.7-1.5-.97-3.56-1.6-5.85-1.67l.99-4.66 3.22.68a2.1 2.1 0 1 0 .23-1.01l-3.83-.81a.52.52 0 0 0-.62.4l-1.12 5.24c-2.37.05-4.5.68-6.05 1.67a2.1 2.1 0 1 0-1.56 3.56c0 2.56 2.9 4.66 6.65 5.11-.06.25-.09.52-.09.79 0 1.74 1.89 3.15 4.22 3.15s4.22-1.41 4.22-3.15c0-.27-.03-.54-.1-.79 3.74-.45 6.66-2.55 6.66-5.11ZM6.08 12.1a1.4 1.4 0 1 1 2.81 0 1.4 1.4 0 0 1-2.8 0Zm9.97 6.93c-.73.73-2.13.78-2.61.78s-1.88-.05-2.61-.78a.52.52 0 1 1 .74-.74c.36.36 1.12.53 1.87.53.75 0 1.5-.17 1.86-.53a.52.52 0 1 1 .74.74ZM15.1 13.5a1.4 1.4 0 1 1 2.81 0 1.4 1.4 0 0 1-2.8 0Z"/></svg>';
      case 'telegram':
        return '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M9.04 15.72 8.9 20.3c.42 0 .6-.18.82-.39l1.97-1.88 4.08 3c.75.41 1.28.19 1.49-.7l2.7-12.66.01-.01c.24-1.11-.41-1.54-1.13-1.27L2.9 10.08c-1.08.42-1.07 1.01-.18 1.27l4.53 1.41 10.52-6.63c.5-.31.96-.14.58.17"/></svg>';
      case 'email':
        return '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z"/></svg>';
      default:
        return '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="currentColor"/></svg>';
    }
  }

  // Delegate clicks from shortcodes
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.onehr-share-open');
    if (!btn) return;
    e.preventDefault();
    // Prevent other delegated handlers (e.g., profile modal) from firing
    if (typeof e.stopImmediatePropagation === 'function') { e.stopImmediatePropagation(); }
    if (typeof e.stopPropagation === 'function') { e.stopPropagation(); }
    var url = btn.getAttribute('data-share-url') || (ONEHR_SHARE && ONEHR_SHARE.home_url) || window.location.href;
    var title = btn.getAttribute('data-share-title') || document.title;
    open({title:title, url:url});
  }, true); // capture phase to run before bubbling listeners

  // On load, if we have ?post=ID, attempt to scroll to #post-ID
  window.addEventListener('load', function(){
    try {
      var params = new URLSearchParams(window.location.search);
      var pid = params.get('post');
      if (!pid) return;
      var anchor = document.getElementById('post-' + pid) || document.querySelector('.post-' + pid);
      if (anchor) {
        anchor.scrollIntoView({ behavior:'smooth', block:'start' });
        anchor.classList.add('onehr-share-highlight');
        setTimeout(function(){ anchor.classList.remove('onehr-share-highlight'); }, 1800);
      }
    } catch(err) {}
  });

  // Expose open function globally for programmatic sharing
  window.openShareModal = function(data) {
    open(data);
  };
})();



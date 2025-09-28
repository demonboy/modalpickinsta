document.addEventListener('DOMContentLoaded', () => {
  // Match the exact header structure provided
  const list = document.querySelector('.wp-block-group.is-content-justification-space-between.is-layout-flex > .wp-block-group.is-layout-flex > ul.wp-block-categories-list.cat-chips.wp-block-categories');
  if (!list) return;

  // Desktop-only arrows
  const mq = window.matchMedia('(min-width:1024px)');
  function setupArrows() {
    // Avoid duplicates
    const prevExisting = list.parentNode.querySelector('.header-cats-prev');
    const nextExisting = list.parentNode.querySelector('.header-cats-next');
    if (mq.matches) {
      if (!prevExisting) {
        const prev = document.createElement('button');
        prev.className = 'header-cats-nav-btn header-cats-prev';
        prev.type = 'button';
        prev.setAttribute('aria-label', 'Scroll categories left');
        prev.textContent = '<';
        list.parentNode.insertBefore(prev, list);
        prev.addEventListener('click', () => {
          const nudge = Math.round(list.clientWidth * 0.8) || 300;
          list.scrollBy({ left: -nudge, behavior: 'smooth' });
        });
      }
      if (!nextExisting) {
        const next = document.createElement('button');
        next.className = 'header-cats-nav-btn header-cats-next';
        next.type = 'button';
        next.setAttribute('aria-label', 'Scroll categories right');
        next.textContent = '>';
        list.parentNode.insertBefore(next, list.nextSibling);
        next.addEventListener('click', () => {
          const nudge = Math.round(list.clientWidth * 0.8) || 300;
          list.scrollBy({ left: nudge, behavior: 'smooth' });
        });
      }
    } else {
      if (prevExisting) prevExisting.remove();
      if (nextExisting) nextExisting.remove();
    }
  }

  setupArrows();
  mq.addEventListener('change', setupArrows);
});



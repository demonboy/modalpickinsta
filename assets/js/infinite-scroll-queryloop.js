(function () {
  'use strict';

  function forEachNodeList(list, cb) {
    Array.prototype.forEach.call(list, cb);
  }

  function findListContainer(q) {
    // Standard
    var list = q.querySelector('.wp-block-post-template');
    if (list) return list;
    // Some themes wrap the list differently; try common fallbacks
    list = q.querySelector('[data-wp-block-content]');
    if (list) return list;
    // Fall back to first UL/OL within the query
    list = q.querySelector('ul,ol');
    return list || null;
  }

  function findNextLink(root) {
    var sel = [
      '.wp-block-query-pagination .wp-block-query-pagination-next a',
      '.wp-block-query-pagination-next a',
      'a.wp-block-query-pagination-next',
      '.wp-block-query-pagination a[rel="next"]',
      '.wp-block-query-pagination a[aria-label="Next page"]',
      '.pagination .next a',
      '.pagination a.next',
      '.page-numbers .next',
      '.nav-links .nav-next a',
      '.nav-links .next',
      'a.next'
    ];
    for (var i = 0; i < sel.length; i++) {
      var a = root.querySelector(sel[i]);
      if (a) return a;
    }
    return null;
  }

  function initQuery(query) {
    // Skip if a custom feed co-exists
    if (document.querySelector('.postpic-feed')) return;

    var list = findListContainer(query);
    if (!list) return;

    var nextLink = findNextLink(query);
    if (!nextLink) return;

    // Hide pagination only after enhancement initialized (progressive enhancement)
    var pagers = query.querySelectorAll('.wp-block-query-pagination, .pagination, .nav-links');
    forEachNodeList(pagers, function (el) { el.style.display = 'none'; });

    // Create sentinel
    var sentinel = document.createElement('div');
    sentinel.className = 'infinite-scroll-sentinel';
    sentinel.setAttribute('aria-hidden', 'true');
    (list.parentNode || query).appendChild(sentinel);

    var isLoading = false;
    var loadingEl = null;

    function createLoading() {
      var div = document.createElement('div');
      div.className = 'infinite-scroll-loading';
      div.innerHTML = '<div class="loading-spinner"></div><p>Loading more…</p>';
      return div;
    }

    function showLoading() {
      if (!loadingEl) {
        loadingEl = createLoading();
        (list.parentNode || query).appendChild(loadingEl);
      }
    }

    function hideLoading() {
      if (loadingEl && loadingEl.parentNode) loadingEl.parentNode.removeChild(loadingEl);
      loadingEl = null;
    }

    function rebindInteractions(scope) {
      var evt = new CustomEvent('infiniteScrollAppended', { detail: { scope: scope } });
      document.dispatchEvent(evt);
    }

    function updateUrl(newUrl) {
      try { history.replaceState(null, '', newUrl); } catch (e) {}
    }

    function loadMore() {
      if (isLoading || !nextLink) return;
      isLoading = true;
      showLoading();
      fetch(nextLink.href, { credentials: 'same-origin' })
        .then(function (r) { return r.text(); })
        .then(function (html) {
          var doc = new DOMParser().parseFromString(html, 'text/html');
          var newQuery = doc.querySelector('.wp-block-query') || doc;
          var newList = findListContainer(newQuery);
          if (!newList) { nextLink = null; return; }
          var children = Array.prototype.slice.call(newList.children);
          children.forEach(function (child) { list.appendChild(child); });
          rebindInteractions(list);
          nextLink = findNextLink(newQuery) || findNextLink(doc);
          var canon = doc.querySelector('link[rel="canonical"]');
          if (canon && canon.href) updateUrl(canon.href);
        })
        .catch(function () { nextLink = null; })
        .finally(function () { hideLoading(); isLoading = false; });
    }

    function initObserver() {
      if (!('IntersectionObserver' in window)) {
        var btnWrap = document.createElement('div');
        btnWrap.className = 'infinite-scroll-load-more';
        var btn = document.createElement('button');
        btn.className = 'load-more-btn';
        btn.type = 'button';
        btn.textContent = 'Load more';
        btn.addEventListener('click', loadMore);
        (list.parentNode || query).appendChild(btnWrap);
        btnWrap.appendChild(btn);
        return;
      }
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting && nextLink) loadMore(); });
      }, { root: null, rootMargin: '120px', threshold: 0.1 });
      observer.observe(sentinel);
    }

    initObserver();
  }

  function init() {
    var queries = document.querySelectorAll('.wp-block-query');
    if (!queries.length) return;
    forEachNodeList(queries, initQuery);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

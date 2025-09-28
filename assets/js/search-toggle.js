document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.arch-search').forEach(function(wrap){
    const btn = wrap.querySelector('[data-arch-toggle]') || wrap.querySelector('.arch-search__toggle');
    const form = wrap.querySelector('.arch-search__form');
    const input = form ? form.querySelector('input[type="search"]') : null;
    if (!btn || !form) return;

    function open(){ wrap.classList.add('arch-search--open'); btn.setAttribute('aria-expanded','true'); if (input) { setTimeout(()=>input.focus(), 0); } }
    function close(){ wrap.classList.remove('arch-search--open'); btn.setAttribute('aria-expanded','false'); }
    function toggle(){ if (wrap.classList.contains('arch-search--open')) close(); else open(); }

    btn.addEventListener('click', function(e){ e.preventDefault(); toggle(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
    document.addEventListener('click', function(e){ if (!wrap.contains(e.target)) close(); });
  });
});





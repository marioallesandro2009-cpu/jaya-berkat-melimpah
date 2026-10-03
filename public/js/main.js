(function () {
  'use strict';

  var root = document.documentElement;
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var isMobile = function () { return window.innerWidth < 720; };
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var clamp = function (v, a, b) { return Math.min(b, Math.max(a, v)); };

  /* ---------- Hero load-in ---------- */
  function start() {
    requestAnimationFrame(function () { document.body.classList.add('is-loaded'); });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();

  var nav = $('.nav');

  /* ---------- Mobile menu ---------- */
  var toggle = $('.nav-toggle');
  var links = $('.nav-links');
  $$('.nav-links > li').forEach(function (li, i) { li.style.setProperty('--i', i); });

  function setMenu(open) {
    if (!toggle || !links) return;
    toggle.classList.toggle('is-open', open);
    links.classList.toggle('is-open', open);
    root.classList.toggle('menu-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', toggle.getAttribute(open ? 'data-label-close' : 'data-label-open') || '');
    if (open) { var first = $('a', links); if (first) setTimeout(function () { first.focus(); }, 60); }
  }
  if (toggle && links) {
    toggle.addEventListener('click', function () { setMenu(!links.classList.contains('is-open')); });
    $$('a', links).forEach(function (a) { a.addEventListener('click', function () { setMenu(false); }); });
    document.addEventListener('keydown', function (e) {
      if (!links.classList.contains('is-open')) return;
      if (e.key === 'Escape') { setMenu(false); toggle.focus(); return; }
      if (e.key === 'Tab') {            // keep focus inside the open menu
        var f = $$('a, button', nav).filter(function (n) { return n.offsetParent !== null; });
        if (!f.length) return;
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
    window.addEventListener('resize', function () { if (window.innerWidth > 1100) setMenu(false); });
  }

  /* ---------- Scroll reveal (once) ---------- */
  $$('[data-stagger]').forEach(function (group) {
    $$(':scope > *', group).slice(0, 6).forEach(function (el, i) {
      el.classList.add('reveal');
      el.style.transitionDelay = (i * 80) + 'ms';
    });
  });
  var revealEls = $$('.reveal, .img-reveal, .route');
  if (reduced || !('IntersectionObserver' in window)) {
    revealEls.forEach(function (el) { el.classList.add('is-visible'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
    revealEls.forEach(function (el) { io.observe(el); });
  }

  /* ---------- Counters (once, 1400ms ease-out) ---------- */
  var counters = $$('[data-count]');
  function fmt(n, el) { return Math.round(n).toLocaleString('en-US') + (el.getAttribute('data-suffix') || ''); }
  if (!reduced && 'IntersectionObserver' in window) {
    counters.forEach(function (el) { el.textContent = fmt(0, el); });
    var cio = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        cio.unobserve(en.target);
        var el = en.target, end = parseFloat(el.getAttribute('data-count')), dur = 1400, t0 = null;
        (function tick(t) {
          if (t0 === null) t0 = t;
          var p = clamp((t - t0) / dur, 0, 1);
          el.textContent = fmt(end * (1 - Math.pow(1 - p, 3)), el);
          if (p < 1) requestAnimationFrame(tick);
        })(performance.now());
      });
    }, { threshold: 0.6 });
    counters.forEach(function (el) { cio.observe(el); });
  }

  /* ---------- Scroll loop: parallax + value chain (one rAF, transform only) ---------- */
  var hero = $('.hero');
  var heroMedia = hero && $('.hero-media', hero);
  var heroContent = hero && $('.hero-content', hero);
  var plx = $$('[data-parallax]');
  var driftX = $$('[data-drift-x]');                    // fish that slide slightly sideways while scrolling
  var gauge = $('[data-depth-gauge]');
  var gaugeNow = gauge && $('[data-depth-now]', gauge);
  var gaugeTrack = gauge && $('.depth-gauge-track', gauge);
  var gaugeBox = gauge && gauge.parentNode;
  var gaugeMax = gauge ? parseFloat(gauge.getAttribute('data-depth-max')) : 0;
  var gaugeLast = '';
  var drift = $$('[data-drift]');                       // slow vertical drift in px (products page depth labels and photos)
  var chain = $('[data-chain]');
  var steps = chain ? $$('.chain-step', chain) : [];
  var frames = chain ? $$('.chain-frame', chain) : [];
  var fill = chain && $('.chain-fill', chain);
  var stepsBox = chain && $('.chain-steps', chain);
  var count = chain && $('.chain-count', chain);
  var activeIdx = -1;
  var ticking = false;

  function setActive(idx) {
    if (idx === activeIdx) return;
    activeIdx = idx;
    steps.forEach(function (s, i) { s.classList.toggle('is-active', i === idx); });
    frames.forEach(function (f, i) { f.classList.toggle('is-active', i === idx); });
    if (count) count.textContent = ('0' + (idx + 1)).slice(-2) + ' / ' + ('0' + steps.length).slice(-2);
  }

  function update() {
    ticking = false;
    var y = window.pageYOffset, vh = window.innerHeight;

    if (nav) nav.classList.toggle('is-scrolled', y > 40);

    if (!reduced && !isMobile()) {
      if (hero && y < hero.offsetHeight) {
        var hh = hero.offsetHeight, hp = clamp(y / hh, 0, 1);
        heroMedia.style.transform = 'translate3d(0,' + (hp * 0.08 * hh).toFixed(1) + 'px,0)';
        heroContent.style.transform = 'translate3d(0,' + (-hp * 0.08 * hh).toFixed(1) + 'px,0)';
      }
      plx.forEach(function (el) {
        var r = el.parentNode.getBoundingClientRect();
        if (r.bottom < -100 || r.top > vh + 100) return;
        var mid = (r.top + r.height / 2 - vh / 2) / vh;       // -1 .. 1 across the viewport
        el.style.transform = 'translate3d(0,' + (mid * -0.06 * r.height).toFixed(1) + 'px,0)';
      });
    }

    if (!reduced && !isMobile()) {
      drift.forEach(function (el) {
        var r = el.parentNode.getBoundingClientRect();
        if (r.bottom < -150 || r.top > vh + 150) return;
        var mid = (r.top + r.height / 2 - vh / 2) / vh;
        el.style.transform = 'translate3d(0,' + (mid * -parseFloat(el.getAttribute('data-drift'))).toFixed(1) + 'px,0)';
      });
    }

    if (!reduced) {
      driftX.forEach(function (el) {
        var r = el.parentNode.getBoundingClientRect();
        if (r.bottom < -150 || r.top > vh + 150) return;
        var mid = (r.top + r.height / 2 - vh / 2) / vh;
        el.style.transform = 'translate3d(' + (mid * parseFloat(el.getAttribute('data-drift-x'))).toFixed(1) + 'px,0,0)';
      });
    }

    if (gauge) {
      var gb = gaugeBox.getBoundingClientRect();
      var gp = clamp((vh * 0.4 - gb.top) / gb.height, 0, 1);
      var dm = Math.round(gp * gaugeMax / 10) * 10;
      var label = dm === 0 ? '0 m' : '−' + dm + ' m';
      if (label !== gaugeLast) { gaugeLast = label; gaugeNow.textContent = label; }
      gaugeNow.style.transform = 'translate3d(0,' + (gp * (gaugeTrack.clientHeight - 24)).toFixed(1) + 'px,0)';
    }

    if (chain && steps.length) {
      var box = stepsBox.getBoundingClientRect();
      var prog = clamp((vh * 0.5 - box.top) / box.height, 0, 1);
      fill.style.transform = 'scaleY(' + prog.toFixed(4) + ')';
      var idx = 0;
      steps.forEach(function (s, i) { if (s.getBoundingClientRect().top < vh * 0.55) idx = i; });
      if (box.top > vh * 0.55) idx = 0;
      setActive(idx);
    }
  }
  function onScroll() { if (!ticking) { ticking = true; requestAnimationFrame(update); } }
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  update();

  /* ---------- Hero slideshow (only when the admin added two or more slides) ---------- */
  var slideBox = $('.hero-media[data-slides]');
  if (slideBox && !reduced) {
    var slides = $$('.hero-slide', slideBox);
    var delay = Math.max(3, parseInt(slideBox.getAttribute('data-slides'), 10) || 6) * 1000;
    var current = 0;
    setInterval(function () {
      if (document.hidden || slides.length < 2) return;
      slides[current].classList.remove('is-active');
      current = (current + 1) % slides.length;
      slides[current].classList.add('is-active');
    }, delay);
  }

  /* ---------- Catalogue refine: submit when a choice changes (it is a plain GET form without JS) ---------- */
  $$('form[data-autosubmit]').forEach(function (f) {
    $$('select', f).forEach(function (s) { s.addEventListener('change', function () { $$('select', f).forEach(function (x) { if (!x.value) x.disabled = true; }); f.submit(); }); });
  });

  /* ---------- Inquiry list: pick several products, send one inquiry (kept in this browser only) ---------- */
  var INQUIRY_KEY = 'jbm.inquiry';
  var bar = $('[data-inquiry-bar]');
  var picked = $('[data-inquiry-picked]');
  function readInquiry() {
    try {
      var list = JSON.parse(localStorage.getItem(INQUIRY_KEY) || '[]');
      return Array.isArray(list) ? list.filter(function (x) { return x && typeof x.name === 'string' && x.name; }).slice(0, 20) : [];
    } catch (err) { return []; }
  }
  function writeInquiry(list) {
    try { localStorage.setItem(INQUIRY_KEY, JSON.stringify(list.slice(0, 20))); } catch (err) { /* private mode: the list lives for this page only */ }
    renderInquiry(list);
  }
  function inquiryHas(list, name) { return list.some(function (x) { return x.name === name; }); }
  function renderInquiryRows(ul, list, removeLabel) {
    ul.textContent = '';
    list.forEach(function (item) {
      var li = document.createElement('li');
      var span = document.createElement('span');
      span.textContent = item.name;
      if (item.code) { var code = document.createElement('span'); code.className = 'inquiry-item-code'; code.textContent = item.code; span.appendChild(code); }
      var rm = document.createElement('button');
      rm.type = 'button'; rm.className = 'inquiry-remove'; rm.setAttribute('data-inquiry-remove', item.name);
      rm.setAttribute('aria-label', removeLabel + ': ' + item.name); rm.textContent = '\u00D7';
      li.appendChild(span); li.appendChild(rm); ul.appendChild(li);
    });
  }
  function renderInquiry(list) {
    list = list || readInquiry();
    $$('[data-inquiry-item]').forEach(function (box) {
      var btn = $('[data-inquiry-add]', box);
      if (!btn) return;
      var on = inquiryHas(list, box.getAttribute('data-name'));
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      var text = $('.prow-add-text', btn);
      if (text) text.textContent = btn.getAttribute(on ? 'data-label-added' : 'data-label-add');
    });
    if (bar) {
      var removeLabel = bar.getAttribute('data-remove') || 'Remove';
      $('[data-inquiry-count]', bar).textContent = list.length;
      renderInquiryRows($('[data-inquiry-listbox]', bar), list, removeLabel);
      bar.hidden = list.length === 0;
      if (!list.length) { $('[data-inquiry-panel]', bar).hidden = true; $('[data-inquiry-toggle]', bar).setAttribute('aria-expanded', 'false'); }
    }
    if (picked) {
      var field = $('#f-items');
      picked.hidden = list.length === 0;
      renderInquiryRows($('ul', picked), list, (bar && bar.getAttribute('data-remove')) || 'Remove');
      if (field) field.value = list.map(function (x) { return x.name + (x.code ? ' (' + x.code + ')' : ''); }).join('\n');
      var sel = $('#f-product');
      if (sel && list.length) {
        var want = list.length === 1 ? list[0].name : 'Other / multiple';
        if ($('option[value="' + want.replace(/"/g, '\\"') + '"]', sel)) sel.value = want;
      }
    }
  }
  function clearInquiry() { writeInquiry([]); }
  document.addEventListener('click', function (e) {
    var t = e.target.closest ? e.target : null;
    if (!t) return;
    var add = t.closest('[data-inquiry-add]');
    if (add) {
      var box = add.closest('[data-inquiry-item]');
      var list = readInquiry(), name = box.getAttribute('data-name');
      list = inquiryHas(list, name) ? list.filter(function (x) { return x.name !== name; }) : list.concat([{ name: name, code: box.getAttribute('data-code') || '' }]);
      writeInquiry(list);
      return;
    }
    var rm = t.closest('[data-inquiry-remove]');
    if (rm) { var n = rm.getAttribute('data-inquiry-remove'); writeInquiry(readInquiry().filter(function (x) { return x.name !== n; })); return; }
    if (t.closest('[data-inquiry-clear]')) { clearInquiry(); return; }
    var tog = t.closest('[data-inquiry-toggle]');
    if (tog && bar) {
      var panel = $('[data-inquiry-panel]', bar), open = panel.hidden;
      panel.hidden = !open; tog.setAttribute('aria-expanded', open ? 'true' : 'false');
      return;
    }
    if (bar && !t.closest('[data-inquiry-bar]')) { $('[data-inquiry-panel]', bar).hidden = true; $('[data-inquiry-toggle]', bar).setAttribute('aria-expanded', 'false'); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && bar && !$('[data-inquiry-panel]', bar).hidden) { $('[data-inquiry-panel]', bar).hidden = true; $('[data-inquiry-toggle]', bar).setAttribute('aria-expanded', 'false'); $('[data-inquiry-toggle]', bar).focus(); }
  });
  window.addEventListener('storage', function (e) { if (e.key === INQUIRY_KEY) renderInquiry(); });
  renderInquiry();

  /* ---------- Inquiry form: saved on the server (works without JS too) ---------- */
  var form = $('#inquiry-form');
  if (form) {
    var status = $('#form-status');
    var submit = $('button[type="submit"]', form);

    // product links in the catalogue pre-select the product
    $$('[data-product]').forEach(function (a) {
      a.addEventListener('click', function () {
        var sel = $('#f-product');
        if (sel) sel.value = a.getAttribute('data-product');
      });
    });

    function setStatus(text, isError) {
      status.textContent = text || '';
      status.className = 'form-status' + (isError ? ' is-error' : '');
    }
    function clearErrors() {
      $$('.field-error', form).forEach(function (p) { p.textContent = ''; });
      $$('[aria-invalid]', form).forEach(function (f) { f.removeAttribute('aria-invalid'); });
    }
    function showErrors(errors) {
      var firstField = null;
      Object.keys(errors).forEach(function (name) {
        var field = form.elements[name];
        var msg = $('#e-' + (field && field.id ? field.id.replace('f-', '') : name));
        if (field) { field.setAttribute('aria-invalid', 'true'); if (!firstField) firstField = field; }
        if (msg) msg.textContent = errors[name][0];
      });
      if (firstField) firstField.focus();
    }

    form.addEventListener('submit', function (e) {
      if (!window.fetch || !window.FormData) return;     // plain POST fallback
      e.preventDefault();
      clearErrors();
      var label = submit.innerHTML;
      submit.disabled = true;
      setStatus(form.getAttribute('data-sending'), false);

      fetch(form.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
        credentials: 'same-origin'
      }).then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (body) { return { res: res, body: body }; });
      }).then(function (r) {
        if (r.res.ok) {
          form.reset();
          clearInquiry();
          setStatus(r.body.message, false);
        } else if (r.res.status === 422 && r.body.errors) {
          setStatus(r.body.message || form.getAttribute('data-check'), true);
          showErrors(r.body.errors);
        } else {
          setStatus(r.body.message || form.getAttribute('data-error'), true);
        }
      }).catch(function () {
        setStatus(form.getAttribute('data-error'), true);
      }).then(function () {
        submit.disabled = false;
        submit.innerHTML = label;
      });
    });
  }

  /* ---------- Page transitions ---------- */
  window.addEventListener('pageshow', function () { document.body.classList.remove('is-leaving'); });
  if (!reduced) {
    document.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('a[href]');
      if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      if (a.target && a.target !== '_self') return;
      if (a.hasAttribute('download') || a.hasAttribute('data-no-transition')) return;
      var u;
      try { u = new URL(a.href, location.href); } catch (err) { return; }
      if (u.protocol !== location.protocol || u.host !== location.host) return;
      if (u.pathname === location.pathname && u.search === location.search) return;   // same-page anchor
      if (/^\/(admin|livewire)/.test(u.pathname)) return;
      e.preventDefault();
      document.body.classList.add('is-leaving');
      setTimeout(function () { location.href = u.href; }, 420);
    });
  }
})();

/* ═══════════════════════════════════════════════════════════
   Kapraa — Animation Engine
   ═══════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  /* ── 1. Page load fade-in ──────────────────────────────── */
  function pageReady() {
    document.body.classList.add('th-ready');
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', pageReady);
  } else {
    pageReady();
  }

  /* ── 2. IntersectionObserver for scroll reveals ─────────── */
  var observerOptions = {
    threshold: 0.12,
    rootMargin: '0px 0px -40px 0px'
  };

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('th-visible');
        observer.unobserve(entry.target); // animate once
      }
    });
  }, observerOptions);

  /* ── 3. Auto-tag elements after DOM is ready ─────────────── */
  function tagElements() {

    /* Product cards — stagger inside each row */
    document.querySelectorAll('.grid-products .item, .row .item').forEach(function (el) {
      el.classList.add('th-reveal');
      observer.observe(el);
    });

    /* Brand / new-arrivals cards */
    document.querySelectorAll(
      '.brand-card, .new-arrivals .card, .new-arrivals .col-6, ' +
      '.new-arrivals .col-md-2, .new-arrivals .col-md-3'
    ).forEach(function (el) {
      el.classList.add('th-reveal', 'th-card-lift');
      observer.observe(el);
    });

    /* Section banners */
    document.querySelectorAll(
      '.home-banner, .collection-hero, .home_banner_2, ' +
      '.page-banner, .section-banner, [class*="banner"]'
    ).forEach(function (el) {
      if (!el.closest('.main-slider') && !el.closest('.slideshow')) {
        el.classList.add('th-reveal-scale');
        observer.observe(el);
      }
    });

    /* Full sections (categories, festive, unstitched etc.) */
    document.querySelectorAll('section').forEach(function (sec, i) {
      if (sec.querySelector('.main-slider, .slideshow')) return; // skip hero
      sec.classList.add('th-reveal');
      sec.style.transitionDelay = (i % 3) * 0.05 + 's';
      observer.observe(sec);
    });

    /* Footer */
    document.querySelectorAll('footer, .footer-section, .footer-wrap').forEach(function (el) {
      observer.observe(el);
    });
  }

  /* ── 4. Also re-run on dynamically loaded content ──────── */
  function observeNewProducts() {
    document.querySelectorAll(
      '.grid-products .item:not(.th-reveal), ' +
      '.row .item:not(.th-reveal)'
    ).forEach(function (el) {
      el.classList.add('th-reveal');
      observer.observe(el);
    });
  }

  /* ── 5. Lazy-load product images with IntersectionObserver */
  var imgObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        var img = entry.target;
        if (img.dataset.src) {
          img.src = img.dataset.src;
          img.classList.add('lazyloaded');
          imgObserver.unobserve(img);
        }
      }
    });
  }, { rootMargin: '200px' });

  function setupLazyImages() {
    document.querySelectorAll('img.lazyload[data-src]').forEach(function (img) {
      imgObserver.observe(img);
    });
  }

  /* ── 6. Smooth scroll for anchor links ───────────────────── */
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href^="#"]');
    if (!a) return;
    var target = document.querySelector(a.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });

  /* ── 7. Navbar shrink on scroll ──────────────────────────── */
  var navbar = document.querySelector('.header-wrapper, .header-section, header, #th-navbar-wrap');
  if (navbar) {
    var lastY = 0;
    window.addEventListener('scroll', function () {
      var y = window.scrollY;
      if (y > 80) {
        navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,.12)';
        navbar.style.transition = 'box-shadow .3s';
      } else {
        navbar.style.boxShadow = '';
      }
      lastY = y;
    }, { passive: true });
  }

  /* ── 8. Run ───────────────────────────────────────────────── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      tagElements();
      setupLazyImages();
      /* Re-check after sliders/carousels finish */
      setTimeout(observeNewProducts, 800);
      setTimeout(observeNewProducts, 2000);
    });
  } else {
    tagElements();
    setupLazyImages();
    setTimeout(observeNewProducts, 800);
  }

  /* Expose so other scripts can trigger re-scan */
  window.thAnimateNewItems = observeNewProducts;
})();

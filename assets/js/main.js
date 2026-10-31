/* =====================================================================
   Simedalis — main.js
   Features: hero slider, mobile nav, scroll-header, gallery lightbox,
             confirm-delete, active nav link.
   ===================================================================== */
(function () {
    'use strict';

    /* ----------------------------------------------------------------
       Helper
    ---------------------------------------------------------------- */
    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

    /* ----------------------------------------------------------------
       Scroll-aware header — transparent only on hero pages
    ---------------------------------------------------------------- */
    var header = $('.site-header');
    if (header) {
        var isHeroPage = header.classList.contains('site-header--hero');
        if (isHeroPage) {
            function onScroll() {
                if (window.scrollY > 60) {
                    header.classList.add('scrolled');
                    header.classList.remove('site-header--hero');
                } else {
                    header.classList.remove('scrolled');
                    header.classList.add('site-header--hero');
                }
            }
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        }
        // Non-hero pages already have class="site-header scrolled" from PHP —
        // no JS needed; they stay solid brown always.
    }

    /* ----------------------------------------------------------------
       Mobile nav toggle
    ---------------------------------------------------------------- */
    var toggle = $('.nav-toggle');
    var nav    = $('.main-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('open');
            toggle.classList.toggle('open', open);
            toggle.setAttribute('aria-expanded', String(open));
        });
        // Close when a link is tapped
        $$('a', nav).forEach(function (a) {
            a.addEventListener('click', function () {
                nav.classList.remove('open');
                toggle.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    /* ----------------------------------------------------------------
       Active nav link (matches current path)
    ---------------------------------------------------------------- */
    $$('.main-nav a').forEach(function (a) {
        var href = a.getAttribute('href') || '';
        var path = window.location.pathname;
        // strip query/hash for comparison
        var hPath = href.split('?')[0].split('#')[0];
        if (hPath && path.endsWith(hPath.replace(/^.*\//, '')) ) {
            a.classList.add('active');
        }
    });

    /* ----------------------------------------------------------------
       Hero Slider
    ---------------------------------------------------------------- */
    var hero = $('.hero');
    if (hero) {
        var track   = $('.hero__track', hero);
        var slides  = $$('.hero__slide', hero);
        var prevBtn = $('.hero__btn--prev', hero);
        var nextBtn = $('.hero__btn--next', hero);
        var dotsWrap = $('.hero__dots', hero);
        var total   = slides.length;
        var current = 0;
        var timer   = null;
        var INTERVAL = 5000;

        if (total < 2) {
            // single slide — just activate zoom
            if (slides[0]) slides[0].classList.add('active');
            return;
        }

        /* Build dots */
        var dots = [];
        if (dotsWrap) {
            slides.forEach(function (_, i) {
                var d = document.createElement('button');
                d.className = 'hero__dot';
                d.setAttribute('aria-label', 'Go to slide ' + (i + 1));
                d.addEventListener('click', function () { goTo(i); resetTimer(); });
                dotsWrap.appendChild(d);
                dots.push(d);
            });
        }

        function goTo(idx) {
            slides[current].classList.remove('active');
            current = (idx + total) % total;
            track.style.transform = 'translateX(-' + (current * 100) + '%)';
            slides[current].classList.add('active');
            dots.forEach(function (d, i) { d.classList.toggle('active', i === current); });
        }

        function next() { goTo(current + 1); }
        function prev() { goTo(current - 1); }

        function startTimer() { timer = setInterval(next, INTERVAL); }
        function resetTimer() { clearInterval(timer); startTimer(); }

        if (prevBtn) prevBtn.addEventListener('click', function () { prev(); resetTimer(); });
        if (nextBtn) nextBtn.addEventListener('click', function () { next(); resetTimer(); });

        // Swipe support
        var touchStartX = 0;
        hero.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].clientX;
        }, { passive: true });
        hero.addEventListener('touchend', function (e) {
            var dx = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(dx) > 50) { dx < 0 ? next() : prev(); resetTimer(); }
        }, { passive: true });

        // Keyboard
        document.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft')  { prev(); resetTimer(); }
            if (e.key === 'ArrowRight') { next(); resetTimer(); }
        });

        // Init
        goTo(0);
        startTimer();
    }

    /* ----------------------------------------------------------------
       Gallery Lightbox
    ---------------------------------------------------------------- */
    var lightbox = document.getElementById('lightbox');
    if (!lightbox) {
        // Create lightbox element if not in HTML
        lightbox = document.createElement('div');
        lightbox.id = 'lightbox';
        lightbox.className = 'lightbox';
        lightbox.innerHTML =
            '<button class="lightbox__close" aria-label="Close">&times;</button>' +
            '<img class="lightbox__img" src="" alt="">';
        document.body.appendChild(lightbox);
    }
    var lbImg   = lightbox.querySelector('.lightbox__img');
    var lbClose = lightbox.querySelector('.lightbox__close');

    function openLightbox(src, alt) {
        lbImg.src = src;
        lbImg.alt = alt || '';
        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        lightbox.classList.remove('open');
        document.body.style.overflow = '';
        lbImg.src = '';
    }

    // Attach to all .gallery-item images
    $$('.gallery-item img').forEach(function (img) {
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', function () {
            openLightbox(img.src, img.alt);
        });
    });
    // Also clicking the figure
    $$('.gallery-item').forEach(function (fig) {
        fig.addEventListener('click', function (e) {
            var img = fig.querySelector('img');
            if (img && e.target !== img) openLightbox(img.src, img.alt);
        });
    });

    if (lbClose) lbClose.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) closeLightbox();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && lightbox.classList.contains('open')) closeLightbox();
    });

    /* ----------------------------------------------------------------
       Confirm-delete prompts
    ---------------------------------------------------------------- */
    $$('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!window.confirm(el.getAttribute('data-confirm') || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

})();

    /* ----------------------------------------------------------------
       Language corner dropdown
    ---------------------------------------------------------------- */
    var langCorner  = document.getElementById('langCorner');
    var langTrigger = document.getElementById('langTrigger');
    var langDropdown = document.getElementById('langDropdown');

    if (langCorner && langTrigger && langDropdown) {

        langTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = langCorner.classList.toggle('open');
            langTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Close when clicking outside
        document.addEventListener('click', function (e) {
            if (!langCorner.contains(e.target)) {
                langCorner.classList.remove('open');
                langTrigger.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                langCorner.classList.remove('open');
                langTrigger.setAttribute('aria-expanded', 'false');
            }
        });
    }

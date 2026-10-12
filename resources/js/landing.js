/**
 * Landing page scroll animations (GSAP + ScrollTrigger).
 * Staggered fade-up reveals for the stats, features, documents and steps grids.
 * Isolated entry — the marketing page doesn't need Alpine or the app bundle.
 */
import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';

function revealAll() {
    document.querySelectorAll('.reveal-up').forEach((el) => {
        el.style.opacity = '1';
        el.style.transform = 'none';
    });
}

function initReveals() {
    // Respect reduced-motion: show everything instantly, no animation.
    const reduceMotion =
        window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) {
        revealAll();
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    // Containers listed top-to-bottom in page order so ScrollTriggers refresh correctly.
    const groups = ['.stats-grid', '.feat-grid', '.erp-grid', '.doc-grid', '.steps'];

    groups.forEach((selector) => {
        const container = document.querySelector(selector);
        if (!container) return;

        const items = container.querySelectorAll('.reveal-up');
        if (!items.length) return;

        gsap.to(items, {
            opacity: 1,
            y: 0,
            duration: 0.6,
            ease: 'power2.out',
            stagger: 0.15, // one-by-one, not all together
            scrollTrigger: {
                trigger: container,
                start: 'top 85%', // fire when the grid is ~85% down the viewport
                once: true, // play the reveal a single time
            },
        });
    });
}

if (document.readyState !== 'loading') {
    initReveals();
} else {
    document.addEventListener('DOMContentLoaded', initReveals);
}


/** Hero slider: autoplay, arrows, dots, swipe; pauses on hover/focus/hidden tab; static for reduced motion. */
function initHeroSlider() {
    const root = document.querySelector('[data-hero-slider]');
    if (!root) return;
    const slides = [...root.querySelectorAll('[data-slide]')];
    const dots = [...root.querySelectorAll('[data-hs-dot]')];
    const bar = root.querySelector('[data-hs-bar]');
    if (slides.length < 2) return;
    const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const DELAY = 6500;
    let index = 0, timer = null, started = 0, remaining = DELAY, paused = false;

    const show = (n) => {
        index = (n + slides.length) % slides.length;
        slides.forEach((s, i) => {
            s.classList.toggle('is-active', i === index);
            i === index ? s.removeAttribute('aria-hidden') : s.setAttribute('aria-hidden', 'true');
        });
        dots.forEach((d, i) => { d.classList.toggle('is-active', i === index); d.setAttribute('aria-selected', i === index); });
        remaining = DELAY;
        schedule();
    };
    const animateBar = (ms) => {
        if (!bar) return;
        bar.style.transition = 'none'; bar.style.width = ((DELAY - ms) / DELAY * 100) + '%';
        void bar.offsetWidth;
        bar.style.transition = `width ${ms}ms linear`; bar.style.width = '100%';
    };
    const schedule = () => {
        clearTimeout(timer);
        if (reduce || paused) return;
        started = Date.now();
        animateBar(remaining);
        timer = setTimeout(() => show(index + 1), remaining);
    };
    const pause = () => {
        if (paused) return;
        paused = true; clearTimeout(timer);
        remaining = Math.max(400, remaining - (Date.now() - started));
        if (bar) { bar.style.transition = 'none'; bar.style.width = getComputedStyle(bar).width; }
    };
    const resume = () => { if (!paused) return; paused = false; schedule(); };

    root.querySelector('[data-hs-prev]')?.addEventListener('click', () => show(index - 1));
    root.querySelector('[data-hs-next]')?.addEventListener('click', () => show(index + 1));
    dots.forEach((d) => d.addEventListener('click', () => show(Number(d.dataset.hsDot))));
    root.addEventListener('mouseenter', pause);
    root.addEventListener('mouseleave', resume);
    root.addEventListener('focusin', pause);
    root.addEventListener('focusout', resume);
    document.addEventListener('visibilitychange', () => (document.hidden ? pause() : resume()));
    root.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') show(index - 1);
        if (e.key === 'ArrowRight') show(index + 1);
    });
    let x0 = null;
    root.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; }, { passive: true });
    root.addEventListener('touchend', (e) => {
        if (x0 === null) return;
        const dx = e.changedTouches[0].clientX - x0; x0 = null;
        if (Math.abs(dx) > 50) show(index + (dx < 0 ? 1 : -1));
    }, { passive: true });

    show(0);
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initHeroSlider);
else initHeroSlider();

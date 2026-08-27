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
    const groups = ['.stats-grid', '.feat-grid', '.doc-grid', '.steps'];

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

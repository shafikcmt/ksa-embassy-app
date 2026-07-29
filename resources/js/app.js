import './bootstrap';

import Alpine from 'alpinejs';
import { animate, inView } from 'motion';

window.Alpine = Alpine;

Alpine.start();

// Motion One helpers (vanilla, no React required).
// Expose the primitives so Blade views can call them via window.* when needed.
window.animate = animate;
window.inView = inView;

/**
 * Fade + slide cards into view as they enter the viewport.
 * Usage: window.fadeInCards('.card') — call from a Blade @push('scripts') block.
 */
window.fadeInCards = (selector) => {
    inView(selector, (el) => {
        animate(el, { opacity: [0, 1], y: [16, 0] }, { duration: 0.35 });
    });
};

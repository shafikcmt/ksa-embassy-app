import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        // Shared ERP form input class strings (App\Support\ErpForm).
        './app/Support/ErpForm.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Latin: Plus Jakarta Sans; Bangla glyphs fall through to Hind Siliguri (clear at small sizes).
                sans: ['Plus Jakarta Sans', 'Hind Siliguri', 'Figtree', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Primary SaaS accent (indigo/blue). `brand-600` == #2563eb.
                brand: {
                    50:  '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                    950: '#172554',
                },
                // Muted text one step darker for contrast (WCAG AA 4.5:1 on white):
                // text-slate-400 → old 500, 500 → old 600, 600 → old 700. Same for gray.
                slate: { 400: '#64748b', 500: '#475569', 600: '#334155' },
                gray:  { 400: '#6b7280', 500: '#4b5563', 600: '#374151' },
                // Dark navy used by the sidebar shell.
                navy: {
                    700: '#101a33',
                    800: '#0b1430',
                    900: '#0a1124',
                },
            },
            // Readable type scale (was 12 / 14px): small UI text is the app's most common text, so xs/sm are
            // lifted to 13 / 15px with matching line-heights. Headings keep the default scale.
            fontSize: {
                xs: ['0.8125rem', { lineHeight: '1.2rem' }],
                sm: ['0.9375rem', { lineHeight: '1.45rem' }],
            },
            boxShadow: {
                soft: '0 1px 2px rgba(15,23,42,.04), 0 1px 3px rgba(15,23,42,.06)',
                card: '0 4px 16px -6px rgba(15,23,42,.12)',
                lift: '0 18px 40px -20px rgba(15,23,42,.28)',
            },
        },
    },

    plugins: [forms],
};

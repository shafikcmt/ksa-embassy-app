import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', 'Inter', ...defaultTheme.fontFamily.sans],
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
                // Dark navy used by the sidebar shell.
                navy: {
                    700: '#101a33',
                    800: '#0b1430',
                    900: '#0a1124',
                },
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

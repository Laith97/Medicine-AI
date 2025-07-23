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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#fdf2f2',
                    100: '#fce7e7',
                    200: '#f9d2d2',
                    300: '#f4b1b1',
                    400: '#ec8585',
                    500: '#DE6262', // Main color
                    600: '#d14545',
                    700: '#b73535',
                    800: '#9a2e2e',
                    900: '#822a2a',
                },
                accent: {
                    50: '#f0f9f0',
                    100: '#dcf2dc',
                    200: '#bce5bc',
                    300: '#8fd18f',
                    400: '#5bb55b',
                    500: '#3a9a3a', // Coordinated green
                    600: '#2d7d2d',
                    700: '#256325',
                    800: '#204f20',
                    900: '#1c421c',
                },
            },
        },
    },

    plugins: [forms],
};

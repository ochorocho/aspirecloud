import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                typo3: {
                    orange: '#ff8700',
                    'orange-hover': '#e67a00',
                    dark: '#313131',
                    'dark-lighter': '#4a4a4a',
                    light: '#f5f5f5',
                    border: '#e0e0e0',
                },
            },
        },
    },

    plugins: [forms, typography],
};

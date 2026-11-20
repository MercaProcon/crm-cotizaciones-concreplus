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
                // Paleta de marca Concreplus (naranja/azul). Se mantiene la
                // clave "procon" a propósito en Fase 1 para no tener que
                // tocar cada vista que usa text-procon/bg-procon-*; si se
                // quiere renombrar a "concreplus" en Fase 2, hay que
                // actualizar también todas las clases en resources/views.
                procon: {
                    DEFAULT: '#ed8300',
                    dark: '#003060',
                    light: '#FDF1E1',
                    accent: '#EAF1F8',
                },
                facades: {
                    primary: '#00438A',
                    'primary-dark': '#003269',
                    'primary-light': '#E8EEF6',
                    secondary: '#007856',
                    'secondary-dark': '#00593F',
                    'secondary-light': '#E6F4EF',
                },
            },
        },
    },

    plugins: [forms],
};

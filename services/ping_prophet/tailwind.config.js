//nuxt ui vue
import tailwindColors from './node_modules/tailwindcss/colors'
const colorSafeList = []
const deprecated = ['lightBlue', 'warmGray', 'trueGray', 'coolGray', 'blueGray']
for (const colorName in tailwindColors) {
    if (deprecated.includes(colorName))
        continue

    const shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900]

    const pallette = tailwindColors[colorName]

    if (typeof pallette === 'object') {
        shades.forEach((shade) => {
            if (shade in pallette) {
                colorSafeList.push(`text-${colorName}-${shade}`),
                    colorSafeList.push(`accent-${colorName}-${shade}`),
                    colorSafeList.push(`bg-${colorName}-${shade}`),
                    colorSafeList.push(`hover:bg-${colorName}-${shade}`),
                    colorSafeList.push(`focus:bg-${colorName}-${shade}`),
                    colorSafeList.push(`ring-${colorName}-${shade}`),
                    colorSafeList.push(`focus:ring-${colorName}-${shade}`),
                    colorSafeList.push(`border-${colorName}-${shade}`)
            }
        })
    }
}
//-----

import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        'node_modules/nuxt-ui-vue/dist/theme/*.{js,jsx,ts,tsx,vue}',
    ],
    safelist: colorSafeList,
    
    darkMode: 'class',
    theme: {
        extend: {
            colors: tailwindColors,
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, typography],
};

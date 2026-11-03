const cv = (name) => `rgb(var(--c-${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './storage/framework/views/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                heading: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            maxWidth: { page: '1280px' },
            colors: {
                page: cv('page'),
                card: cv('card'),
                primary: { DEFAULT: cv('primary'), dark: cv('primary-dark'), deep: cv('primary-deep') },
                ink: cv('ink'),
                body: cv('body'),
                line: cv('line'),
                success: { DEFAULT: cv('success'), text: cv('success-text') },
                error: { DEFAULT: cv('error'), container: cv('error-container'), on: cv('error-on') },
                surface: { DEFAULT: cv('surface'), low: cv('surface-low'), high: cv('surface-high') },
                slate: Object.fromEntries([50,100,200,300,400,500,600,700,800,900].map((n) => [n, cv(`slate-${n}`)])),
            },
        },
    },
    plugins: [],
};

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/**/*.php",
        "./resources/**/*.html",
        "./public/**/*.php",
        "./public/**/*.html",
    ],
    theme: {
        extend: {
            colors: {
                primary: '#f97316', // Ajustaré estos a naranja/verde según se vea
                secondary: '#1e293b',
                accent: '#22c55e',
                background: '#f8fafc',
            },
            fontFamily: {
                sans: ['Outfit', 'sans-serif'],
            },
            fontWeight: {
                thin: '300',
                regular: '400',
                bold: '700',
                black: '900',
            }
        },
    },
    plugins: [],
}

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/**/*.php",
        "./resources/**/*.html",
        "./public/**/*.php",
        "./public/**/*.html",
    ],
    safelist: [
        'bg-amber-50', 'text-amber-800', 'border-amber-200',
        'bg-emerald-50', 'text-emerald-800', 'border-emerald-200',
        'bg-blue-50', 'text-blue-800', 'border-blue-200',
        'bg-indigo-50', 'text-indigo-800', 'border-indigo-200',
        'bg-teal-50', 'text-teal-800', 'border-teal-200',
        'bg-red-50', 'text-red-800', 'border-red-200',
        'bg-gray-50', 'text-gray-700', 'border-gray-200'
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

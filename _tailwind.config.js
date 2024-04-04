/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./templates/*.{twig,html,js}'],
  colors: {
        'custom-green': 'rgba(75, 192, 192, 1)',
        'custom-orange': 'rgba(255, 159, 64, 1)',
      },
  theme: {
    extend: {
      colors: {
        'custom-green': 'rgba(75, 192, 192, 1)',
        'custom-orange': 'rgba(255, 159, 64, 1)',
      },
      backgroundColor: {
        'en': '#ef4444',
        'es': '#f97316',
        'fr': '#eab308', 
        'de': '#84cc16',
        'it': '#14b8a6',
        'pt': '#0ea5e9',
        'zh': '#8b5cf6',
        'cr': '#ec4899',
        'ba': '#f43f5e',
        'ar': '#06b6d4',
      },
    },
  },
  plugins: [],
}
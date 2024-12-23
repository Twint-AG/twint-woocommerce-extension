/** @type {import('tailwindcss').Config} */
module.exports = {
  prefix: 'tw-',
  content: [
    './src/includes/Admin/**/*.php',
    './resources/js/admin/**/*.js',
    './resources/scss/admin/**/*.scss',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}

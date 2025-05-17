/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.{html,php}",
    "./src/**/*.{html,js,php}"
  ],
  theme: {
    extend: {
      colors: {
        'eva': '#1c2434',
        'modern': '#e9f4ff',
      },
    },
  },
  plugins: [],
};

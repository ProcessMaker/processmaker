/** @type {import('tailwindcss').Config} */
module.exports = {
  prefix: "tw-",
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
  safelist: [
    {
      pattern: /(bg|outline|border|text)-(gray|purple|blue|amber|green|gray|emerald|red)-(100|200|300|400|500)/,
      variants: ["hover"],
    },
  ],
};

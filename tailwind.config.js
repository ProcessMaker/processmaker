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
  corePlugins: {
    preflight: false,
  },
  safelist: [
    {
      pattern: /(outline|border|text)-(gray|purple|blue|amber|green|gray|emerald|red|orange)-(100|200|300|400|500|600|700|800|900)/,
      variants: ["hover"],
    },
    {
      pattern: /(bg)-([^-]+)-([0-9]+)/,
      variants: ["hover"],
    },
  ],
};

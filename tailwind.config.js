/** @type {import('tailwindcss').Config} */
const defaultTheme = require("tailwindcss/defaultTheme");
const plugin = require('tailwindcss/plugin');
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./app//View/**/*.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./resources/**/*.js",
  ],
  safelist: ["swiper-pagination-bullet"],
  theme: {
    extend: {
      width: {
        "250": "250px",
        "18": "4.5rem",
      },
      height: {
        "18": "4.5rem",
      },
      maxWidth: {
        '22': '5.3rem',
      },
      minWidth: {
        "18": "4.5rem",
        '22': '5.3rem',
      },
      boxShadow: {
        'calendar': '0px 20px 40px 0px rgba(0, 0, 0, 0.05)',
        'interactive-s': '0px 0px 3px 0px rgba(31, 52, 98, 0.28)',
        'card-review': '0px 2px 4px -2px rgba(0, 0, 0, 0.1), 0px 4px 6px -1px rgba(0, 0, 0, 0.1)',
        'tag-service': '0px 0px 1.8px 0px rgba(31, 52, 98, 0.26)',
        'top-bottom': '0 -4px 8px -1px rgba(0, 0, 0, 0.05), 0 4px 8px -1px rgba(0, 0, 0, 0.05)',
      },
      borderRadius: {
        "20": "1.25rem",
      },
      backgroundImage: {
        "radial-gradient": "radial-gradient(var(--tw-gradient-stops))",
      },
      backgroundColor: {
        strong: "#dde0e4",
      },
      grayscale: {
        70: '70%',
      },
      colors: {
        surface: "#fff",
        "surface-subdued": "#EBF0F3",
        "icon-subdued": "#BABFC1",
        interactive: "#1F3462",
        "interactive-50": "rgba(31, 52, 98, 0.5)",
        "interactive-hovered": "#40598E",
        "interactive-button-hovered": "#241F62",
        heading: "#1F3462",
        backdrop: "#1F3462",
        "action-primary-light": "#FDEDDD",
        "action-primary": "#F5841F",
        "action-primary-hovered": "#F56C1F",
        "action-primary-pressed": "#F56C1F",
        "action-secondary": "#384349",
        "action-secondary-hovered": "#12181B",
        "action-secondary-pressed": "#12181B",
        vk: "#4E7CBF",
        disabled: "#ebecef",
        "border-interactive-focus": "rgba(62, 125, 213, 1)",
        "blue-label": "#3981F1",
        "blue-lite": "#83C3FF",
        "blue-superlite": "#ECF3FF",
        "action-primary-100": "#fcddbf",
        "action-primary-200": "#fabe88",
        "action-primary-300": "#f79c4a",
        "action-primary-400": "#f58420",
        "body-gray": "#E5E7EB",
        "blue-gray": "#CBD3E5",
        "light-gray": "#F6F7F9",
        "blue-dark": "#0D103B",
      },
      textColor: {
        subdued: "#87909b",
        critical: "#c5280c",
        disabled: "#A4ACAF",
      },
      fill: {
        "icon-subdued": "#87909b",
      },
      borderColor: {
        default: "#BABFC1",
      },

      container: {
        center: true,
        screens: {
          "2xl": "1306px",
        },
        padding: {
          DEFAULT: "1rem",
        },
      },
      fontSize: {
        "es": "0.625rem",
        "55": "55px",
        "40": "40px",
      },
      fontFamily: {
        sans: ["Gilroy", ...defaultTheme.fontFamily.sans],
        bebas: ["'Bebas Neue Light'", "sans-serif"]
      },
      animation: {
        backdrop: "fade-in 200ms 1 forwards",
        "spin-fast": "spin 500ms linear infinite",
      },
      keyframes: {
        "fade-in": {
          to: { opacity: ".8" },
        },
      },
      screens: {
        "high-contrast": { raw: "(-ms-high-contrast: active)" },
        "forced-colors": { raw: "(forced-colors: active)" },
        "md-down": { max: "767px" },
      },
      backgroundImage: {
        "checkbox-checked": `url("data:image/svg+xml,<svg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'><path d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/></svg>")`,
      },
      letterSpacing: {
        between: '-.04em',
      },
      lineHeight: {
        '14': '4rem',
      }
    },
  },
  plugins: [
    plugin(function ({ addVariant }) {
      addVariant('accessibility', '[data-accessibility="true"] &');
    }),
  ],
};

import type { Config } from 'tailwindcss';

const config: Config = {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif']
      },
      boxShadow: {
        soft: '0 18px 45px rgba(15, 23, 42, 0.08)',
        panel: '0 10px 30px rgba(15, 23, 42, 0.08)'
      },
      colors: {
        brand: {
          50: '#eef4ff',
          100: '#e0e7ff',
          500: '#2563eb',
          700: '#1d4ed8'
        },
        surface: {
          950: '#0f172a'
        }
      }
    }
  },
  plugins: []
};

export default config;

import type { Config } from 'tailwindcss';

const config: Config = {
  darkMode: ['class'],
  content: [
    './app/**/*.{ts,tsx}',
    './components/**/*.{ts,tsx}',
    './hooks/**/*.{ts,tsx}',
    './lib/**/*.{ts,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        surface: 'hsl(220 20% 98%)',
        ink: 'hsl(222 47% 11%)',
        muted: 'hsl(215 16% 47%)',
        accent: 'hsl(221 83% 53%)',
        success: 'hsl(142 71% 45%)',
        warning: 'hsl(38 92% 50%)',
        danger: 'hsl(0 72% 51%)',
      },
      boxShadow: {
        glass: '0 10px 30px rgba(15, 23, 42, 0.08)',
      },
    },
  },
  plugins: [],
};

export default config;

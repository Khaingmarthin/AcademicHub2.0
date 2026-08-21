/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./pages/**/*.php",
    "./admin/**/*.php",
    "./includes/**/*.php",
    "./actions/**/*.php"
  ],
  theme: {
    extend: {
      /* ------------------------------------------------------------------
         UCS Design Tokens — Modern Academic Editorial
         ------------------------------------------------------------------ */

      colors: {
        // Primary blue — institutional, academic, trustworthy
        ucs: {
          primary: '#2563eb',       // blue-600
          'primary-hover': '#1d4ed8', // blue-700
          'primary-light': '#eff6ff', // blue-50
          'primary-muted': '#dbeafe', // blue-100

          // Surfaces
          surface: '#ffffff',
          'surface-alt': '#f8fafc',  // slate-50
          'surface-dim': '#f1f5f9',  // slate-100

          // Borders
          border: '#e2e8f0',         // slate-200
          'border-light': '#f1f5f9', // slate-100
          'border-focus': '#2563eb',

          // Text
          'text-primary': '#0f172a',   // slate-900
          'text-secondary': '#475569',  // slate-600
          'text-muted': '#94a3b8',      // slate-400
          'text-inverse': '#ffffff',

          // Semantic — keep existing system colors for admin status
          success: '#059669',  // emerald-600
          warning: '#d97706',  // amber-600
          danger: '#dc2626',   // red-600
          info: '#2563eb',     // same as primary
        },

        // Keep standard Tailwind palette for backward compatibility.
        // Pages that already use `bg-blue-600`, `text-gray-900`, etc. will
        // continue to work. The `ucs-*` tokens above are the preferred way
        // forward for new/updated components.
      },

      fontFamily: {
        sans: [
          'Inter',
          'ui-sans-serif',
          'system-ui',
          '-apple-system',
          'BlinkMacSystemFont',
          '"Segoe UI"',
          'Roboto',
          '"Helvetica Neue"',
          'Arial',
          '"Noto Sans"',
          'sans-serif',
        ],
        // Keep serif for editorial/decorative accents if needed later
        serif: [
          'Merriweather',
          'ui-serif',
          'Georgia',
          'Cambria',
          '"Times New Roman"',
          'Times',
          'serif',
        ],
      },

      fontSize: {
        // Editorial scale — tightened for readability
        'display': ['3rem',     { lineHeight: '1.1',  fontWeight: '800', letterSpacing: '-0.025em' }],   // 48px
        'display-sm': ['2.25rem',{ lineHeight: '1.15', fontWeight: '800', letterSpacing: '-0.02em' }],   // 36px
        'heading': ['1.875rem', { lineHeight: '1.2',  fontWeight: '800', letterSpacing: '-0.02em' }],   // 30px
        'heading-sm': ['1.5rem',{ lineHeight: '1.25', fontWeight: '700', letterSpacing: '-0.015em' }],  // 24px
        'subheading': ['1.25rem',{ lineHeight: '1.35', fontWeight: '700', letterSpacing: '-0.01em' }],  // 20px
        'body': ['1rem',        { lineHeight: '1.65', fontWeight: '400' }],                              // 16px
        'body-sm': ['0.875rem', { lineHeight: '1.6',  fontWeight: '400' }],                              // 14px
        'caption': ['0.75rem',  { lineHeight: '1.5',  fontWeight: '500' }],                              // 12px
        'overline': ['0.6875rem',{ lineHeight: '1',   fontWeight: '600', letterSpacing: '0.12em' }],     // 11px
      },

      spacing: {
        // Consistent 4px grid — explicit tokens for key values
        '4.5': '1.125rem',  // 18px — between section label and heading
        '18': '4.5rem',     // 72px — large section vertical padding
        '22': '5.5rem',     // 88px — extra-large section padding
        '26': '6.5rem',     // 104px — hero vertical padding
        '30': '7.5rem',     // 120px — max hero padding
      },

      borderRadius: {
        // Conservative radius scale — academic, not bubbly
        'card': '0.75rem',     // 12px — default card radius
        'widget': '0.625rem',  // 10px — form inputs, smaller widgets
        'pill': '9999px',      // full — badges, tags
      },

      boxShadow: {
        // Minimal, intentional shadows — not decorative
        'surface': '0 1px 3px rgb(15 23 42 / 0.04), 0 1px 2px rgb(15 23 42 / 0.06)',
        'surface-hover': '0 4px 12px rgb(15 23 42 / 0.08)',
        'elevated': '0 8px 24px rgb(15 23 42 / 0.1)',
        'focus': '0 0 0 3px rgb(37 99 235 / 0.15)',
      },

      borderWidth: {
        '3': '3px',
      },

      maxWidth: {
        // Content width tokens
        'prose': '42rem',    // 672px — editorial reading width
        'content': '56rem',  // 896px — standard content area
        'wide': '72rem',     // 1152px — wide content area (admin tables)
        'page': '80rem',     // 1280px — full page width (admin layout)
      },

      transitionDuration: {
        '150': '150ms',
        '200': '200ms',
        '300': '300ms',
      },

      transitionTimingFunction: {
        'out': 'cubic-bezier(0, 0, 0.2, 1)',
      },
    },
  },
  plugins: [],
}

import defaultTheme from 'tailwindcss/defaultTheme';
// import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    important: true,
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.{html,js,ts,vue,blade.php}',
        "./node_modules/flowbite/**/*.js",
    ],
    theme: {
        screens: {
            sm: '480px',
            md: '768px',
            lg: '976px',
            xl: '1440px',
        },
        colors: {
          'purple': '#7e5bef',
          'pink': '#ff49db',
          'orange': '#ff7849',
          'green': '#13ce66',
          'yellow': '#ffc82c',
          'gray-dark': '#273444',
          'gray': '#8492a6',
          'gray-light': '#d3dce6',
          white: '#ffffff',
          black: '#000000',
          primary: {
            DEFAULT: '#005890',
            hover: '#00436E',
            active: '#00385C',
            disabled: '#C1E7FF',
            800: '#005890',
            700: '#2574A6',
            600: '#4A90BC',
            500: '#6FACD3',
            400: '#81BADE',
            300: '#94C8E9',
            200: '#A6D6F4',
            100: '#B9E4FF',
          },
          secondary: {
            DEFAULT: '#9098A1',
            hover: '#858C94',
            active: '#798087',
            disabled: '#CBCED2',
          },
          tertiary: {
            DEFAULT: '#E87F54',
            hover: '#D96A3A',      // slightly darker for hover
            active: '#C95A2A',     // even darker for active
            disabled: '#F3C6B2',   // light desaturated for disabled
            800: '#E87F54',        // base
            700: '#E16B3B',        // a bit darker
            600: '#D95A3A',        // darker
            500: '#C95A2A',        // more dark
            400: '#F09B7A',        // lighter
            300: '#F3B08F',        // even lighter
            200: '#F6C5A4',        // pale
            100: '#F9DAC0',        // very pale
          },
          neutral: {
            DEFAULT: '#6F6F6E',
            700: '#206430',
            600: '#6F6F6E',
            500: '#A3AAB2',
            400: '#D9DDE2',
            300: '#E6E7E8',
            200: '#F5F7FA',
            100: '#FFFFFF',
          },
          success: {
            DEFAULT: '#287D3C',
            hover: '#226C34',
            disabled: '#93C49E',
            600: '#287D3C',
            500: '#4C955D',
            400: '#6FAC7E',
            300: '#93C49E',
            200: '#A5D0AF',
            100: '#B6DBBF',
          },
          info: {
            DEFAULT: '#2E5AAC',
            600: '#2E5AAC',
            500: '#587BBD',
            400: '#829CCD',
            300: '#ABBDDE',
            200: '#C0CEE6',
            100: '#D5DEEE',
          },
          warning: {
            DEFAULT: '#F49A00',
            hover: '#DE8D03',
            disabled: '#FBD799',
            600: '#F49A00',
            500: '#F6AE33',
            400: '#F8C266',
            300: '#FBD799',
            200: '#FCE1B3',
            100: '#FDEBCC',
          },
          alert: {
            DEFAULT: '#DA1414',
            hover: '#B70A0A',
            disabled: '#F0A1A1',
            600: '#DA1414',
            500: '#E14343',
            400: '#E97272',
            300: '#F0A1A1',
            200: '#F4B9B9',
            100: '#F8D0D0',
          },
        },
        fontFamily: {
            sans: ['Montserrat', 'sans-serif'],
            serif: ['Montserrat', 'serif'],
        },
        fontSize: {
            h1: ['38px', '44px'],
            h2: ['33px', '36px'],
            h3: ['28px', '32px'],
            h4: ['24px', '28px'],
            h5: ['21px', '24px'],
            h6: ['18px', '20px'],
        },
        extend: {
            spacing: {
                '128': '32rem',
                '144': '36rem',
            },
            borderRadius: {
                '4xl': '2rem',
            },
        },
    },
    plugins: [
        require('flowbite/plugin')
    ]
};

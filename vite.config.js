import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import postcssRTLCSS from 'postcss-rtlcss';
import purgecss from '@fullhuman/postcss-purgecss';

export default defineConfig(({ mode }) => ({
    plugins: [
        laravel({
            input: ['resources/scss/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    css: {
        postcss: {
            plugins: [
                postcssRTLCSS({ mode: 'override' }),
                ...(mode === 'production'
                    ? [
                          purgecss({
                              content: [
                                  './resources/views/**/*.blade.php',
                                  './resources/js/**/*.js',
                              ],
                              safelist: {
                                  standard: ['show', 'fade', 'collapsing', 'active', 'disabled'],
                                  deep: [/^pagination/, /^page-/],
                                  greedy: [/data-bs-/],
                              },
                              defaultExtractor: (content) =>
                                  content.match(/[\w-/:]+(?<!:)/g) || [],
                          }),
                      ]
                    : []),
            ],
        },
        preprocessorOptions: {
            scss: {
                quietDeps: true,
                silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'mixed-decls'],
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
}));

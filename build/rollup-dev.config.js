import vue from 'rollup-plugin-vue'
import css from 'rollup-plugin-css-only'
import resolve from 'rollup-plugin-node-resolve';
import replace from 'rollup-plugin-replace';

export default {
    input: 'frontend/main.js',
    output: {
        file: 'public/assets/bundle.js',
        format: "esm",
        sourcemap: true
    },

  plugins: [
    resolve(),
    css(),
    vue( { css:false } ),
    replace({
        'process.env.NODE_ENV': JSON.stringify( 'dev' )
    }),
  ]
}
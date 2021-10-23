import babel from '@rollup/plugin-babel';
import inject from '@rollup/plugin-inject'
import resolve from '@rollup/plugin-node-resolve';
import { terser } from 'rollup-plugin-terser';

export default {
  output: {
    format: 'iife',
  },
  plugins: [
    resolve(),
    inject({
      include: 'node_modules/webfontloader/**',
      window: 'global/window',
    }),
    babel({ babelHelpers: 'bundled' }),
    terser(),
  ],
};

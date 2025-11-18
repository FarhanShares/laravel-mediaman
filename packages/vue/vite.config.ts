import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import dts from 'vite-plugin-dts';
import { resolve } from 'path';

export default defineConfig({
  plugins: [
    vue(),
    dts({
      insertTypesEntry: true,
      include: ['src/**/*.ts', 'src/**/*.vue'],
    }),
  ],
  build: {
    lib: {
      entry: resolve(__dirname, 'src/index.ts'),
      name: 'MediaManVue',
      formats: ['es', 'cjs'],
      fileName: (format) => `index.${format === 'es' ? 'esm.' : ''}js`,
    },
    rollupOptions: {
      external: ['vue', '@mediaman/core'],
      output: {
        exports: 'named',
        globals: {
          vue: 'Vue',
          '@mediaman/core': 'MediaManCore',
        },
      },
    },
    sourcemap: true,
  },
});

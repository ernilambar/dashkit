import { defineConfig } from 'vite';
import browserslistToEsbuild from 'browserslist-to-esbuild';

export default defineConfig( {
	build: {
		target: browserslistToEsbuild(),
		lib: {
			entry: 'resources/js/dashkit.js',
			formats: [ 'iife' ],
			name: 'dashkit',
			fileName: () => 'dashkit.js',
		},
		outDir: 'assets',
		emptyOutDir: false,
		rollupOptions: {
			output: {
				assetFileNames: 'dashkit[extname]',
			},
		},
	},
} );

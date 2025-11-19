import tailwindcss from "@tailwindcss/vite";
import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';

export default defineConfig({
	plugins: [sveltekit(), tailwindcss()],
	server: {
		proxy: {
		  '/api': {
			target: 'http://localhost/shenieva-teacher/src/lib/api',
			changeOrigin: true,
			rewrite: (path) => path.replace(/^\/api/, '')
		  }
		},
		watch: {
      ignored: ['**/src/lib/api/**/*', '**/src/server-logs/**/*']
    }
	  }
});

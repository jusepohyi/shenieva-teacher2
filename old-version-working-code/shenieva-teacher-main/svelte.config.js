// Use the static adapter for hosts that don't allow running Node (e.g. many shared PHP hosts).
// Keep the previous adapter-node config commented out in case you need a Node server locally.
import adapter from '@sveltejs/adapter-static';

/** @type {import('@sveltejs/kit').Config} */
const config = {
	kit: {
		adapter: adapter({
			// output directories for the static build
			pages: 'build',
			assets: 'build',
			// fallback index for SPA-style routing (serve index.html for unknown paths)
			fallback: 'index.html'
		})
	}
};

export default config;

// If you later need a Node server during development or on a Node-capable host,
// switch back to @sveltejs/adapter-node and rebuild.
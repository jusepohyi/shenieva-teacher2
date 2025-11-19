// Lightweight image preloader utility
// Usage: import { preloadImages } from '$lib/preload';
// await preloadImages(urls, (progress) => { /* progress: 0..1 */ })

/**
 * Preload a list of image URLs.
 * @param {string[]} urls
 * @param {(progress:number) => void} onProgress
 * @returns {Promise<void>}
 */
export function preloadImages(urls = [], onProgress = () => {}) {
  return /** @type {Promise<void>} */ (new Promise((resolve) => {
    const total = urls.length;
    if (total === 0) {
      try { onProgress(1); } catch (e) {}
      resolve();
      return;
    }

    let loaded = 0;

    const doneOne = () => {
      loaded += 1;
      try {
        onProgress(loaded / total);
      } catch (e) {
        // ignore progress callback errors
      }
      if (loaded === total) resolve();
    };

    for (const url of urls) {
      try {
        const img = new Image();
        // allow cross-origin images if served from CDN
        img.crossOrigin = 'anonymous';
        img.onload = doneOne;
        img.onerror = (e) => {
          // still count errors as "loaded" to avoid stalling
          console.warn('Image failed to load:', url, e);
          doneOne();
        };
        img.src = url;
      } catch (err) {
        console.warn('preloadImages error for', url, err);
        doneOne();
      }
    }
  }));
}

// Helper: progressively preload in batches to reduce memory spike
/**
 * @param {string[]} urls
 * @param {(progress:number) => void} onProgress
 * @param {number} batchSize
 */
export async function preloadImagesBatched(urls = [], onProgress = () => {}, batchSize = 20) {
  const total = urls.length;
  let done = 0;
  for (let i = 0; i < urls.length; i += batchSize) {
    const slice = urls.slice(i, i + batchSize);
    // p is batch progress 0..1
    await preloadImages(slice, (p) => {
      onProgress((done + p * slice.length) / total);
    });
    done += slice.length;
  }
}

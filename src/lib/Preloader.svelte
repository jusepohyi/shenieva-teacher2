<script>
  import { onMount } from 'svelte';
  import { preloadImagesBatched } from '$lib/preload';
  import { goto } from '$app/navigation';

  // You can pass either `assetUrls` (an array of compiled URLs) or `assetGlobs`.
  // Note: Vite's import.meta.glob requires literal strings at compile time,
  // so prefer passing `assetUrls` from the caller (we do that in +layout.svelte).
  export let assetGlobs = [
    '/src/assets/**/**.{png,jpg,jpeg,webp,gif,svg}',
    '/static/assets/**/**.{png,jpg,jpeg,webp,gif,svg}'
  ];
  export let assetUrls = []; // optional pre-expanded urls passed by caller
  export let batchSize = 30;
  export let onComplete = () => {}; // optional callback after preload
  // allow user to skip waiting for these assets (will still load in background)
  export let allowSkip = false;

  let progress = 0;
  let status = 'Preparing...';

  // Collect asset URLs. Prefer `assetUrls` when provided (caller expands via import.meta.glob).
  function collectUrls() {
    // Only use `assetUrls` - dynamic expansion using variables is not safe with Vite's import.meta.glob.
    if (Array.isArray(assetUrls) && assetUrls.length > 0) {
      return assetUrls.slice();
    }
    // No pre-expanded URLs provided. Return empty so caller can handle fallback.
    return [];
  }

  let aborted = false;

  function skip() {
    aborted = true;
    status = 'Skipped by user';
    progress = 1;
    if (onComplete) {
      // fire but don't await (caller will handle)
      try { onComplete(); } catch (e) { /* ignore */ }
    }
  }

  onMount(async () => {
    const urls = collectUrls();
    if (urls.length === 0) {
      status = 'No assets found to preload';
      progress = 1;
      if (onComplete) onComplete();
      return;
    }

    status = `Preloading ${urls.length} images...`;
    try {
      // start the preload but only await if not aborted — skipping allows continuing without waiting
      const task = preloadImagesBatched(urls, (p) => {
        progress = p;
      }, batchSize);

      if (!aborted) {
        await task;
      }

      if (!aborted) {
        status = 'Assets loaded';
        progress = 1;
        if (onComplete) await onComplete();
      }
    } catch (e) {
      console.error('Preload failed', e);
      status = 'Preload finished (with errors)';
    }
  });
</script>

<style>
  .preloader { display:flex; flex-direction:column; align-items:center; gap:.5rem }
  .bar { width:320px; height:12px; background:#eee; border-radius:6px; overflow:hidden }
  .fill { height:100%; background:linear-gradient(90deg,#7dd3fc,#60a5fa); transition:width .2s }
</style>

<div class="preloader">
  <div>{status}</div>
  <div class="bar" aria-hidden="true">
    <div class="fill" style="width: {Math.round(progress*100)}%"></div>
  </div>
  <div>{Math.round(progress * 100)}%</div>
  {#if allowSkip}
    <button on:click={skip} aria-label="Skip preloading">Skip</button>
  {/if}
</div>

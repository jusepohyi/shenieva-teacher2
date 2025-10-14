<script lang="ts">
  export let storyKey: string | undefined;
  let Slide: any = null;

  $: if (storyKey) {
    // dynamic import from the selected story folder
    (async () => {
      try {
        const module = await import(/* @vite-ignore */ `../../Levels/Level3/${storyKey}/slide_1.svelte`);
        Slide = module.default;
      } catch (e) {
        console.error('Failed to load story slide_1 for', storyKey, e);
        Slide = null;
      }
    })();
  } else {
    // fallback to root chooser slide
    (async () => {
      try {
        const module = await import('../../Levels/Level3/slide_1.svelte');
        Slide = module.default;
      } catch (e) {
        console.error('Failed to load fallback Level3 slide_1', e);
        Slide = null;
      }
    })();
  }
</script>

{#if Slide}
  <svelte:component this={Slide} {storyKey} />
{:else}
  <div>Loading...</div>
{/if}

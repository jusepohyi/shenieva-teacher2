<script lang="ts">
  export let storyKey: string | undefined;
  let Slide: any = null;

  $: if (storyKey) {
    (async () => {
      try {
        const module = await import(/* @vite-ignore */ `../../Levels/Level3/${storyKey}/slide_3.svelte`);
        Slide = module.default;
      } catch (e) {
        console.error('Failed to load story slide_3 for', storyKey, e);
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

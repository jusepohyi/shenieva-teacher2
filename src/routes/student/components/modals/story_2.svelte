<script lang="ts">
  import Level2 from './level_2.svelte';
  export let storyKey: string = '';
  export let showModal: boolean = false;
  export let onClose: () => void = () => {};
</script>

<svelte:component this={Level2} {storyKey} {showModal} {onClose} />
<script lang="ts">
  import Level1 from './level_1.svelte';
  export let storyKey: string = '';
  export let showModal: boolean = false;
  export let onClose: () => void = () => {};
</script>

<!-- Wrapper: forward props to the real Level1 modal -->
<svelte:component this={Level1} {storyKey} {showModal} {onClose} />
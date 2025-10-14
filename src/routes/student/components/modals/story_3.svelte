
<script lang="ts">
  import Level3 from './level_3.svelte';
  export let showModal: boolean = false;
  export let onClose: () => void = () => {};
  export let storyKey: string = '';
</script>

<svelte:component this={Level3} {storyKey} {showModal} {onClose} />

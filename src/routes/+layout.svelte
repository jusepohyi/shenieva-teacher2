<script>
	import '../app.css';
	import AudioControl from '$lib/components/AudioControl.svelte';
	import { onMount } from 'svelte';
	import { audioStore } from '$lib/store/audio_store';
	
	let { children } = $props();
	
	onMount(() => {
		// Initialize audio system
		audioStore.init();
	});
</script>

{@render children()}

<!-- Global floating audio control -->
<AudioControl />

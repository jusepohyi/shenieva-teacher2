<!-- student/+layout.svelte -->
<script lang="ts">
    import { page } from '$app/stores';
</script>

<div class="min-h-screen w-screen flex flex-col">
    <slot />
</div>

<style>
    :global(html, body, #app) {
        margin: 0;
        padding: 0;
        height: 100%;
        width: 100%;
        overflow: hidden;
        font-family: 'Comic Sans MS', cursive;
    }

    :global(*) {
        box-sizing: border-box;
    }
</style>
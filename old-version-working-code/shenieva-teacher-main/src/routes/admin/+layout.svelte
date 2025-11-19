<script>
    import Sidebar from '$lib/components/admin/Sidebar.svelte';
</script>

<div class="h-screen flex">
    
    <!-- Sidebar (Fixed Width & Full Height) -->
    <aside class="w-64 h-full bg-gray-800 text-white shrink-0">
        <Sidebar />
    </aside>

    <!-- Main Content (Takes Remaining Space) -->
    <main class="flex-1 p-4 overflow-auto">
        <slot />
    </main>
</div>

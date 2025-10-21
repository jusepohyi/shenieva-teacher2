<script>

	import { Dropdown } from 'flowbite-svelte';
    import { fly } from 'svelte/transition';
    import { writable } from 'svelte/store';
  import { flip } from 'svelte/animate';
    import Attendance from './attendance.svelte';

        let activeTab = writable("attendance");
</script>


<div class=" text-gray-500 font-bold text-2xl pl-10">
        <h1>Attendance Record</h1>
</div>

<div class="p-6 pb-0 max-w-6xl mx-auto">
    <!-- Only Attendance tab is available -->
    <div class="flex space-x-2 border-b-2 pb-2 justify-start">
        <button 
            class="px-4 py-2 font-semibold rounded-lg bg-orange-500 text-white"
        >
            Attendance
        </button>
    </div>
</div>



    <!-- Tab Content with Smooth Sliding Transition -->
    <div class="relative overflow-hidden">
        {#if $activeTab === "attendance"}
            <div in:fly={{ y: -50, opacity: 0 }} out:fly={{ y: -50, opacity: 0 }}>
                <Attendance/>
            </div>
        {/if}
        {#if $activeTab === "quiz1"}
            <div in:fly={{ y: -50, opacity: 0 }} out:fly={{ y: -50, opacity: 0 }}>
            <Quiz1/>
            </div>
        {/if}
        {#if $activeTab === "quiz2"}
            <div in:fly={{ y: -50, opacity: 0 }} out:fly={{ y: -50, opacity: 0 }}>
                <Quiz2/>
            </div>
        {/if}
        {#if $activeTab === "quiz3"}
            <div in:fly={{ y: -50, opacity: 0 }} out:fly={{ y: -50, opacity: 0 }}>
                <Quiz3/>
            </div>
        {/if}
    </div>


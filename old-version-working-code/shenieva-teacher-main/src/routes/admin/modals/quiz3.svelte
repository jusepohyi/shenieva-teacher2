<script>
    import { createEventDispatcher } from "svelte";
    import { onMount } from "svelte";

    export let selectedPerson;
    const dispatch = createEventDispatcher();

    function closeModal() {
        dispatch("close");
    }

    onMount(() => {
        document.addEventListener("keydown", handleEscape);
    });

    function handleEscape(event) {
        if (event.key === "Escape") {
            closeModal();
        }
    }
</script>

<div class="fixed inset-0 flex items-center justify-center bg-black/30 z-30" on:click={closeModal}>
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full" on:click|stopPropagation>
        <h2 class="text-xl font-bold mb-4">Person Details</h2>
        {#if selectedPerson}
            <p><strong>Name:</strong> {selectedPerson.name}</p>
            <p><strong>Gender:</strong> {selectedPerson.gender}</p>
            <p><strong>Date & Time:</strong> {selectedPerson.datetime}</p>
            <p><strong>Score:</strong> {selectedPerson.score}</p>
            <p><strong>Attempts:</strong> {selectedPerson.attempts}</p>
            <p><strong>Status:</strong> {selectedPerson.status}</p>
        {/if}

        <button class="mt-4 px-4 py-2 bg-red-500 text-white rounded-md" on:click={closeModal}>
            Close
        </button>
    </div>
</div>
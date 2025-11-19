<!-- src/routes/admin/modals/successModal.svelte -->
<script>
    import { fade, scale } from "svelte/transition";
  
    // Props
    export let showSuccessModal;
    export let closeSuccessModal;
    export let personName;
  
    function handleOverlayClick(event) {
      if (event.target === event.currentTarget) {
        closeSuccessModal();
      }
    }
  </script>
  
  {#if showSuccessModal}
    <div
      class="fixed inset-0 bg-gray-800/50 flex items-center justify-center z-70 p-4 cursor-default"
      on:click={handleOverlayClick}
      transition:fade={{ duration: 300 }}
    >
      <div
        class="bg-white rounded-3xl max-w-md w-full p-8 relative shadow-2xl"
        transition:scale={{ duration: 300, start: 0.95 }}
      >
        <div class="text-center">
          <div class="mb-6 flex justify-center">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-20 h-20 text-green-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>
          <h2 class="text-3xl font-extrabold text-gray-900 mb-4">Review Successful</h2>
          <p class="text-gray-600 mb-6 text-lg">
            The quiz for {personName} has been successfully reviewed.
          </p>
          <button
            on:click={closeSuccessModal}
            class="px-6 py-3 bg-lime-500 text-white rounded-full hover:bg-lime-600 transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-lg cursor-pointer"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  {/if}
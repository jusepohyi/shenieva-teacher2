<script>
    import { createEventDispatcher } from 'svelte';
  
    export let ribbonsEarned = 0;
  
    const dispatch = createEventDispatcher();
  
    function handleOk() {
      dispatch('action', { action: 'ok' });
    }
  </script>
  
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-gradient-to-br from-yellow-100 to-orange-100 rounded-2xl shadow-2xl p-8 w-full max-w-md text-center transform scale-100 transition-transform">
      <h2 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 mb-4">
        You Earned Ribbons! 🎀
      </h2>
      <p class="text-2xl text-gray-800 mb-6 animate-bounce">
        Congratulations! You've earned <span class="font-bold text-purple-700">{ribbonsEarned}</span> ribbon(s)!
      </p>
      <button
        on:click={handleOk}
        class="bg-gradient-to-r from-purple-500 to-pink-500 text-white font-bold px-6 py-3 rounded-full hover:scale-105 transition-transform"
      >
        OK
      </button>
    </div>
  </div>
  
  <style>
    .animate-bounce {
      animation: bounce 1s infinite;
    }
  
    @keyframes bounce {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-10px);
      }
    }
  </style>
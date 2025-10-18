<script>
    import { slide } from "svelte/transition"; // Svelte transition
  </script>
  
  <div in:slide={{ duration: 400 }}>
    <h1 class="text-4xl font-bold text-gray-500 text-center mb-[1vh] animate-bounce">
      Change It Up! 🎨
    </h1>
    <p class="text-xl text-gray-700 text-center">
      Make it your way!
    </p>
  </div>
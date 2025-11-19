<script>
  import { name, resetName } from "$lib/store/nameStore.js"; // Import resetName
  import { quiz1Taking, clearQuiz1State } from "$lib/store/quiz1_taking.js"; // Import clearQuiz1State
  import { studentData, resetStudentData } from "$lib/store/student_data.js"; // Import resetStudentData
  import { audioStore } from "$lib/store/audio_store.js"; // Import audio store
  import { fade, slide, scale } from "svelte/transition";
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';
  import Play from '../play/+page.svelte';
  import Stats from '../stats/+page.svelte';
  import Profile from '../profile/+page.svelte';
  import Settings from '../settings/+page.svelte';

  let activeTab = "play";
  let showLogoutModal = false;
  let hoveredTab = null;
  let audioInitialized = false;

  onMount(() => {
    // Start background music automatically when dashboard loads
    // (User has already interacted via name input, gender selection, welcome page)
    audioStore.playTrack('default');
    
    // Fallback: If autoplay is still blocked, enable on any interaction
    const enableAudio = () => {
      audioStore.playTrack('default');
    };

    document.addEventListener('click', enableAudio, { once: true });
    document.addEventListener('keydown', enableAudio, { once: true });
    document.addEventListener('touchstart', enableAudio, { once: true });

    return () => {
      document.removeEventListener('click', enableAudio);
      document.removeEventListener('keydown', enableAudio);
      document.removeEventListener('touchstart', enableAudio);
    };
  });

  function wiggleButton(tab) {
    activeTab = tab;
  }

  function setHoveredTab(tab) {
    hoveredTab = tab;
  }

  function clearHoveredTab() {
    hoveredTab = null;
  }

  function openLogoutModal() {
    showLogoutModal = true;
  }

  function confirmLogout() {
    // Completely destroy audio elements to prevent music from continuing
    audioStore.destroy();
    
    // Reset all stores and clear localStorage
    resetName();
    clearQuiz1State();
    resetStudentData();
    
    // Clear village progress tracking
    localStorage.removeItem('villageVisitedScenes');
    localStorage.removeItem('villageVisitedLevels');
    localStorage.removeItem('villageReturnScene');
    
    // Clear any pending or cached data
    localStorage.removeItem('pending_story');
    localStorage.removeItem('claimed_story1-1');
    localStorage.removeItem('claimed_story1-2');
    localStorage.removeItem('claimed_story1-3');
    localStorage.removeItem('claimed_story2-1');
    localStorage.removeItem('claimed_story2-2');
    localStorage.removeItem('claimed_story2-3');
    localStorage.removeItem('claimed_story3-1');
    localStorage.removeItem('claimed_story3-2');
    localStorage.removeItem('claimed_story3-3');

    showLogoutModal = false;
    goto('../'); // Redirect to parent directory
  }

  function cancelLogout() {
    showLogoutModal = false;
  }
</script>

  <div class="min-h-screen flex flex-col items-center justify-start overflow-hidden bg-gray-100 relative" style="background: url('/converted/assets/readville.webp') no-repeat center center/cover;">
  <!-- Logo in Top-Left Corner -->
  <img
    src="/converted/assets/shenievia.webp"
    alt="Shenievia Logo"
    class="absolute top-[0vh] left-[0vw] animate-rubberband-double-loop drop-shadow-[0_0_0.5vw_white] w-[200vw] max-w-[350px] h-auto"
  />

  <!-- Subtle Animated Background -->
  <div class="absolute inset-0 bg-[url('/converted/assets/kid-bg.webp')] bg-cover bg-center opacity-15 animate-pulse"></div>

  <!-- Floating Circle Buttons with Text and Underscore -->
  <div class="relative z-10 w-full p-[2vw] flex justify-center gap-[2vw] mt-[5vh]">
    <div class="flex flex-col items-center">
      <button
        class="w-[8vw] h-[8vw] max-w-[64px] max-h-[64px] rounded-full flex items-center justify-center text-[2.5vw] md:text-[1.5rem] transition-all duration-300 hover:bg-lime-500 border-[0.5vw] border-white shadow-[0_0.75vw_1.5vw_rgba(0,0,0,0.4),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)]"
        class:bg-lime-300={activeTab !== "play"}
        class:bg-lime-400={activeTab === "play"}
        class:scale-125={activeTab === "play"}
        class:animate-wiggle={activeTab === "play"}
        class:shadow-[0_1vw_2vw_rgba(0,0,0,0.5),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)]={activeTab === "play"}
        on:click={() => wiggleButton("play")}
        on:mouseenter={() => setHoveredTab("play")}
        on:mouseleave={clearHoveredTab}
      >
        🎮
      </button>
      <span
        class="mt-[0.5vh] text-[1.5vw] md:text-[0.875rem] font-bold text-white drop-shadow-[0_0.125vw_0.125vw_rgba(0,0,0,0.8)] transition-all duration-300"
        class:scale-150={activeTab === "play" || hoveredTab === "play"}
        class:animate-wiggle={activeTab === "play"}
      >
        Play
      </span>
      {#if activeTab === "play"}
        <div class="w-[4vw] h-[0.125vh] bg-lime-400 mt-[0.25vh] rounded transition-all duration-300" in:slide={{ duration: 200 }}></div>
      {/if}
    </div>
    <div class="flex flex-col items-center">
      <button
        class="w-[8vw] h-[8vw] max-w-[64px] max-h-[64px] rounded-full flex items-center justify-center text-[2.5vw] md:text-[1.5rem] transition-all duration-300 hover:bg-orange-500 border-[0.5vw] border-white shadow-[0_0.75vw_1.5vw_rgba(0,0,0,0.4),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)]"
        class:bg-orange-300={activeTab !== "dashboard"}
        class:bg-orange-400={activeTab === "dashboard"}
        class:scale-125={activeTab === "dashboard"}
        class:animate-wiggle={activeTab === "dashboard"}
        class:shadow-[0_1vw_2vw_rgba(0,0,0,0.5),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)]={activeTab === "dashboard"}
        on:click={() => wiggleButton("dashboard")}
        on:mouseenter={() => setHoveredTab("dashboard")}
        on:mouseleave={clearHoveredTab}
      >
        📊
      </button>
      <span
        class="mt-[0.5vh] text-[1.5vw] md:text-[0.875rem] font-bold text-white drop-shadow-[0_0.125vw_0.125vw_rgba(0,0,0,0.8)] transition-all duration-300"
        class:scale-150={activeTab === "dashboard" || hoveredTab === "dashboard"}
        class:animate-wiggle={activeTab === "dashboard"}
      >
        Stats
      </span>
      {#if activeTab === "dashboard"}
        <div class="w-[4vw] h-[0.125vh] bg-orange-400 mt-[0.25vh] rounded transition-all duration-300" in:slide={{ duration: 200 }}></div>
      {/if}
    </div>
    <div class="flex flex-col items-center">
      <button
        class="w-[8vw] h-[8vw] max-w-[64px] max-h-[64px] rounded-full flex items-center justify-center text-[2.5vw] md:text-[1.5rem] transition-all duration-300 hover:bg-blue-500 border-[0.5vw] border-white shadow-[0_0.75vw_1.5vw_rgba(0,0,0,0.4),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)]"
        class:bg-blue-300={activeTab !== "profile"}
        class:bg-blue-400={activeTab === "profile"}
        class:scale-125={activeTab === "profile"}
        class:animate-wiggle={activeTab === "profile"}
        class:shadow-[0_1vw_2vw_rgba(0,0,0,0.5),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)]={activeTab === "profile"}
        on:click={() => wiggleButton("profile")}
        on:mouseenter={() => setHoveredTab("profile")}
        on:mouseleave={clearHoveredTab}
      >
        🏠
      </button>
      <span
        class="mt-[0.5vh] text-[1.5vw] md:text-[0.875rem] font-bold text-white drop-shadow-[0_0.125vw_0.125vw_rgba(0,0,0,0.8)] transition-all duration-300"
        class:scale-150={activeTab === "profile" || hoveredTab === "profile"}
        class:animate-wiggle={activeTab === "profile"}
      >
        Home
      </span>
      {#if activeTab === "profile"}
        <div class="w-[4vw] h-[0.125vh] bg-red-400 mt-[0.25vh] rounded transition-all duration-300" in:slide={{ duration: 200 }}></div>
      {/if}
    </div>
    <div class="flex flex-col items-center">
      <button
        class="w-[8vw] h-[8vw] max-w-[64px] max-h-[64px] rounded-full flex items-center justify-center text-[2.5vw] md:text-[1.5rem] transition-all duration-300 hover:bg-gray-500 border-[0.5vw] border-white shadow-[0_0.75vw_1.5vw_rgba(0,0,0,0.4),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)]"
        class:bg-gray-300={activeTab !== "settings"}
        class:bg-gray-400={activeTab === "settings"}
        class:scale-125={activeTab === "settings"}
        class:animate-wiggle={activeTab === "settings"}
        class:shadow-[0_1vw_2vw_rgba(0,0,0,0.5),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)]={activeTab === "settings"}
        on:click={() => wiggleButton("settings")}
        on:mouseenter={() => setHoveredTab("settings")}
        on:mouseleave={clearHoveredTab}
      >
        🎖️
      </button>
      <span
        class="mt-[0.5vh] text-[1.5vw] md:text-[0.875rem] font-bold text-white drop-shadow-[0_0.125vw_0.125vw_rgba(0,0,0,0.8)] transition-all duration-300"
        class:scale-150={activeTab === "settings" || hoveredTab === "settings"}
        class:animate-wiggle={activeTab === "settings"}
      >
        Ribbons
      </span>
      {#if activeTab === "settings"}
        <div class="w-[4vw] h-[0.125vh] bg-gray-400 mt-[0.25vh] rounded transition-all duration-300" in:slide={{ duration: 200 }}></div>
      {/if}
    </div>
  </div>

  <!-- Main Content Area with Imported Components -->
  <div class="relative z-40 w-auto mt-[5vh] p-[2vw] bg-white rounded-[2vw] shadow-xl max-h-[70vh] overflow-hidden" in:fade={{ duration: 600 }}>
    {#if activeTab === "play"}
      <Play />
    {:else if activeTab === "dashboard"}
      <Stats />
    {:else if activeTab === "profile"}
      <Profile />
    {:else if activeTab === "settings"}
      <Settings />
    {/if}
  </div>

  <!-- Logout Button -->
  <button
    title="Logout"
    class="absolute top-[2vh] right-[2vw] w-[6vw] h-[6vw] max-w-[48px] max-h-[48px] bg-red-400 text-white text-[2vw] md:text-[1.25rem] font-bold rounded-full hover:bg-red-500 transition-all duration-300 hover:scale-110 active:scale-100 z-30 flex items-center justify-center border-[0.5vw] border-white shadow-[0_0.75vw_1.5vw_rgba(0,0,0,0.4),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)]"
    on:click={openLogoutModal}
  >
    ⏻
  </button>

  <!-- Logout Modal -->
  {#if showLogoutModal}
    <div
      class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-40"
      transition:fade={{ duration: 200 }}
    >
      <div
        class="bg-white p-[2vw] rounded-[2vw] shadow-xl w-[80vw] max-w-[320px]"
        transition:scale={{ duration: 300, start: 0.9 }}
      >
        <h2 class="text-[3vw] md:text-[1.5rem] font-bold text-purple-500 text-center mb-[1vh] animate-bounce">
          Bye Bye? 👋
        </h2>
        <p class="text-[2vw] md:text-[1rem] text-gray-700 text-center mb-[2vh]">
          Do you want to leave the fun?
        </p>
        <div class="flex justify-center gap-[2vw]">
          <button
            class="w-[15vw] h-[6vh] max-w-[80px] max-h-[48px] bg-lime-400 text-white font-bold rounded-full hover:bg-lime-500 transition-all duration-300 hover:scale-110 active:scale-95 border-[0.5vw] border-white shadow-[0_0.75vw_1.5vw_rgba(0,0,0,0.4),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)]"
            on:click={confirmLogout}
          >
            Yes
          </button>
          <button
            class="w-[15vw] h-[6vh] max-w-[80px] max-h-[48px] bg-red-400 text-white font-bold rounded-full hover:bg-red-500 transition-all duration-300 hover:scale-110 active:scale-95 border-[0.5vw] border-white shadow-[0_0.75vw_1.5vw_rgba(0,0,0,0.4),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)]"
            on:click={cancelLogout}
          >
            No
          </button>
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
  /* Custom Wiggle Animation for Buttons */
  @keyframes wiggle {
    0% { transform: rotate(0deg) scale(1.50); }
    25% { transform: rotate(5deg) scale(1.50); }
    50% { transform: rotate(-5deg) scale(1.50); }
    75% { transform: rotate(5deg) scale(1.50); }
    100% { transform: rotate(0deg) scale(1.50); }
  }
  .animate-wiggle {
    animation: wiggle 0.5s ease-in-out 1;
  }

  /* Slow Bounce Animation for Audio Prompt */
  @keyframes bounce-slow {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
  }
  .animate-bounce-slow {
    animation: bounce-slow 2s ease-in-out infinite;
  }

  /* Rubberband Animation for Logo */
  @keyframes rubberband {
    0% { transform: scale(1); }
    30% { transform: scaleX(1.25) scaleY(0.75); }
    40% { transform: scaleX(0.75) scaleY(1.25); }
    60% { transform: scaleX(1.15) scaleY(0.85); }
    100% { transform: scale(1); }
  }
  .animate-rubberband-double-loop {
    animation: rubberband-double-loop 7s infinite;
  }
  @keyframes rubberband-double-loop {
    0% { transform: scale(1); } /* Start steady */
    14.29% { transform: scaleX(1.25) scaleY(0.75); } /* 1s: First rubberband start */
    19.05% { transform: scaleX(0.75) scaleY(1.25); }
    28.57% { transform: scaleX(1.15) scaleY(0.85); }
    28.58% { transform: scale(1); } /* Second rubberband end */
    57.15% { transform: scale(1); } /* Second rubberband end */
    100% { transform: scale(1); } /* Steady for remaining 5s */
  }
</style>
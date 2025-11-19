<script>
  import { slide } from "svelte/transition";
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { studentData } from "$lib/store/student_data";

  let canEnterHome = false;

  onMount(() => {
    const student = $studentData;
    canEnterHome = student?.studentLevel >= 3;
  });

  function goToHome() {
    if (canEnterHome) {
      // Navigate to village and set a flag to go directly to home interior
      localStorage.setItem('goDirectlyToHome', 'true');
      goto('/student/village');
    }
  }
</script>

<div in:slide={{ duration: 400 }}>
  <h1 class="text-3xl font-bold text-blue-500 text-center mb-[0.5vh] animate-bounce">
    Welcome Home! ??
  </h1>
  
  {#if canEnterHome}
    <p class="text-lg text-gray-700 text-center mb-[1vh]">
      You've completed all your adventures in Readville Village! Visit your home anytime.
    </p>
    
    <div class="home-preview">
  <img src="/assets/Level_Walkthrough/places/home.webp" alt="Your Home" class="home-image" />
    </div>
    
    <div class="text-center mt-[1vh]">
      <button class="visit-home-btn" on:click={goToHome}>
        🏡 Visit Shenievia's Home
      </button>
    </div>
  {:else}
    <p class="text-lg text-gray-700 text-center mb-[1vh]">
      Complete all your adventures in Readville Village to unlock your home!
    </p>
    
    <div class="locked-home">
      <div class="lock-icon">??</div>
      <p class="lock-text">Complete Level 1, 2, and 3 to access your home</p>
    </div>
  {/if}
</div>

<style>
  .home-preview {
    max-width: 350px;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  }

  .home-image {
    width: 100%;
    height: auto;
    display: block;
  }

  .visit-home-btn {
    background: linear-gradient(135deg, #84cc16, #65a30d);
    color: white;
    padding: 10px 30px;
    border-radius: 12px;
    font-size: 1.2rem;
    font-weight: bold;
    border: 2px solid white;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    animation: pulse 2s ease-in-out infinite;
  }

  .visit-home-btn:hover {
    background: linear-gradient(135deg, #65a30d, #4d7c0f);
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
  }

  .visit-home-btn:active {
    transform: scale(0.95);
  }

  @keyframes pulse {
    0%, 100% {
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }
    50% {
      box-shadow: 0 6px 20px rgba(132, 204, 22, 0.6);
    }
  }

  .locked-home {
    text-align: center;
    padding: 1.5vh 30px;
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    border-radius: 12px;
    max-width: 350px;
    margin: 0 auto;
  }

  .lock-icon {
    font-size: 3rem;
    margin-bottom: 0.5vh;
  }

  .lock-text {
    font-size: 1rem;
    color: #6b7280;
    font-weight: 600;
  }

  @media (max-width: 768px) {
    .visit-home-btn {
      font-size: 1rem;
      padding: 8px 24px;
    }
  }
</style>

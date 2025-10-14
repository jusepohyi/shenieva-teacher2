<!-- src/routes/student/+page.svelte -->
<script lang="ts">
  import { name } from "$lib/store/nameStore.js"; // Import the global name store
  import { slide } from "svelte/transition"; // Svelte transition
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { goto, afterNavigate } from '$app/navigation';
  import { get } from 'svelte/store';
  import { studentData } from '$lib/store/student_data';
  import ChooseLevel from "../components/modals/choose_level.svelte"; // Import choose_level
  import Story1 from "../components/modals/level_1.svelte"; // Import story_1
  import Story2 from "../components/modals/level_2.svelte"; // Import story_2
  import Story3 from "../components/modals/level_3.svelte"; // Import story_3

  let isClicked = false; // Track if the Start button is clicked
  let isWiggling = true; // Track if the initial wiggle is active
  let showChooseStoryModal = false; // Control choose_story modal
  let showStory1Modal = false; // Control story_1 modal
  let showStory2Modal = false; // Control story_2 modal
  let showStory3Modal = false; // Control story_3 modal
  let story1Key = '';
  let story2Key = '';
  let story3Key = '';
  let lastRetakeToken: string | null = null;

  // Switch from wiggle to steady after the initial wiggle completes
  setTimeout(() => {
    isWiggling = false;
  }, 500); // Matches the 0.5s duration of the wiggle animation

  // Open choose_story modal when Start is clicked
  function handleStartClick() {
    // Allow opening the Choose Story modal; selection is validated inside the modal
    isClicked = true;
    showChooseStoryModal = true;
  }

  // Handle story selection from choose_story
  // Now interpreting chooser selection as Level selection (Level 1/2/3)
  function handleStorySelect(selection: string) {
    if (selection === "Level 1") {
      // Open Level 1 modal (which itself will show its story chooser)
      story1Key = '';
      showStory1Modal = true;
      showChooseStoryModal = false;
    } else if (selection === "Level 2") {
      story2Key = '';
      showStory2Modal = true;
      showChooseStoryModal = false;
    } else if (selection === "Level 3") {
      story3Key = '';
      showStory3Modal = true;
      showChooseStoryModal = false;
    }
  }
  
  // Close modals
  function handleCloseChooseStoryModal() {
    showChooseStoryModal = false;
    isClicked = false; // Reset button state
  }

  function handleCloseStory1Modal() {
    showStory1Modal = false;
    // Clear retake flags
    try {
      localStorage.removeItem('openStory1Modal');
      localStorage.removeItem('retakeLevel1');
    } catch {}
    
    // Check if we should return to village
    const returnScene = localStorage.getItem('villageReturnScene');
    const fromVillage = get(page).url.searchParams.has('level');
    
    console.log('handleCloseStory1Modal - returnScene:', returnScene, 'fromVillage:', fromVillage);
    
    if (returnScene !== null || fromVillage) {
      // Return to village - they will see the prompt again
      console.log('Navigating to village');
      goto('/student/village');
      return;
    }
    
    if (get(page).url.searchParams.has('retake') || get(page).url.searchParams.has('story')) {
      // Navigate back if coming from retake/story URL
      console.log('Navigating to dashboard');
      goto('/student/dashboard');
      return;
    }
    
    // Default: stay on current page or go to dashboard
    console.log('No navigation condition met');
  }
  function handleCloseStory2Modal() {
    showStory2Modal = false;
    // Clear retake flags
    try {
      localStorage.removeItem('openStory2Modal');
      localStorage.removeItem('retakeLevel2');
    } catch {}
    
    // Check if we should return to village
    const returnScene = localStorage.getItem('villageReturnScene');
    const fromVillage = get(page).url.searchParams.has('level');
    
    console.log('handleCloseStory2Modal - returnScene:', returnScene, 'fromVillage:', fromVillage);
    
    if (returnScene !== null || fromVillage) {
      // Return to village - they will see the prompt again
      console.log('Navigating to village');
      goto('/student/village');
      return;
    }
    
    if (get(page).url.searchParams.has('retake') || get(page).url.searchParams.has('story')) {
      // Navigate back if coming from retake/story URL
      console.log('Navigating to dashboard');
      goto('/student/dashboard');
      return;
    }
    
    // Default: stay on current page or go to dashboard
    console.log('No navigation condition met');
  }
  function handleCloseStory3Modal() {
    showStory3Modal = false;
    // Clear retake flags
    try {
      localStorage.removeItem('openStory3Modal');
      localStorage.removeItem('retakeLevel3');
    } catch {}
    
    // Check if we should return to village
    const returnScene = localStorage.getItem('villageReturnScene');
    const fromVillage = get(page).url.searchParams.has('level');
    
    console.log('handleCloseStory3Modal - returnScene:', returnScene, 'fromVillage:', fromVillage);
    
    if (returnScene !== null || fromVillage) {
      // Return to village - they will see the prompt again
      console.log('Navigating to village');
      goto('/student/village');
      return;
    }
    
    if (get(page).url.searchParams.has('retake') || get(page).url.searchParams.has('story')) {
      // Navigate back if coming from retake/story URL
      console.log('Navigating to dashboard');
      goto('/student/dashboard');
      return;
    }
    
    // Default: stay on current page or go to dashboard
    console.log('No navigation condition met');
  }

  // Trigger retake flow (open choose_story and then specific Level modal)
  function triggerRetakeFor(level: string) {
    // Ensure any existing story modal is closed so it remounts cleanly
    showStory1Modal = false;
    showStory2Modal = false;
    showStory3Modal = false;
    showChooseStoryModal = false;

    isClicked = true;
    showChooseStoryModal = true;
    setTimeout(() => {
      // Open the requested Level so its internal story chooser can handle the retake
      handleStorySelect(level);
      try {
        // clear matching retake localStorage keys if present
        if (level === 'Level 1') localStorage.removeItem('retakeLevel1');
        if (level === 'Level 2') localStorage.removeItem('retakeLevel2');
        if (level === 'Level 3') localStorage.removeItem('retakeLevel3');
      } catch {}
    }, 0);
  }

  // Auto retake flow: open choose_story then open Story 1 modal
  onMount(() => {
    try {
      // Check if coming from village with level parameter
      const levelParam = get(page).url.searchParams.get('level');
      if (levelParam) {
        if (levelParam === '1') {
          story1Key = '';
          showStory1Modal = true;
          return;
        } else if (levelParam === '2') {
          story2Key = '';
          showStory2Modal = true;
          return;
        } else if (levelParam === '3') {
          story3Key = '';
          showStory3Modal = true;
          return;
        }
      }

      // If the URL already includes a requested story (for example a direct link),
      // open the correct Level modal immediately and clear Level1 flags so it doesn't steal focus.
      // However, if the link includes a retake token, open the Level chooser (so user can re-choose the story)
      const requestedStory = get(page).url.searchParams.get('story') || '';
      const hasRetake = get(page).url.searchParams.has('retake');
      if (requestedStory.startsWith('story2-')) {
        try { localStorage.removeItem('openStory1Modal'); localStorage.removeItem('retakeLevel1'); } catch {}
        if (hasRetake) {
          // Open Level chooser so the student can re-choose
          story2Key = '';
          showStory2Modal = true;
        } else {
          // Open the requested story directly
          story2Key = requestedStory;
          showStory2Modal = true;
        }
        return;
      } else if (requestedStory.startsWith('story3-')) {
        try { localStorage.removeItem('openStory1Modal'); localStorage.removeItem('retakeLevel1'); } catch {}
        if (hasRetake) {
          story3Key = '';
          showStory3Modal = true;
        } else {
          story3Key = requestedStory;
          showStory3Modal = true;
        }
        return;
      }

      // If URL already contains retake param, defer to afterNavigate handler
      if (!get(page).url.searchParams.has('retake')) {
        const r1 = localStorage.getItem('retakeLevel1');
        const r2 = localStorage.getItem('retakeLevel2');
        const r3 = localStorage.getItem('retakeLevel3');
        if (r2 === 'true') {
          triggerRetakeFor('Level 2');
        } else if (r3 === 'true') {
          triggerRetakeFor('Level 3');
        } else if (r1 === 'true') {
          triggerRetakeFor('Level 1');
        }
      }
    } catch {}
  });

  // Trigger when navigating to this page with ?retake=...
  afterNavigate(() => {
    try {
      const token = get(page).url.searchParams.get('retake');
      if (token && token !== lastRetakeToken) {
        lastRetakeToken = token;
        // If the URL specifies a story, open the appropriate Level modal directly and pass the storyKey
        const requestedStory = get(page).url.searchParams.get('story') || '';
        if (requestedStory.startsWith('story2-')) {
          // open Level 2 modal; if this is a retake link, open the chooser instead of directly mounting the requested story
          showChooseStoryModal = false;
          showStory1Modal = false;
          showStory3Modal = false;
          const isRetakeUrl = get(page).url.searchParams.has('retake');
          if (isRetakeUrl) {
            story2Key = '';
          } else {
            story2Key = requestedStory;
          }
          showStory2Modal = true;
          try { localStorage.setItem('openStory2Modal', 'true'); localStorage.setItem('retakeLevel2', 'true'); } catch {}
          try { localStorage.removeItem('openStory1Modal'); localStorage.removeItem('retakeLevel1'); } catch {}
        } else if (requestedStory.startsWith('story3-')) {
          showChooseStoryModal = false;
          showStory1Modal = false;
          showStory2Modal = false;
          const isRetakeUrl = get(page).url.searchParams.has('retake');
          if (isRetakeUrl) {
            story3Key = '';
          } else {
            story3Key = requestedStory;
          }
          showStory3Modal = true;
          try { localStorage.setItem('openStory3Modal', 'true'); localStorage.setItem('retakeLevel3', 'true'); } catch {}
          try { localStorage.removeItem('openStory1Modal'); localStorage.removeItem('retakeLevel1'); } catch {}
        } else {
          // default to Level 1
          showChooseStoryModal = false;
          showStory2Modal = false;
          showStory3Modal = false;
          story1Key = requestedStory || '';
          showStory1Modal = true;
          try { localStorage.setItem('openStory1Modal', 'true'); localStorage.setItem('retakeLevel1', 'true'); } catch {}
        }
      }
    } catch {}
  });
</script>

<div in:slide={{ duration: 400 }} class="text-center">
  <h1 class="text-[6vw] md:text-4xl font-bold text-lime-500 mb-[2vh] animate-bounce">
    Hi, {$name}! 🌟
  </h1>
  <h2 class="text-[5vw] md:text-3xl font-bold text-lime-500 mb-[2vh]">
    Welcome to Readville Village! 📖✨
  </h2>
  <p class="text-[3vw] md:text-xl text-gray-700 mb-[1vh]">
    Join Shenievia Reads on an exciting journey home!<br> Explore fun stories, answer questions, and earn rewards along the way.
  </p>
  <p class="text-[3vw] md:text-xl text-gray-700 mb-[3vh]">
    Think, learn, and discover the hidden lessons in every tale.
  </p>
  <p class="text-[4vw] md:text-xl text-orange-500 mb-[5vh] font-bold">
    Your adventure starts now! 🚀📚
  </p>
  <button
    class="px-[3vw] py-[2vh] rounded-full text-[3.5vw] md:text-lg font-bold text-white bg-lime-400 border-[0.3vw] border-white shadow-[0_0.75vw_1.5vw_rgba(0,0,0,0.4),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)] transition-all duration-300 hover:bg-lime-500"
    class:animate-wiggle={isWiggling && !isClicked}
    class:animate-steady={!isWiggling && !isClicked}
    class:scale-125={isClicked}
    class:shadow-[0_1vw_2vw_rgba(0,0,0,0.5),inset_0_0.5vw_0.75vw_rgba(255,255,255,0.7),inset_0_-0.5vw_0.75vw_rgba(0,0,0,0.3)]={isClicked}
    on:click={() => goto('/student/village')}
  >
    🏘️ Enter Village
  </button>
</div>

<!-- Choose Level Modal -->
<ChooseLevel
  showModal={showChooseStoryModal}
  onSelectLevel={handleStorySelect}
  onSelectStory={handleStorySelect}
  onClose={handleCloseChooseStoryModal}
/> 

<!-- Story 1 Modal -->
<!-- Story 1 Modal -->
<Story1 storyKey={story1Key} showModal={showStory1Modal} onClose={handleCloseStory1Modal} />
<!-- Story 2 Modal -->
<Story2 storyKey={story2Key} showModal={showStory2Modal} onClose={handleCloseStory2Modal} />
<!-- Story 3 Modal -->
<Story3 storyKey={story3Key} showModal={showStory3Modal} onClose={handleCloseStory3Modal} />

<style>
  /* Initial Wiggle Animation */
  @keyframes wiggle {
    0% { transform: rotate(0deg); }
    25% { transform: rotate(5deg); }
    50% { transform: rotate(-5deg); }
    75% { transform: rotate(5deg); }
    100% { transform: rotate(0deg); }
  }
  .animate-wiggle {
    animation: wiggle 0.5s ease-in-out 1;
  }

  /* Steady Loop Animation */
  @keyframes steady {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
  }
  .animate-steady {
    animation: steady 1.5s ease-in-out infinite;
  }
</style>
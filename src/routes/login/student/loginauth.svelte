<!-- src/lib/loginauth.svelte -->
<script lang="ts">
  import { goto } from '$app/navigation';
  import { studentData } from '$lib/store/student_data'; // Import the store

  export let idNo: string;
  export let password: string;

  let modalState: 'idle' | 'loading' | 'success' | 'error' = 'idle';
  let modalMessage = '';

  async function authenticateStudent() {
    modalState = 'loading';
    modalMessage = 'Getting ready...';

    const credentials = {
      idNo,
      studentPass: password,
    };

    try {
      const response = await fetch('http://localhost/shenieva-teacher/src/lib/api/login_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(credentials),
      });

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        // Clear any old localStorage data before setting new student data
        localStorage.removeItem('studentData');
        localStorage.removeItem('villageVisitedLevels');
        localStorage.removeItem('villageVisitedScenes');
        localStorage.removeItem('villageReturnScene');
        
        // Save fresh student data from database to the store
        studentData.set(result.data);

        modalState = 'success';
        modalMessage = "Yay, you're in!";
        setTimeout(() => {
          goto('/student');
        }, 5000);
      } else {
        modalState = 'error';
        modalMessage = `Oops! ${result.message || 'Wrong ID or password!'}`;
        setTimeout(() => {
          modalState = 'idle';
        }, 5000);
      }
    } catch (error) {
      modalState = 'error';
      modalMessage = 'Oh no! Can’t connect right now!';
      console.error('Login error:', error);
      setTimeout(() => {
        modalState = 'idle';
      }, 5000);
    }
  }

  export { authenticateStudent as authenticate };
</script>

<!-- Modal (unchanged) -->
{#if modalState !== 'idle'}
  <div 
    class="fixed inset-0 bg-yellow-100/70 flex items-center justify-center z-50"
    role="dialog"
    aria-labelledby="modal-title"
    aria-modal="true"
  >
    <div class="bg-yellow-100 p-6 rounded-3xl shadow-xl w-96 text-center border-4 border-orange-400">
      <h3 
        id="modal-title" 
        class="text-2xl font-bold text-orange-600 mb-4"
        style="font-family: 'Comic Sans MS', 'Chalkboard', cursive;"
      >
        {modalMessage}
      </h3>
      {#if modalState === 'loading'}
        <div class="mt-4 flex justify-center">
          <span class="text-4xl animate-bounce">🌟</span>
        </div>
      {/if}
      {#if modalState === 'success'}
        <div class="mt-4">
          <span class="text-5xl animate-pulse">😊👍</span>
        </div>
      {/if}
      {#if modalState === 'error'}
        <div class="mt-4">
          <span class="text-5xl animate-wiggle">😢👎</span>
        </div>
      {/if}
    </div>
  </div>
{/if}

<style>
  @keyframes wiggle {
    0% { transform: rotate(0deg); }
    25% { transform: rotate(-10deg); }
    75% { transform: rotate(10deg); }
    100% { transform: rotate(0deg); }
  }

  .animate-wiggle {
    animation: wiggle 0.5s infinite;
  }
</style>
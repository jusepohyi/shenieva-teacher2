<!-- src/routes/register/+page.svelte -->
<script lang="ts">
    import { goto } from '$app/navigation';
  
    let teacherId = '';
    let teacherPassword = '';
    let teacherName = ''; // Added for registration
    let showTeacherPassword = false;
    let errorMessage = '';
    let successMessage = '';
  
    async function handleRegister(event: SubmitEvent) {
      event.preventDefault();
      
      const formData = new FormData();
      formData.append('userType', 'teacher');
      formData.append('idNo', teacherId);
      formData.append('password', teacherPassword);
      formData.append('name', teacherName);
  
      try {
        const response = await fetch('/register', {
          method: 'POST',
          body: formData
        });
  
        const result = await response.json();
  
        if (result.success) {
          successMessage = 'Registration successful! Redirecting to login...';
          errorMessage = '';
          setTimeout(() => goto('/login'), 2000);
        } else {
          errorMessage = result.error || 'Registration failed';
          successMessage = '';
        }
      } catch (err) {
        errorMessage = 'An error occurred during registration';
        successMessage = '';
        console.error('Registration error:', err);
      }
    }
  </script>
  
  <div class="flex items-center justify-center min-h-screen bg-gray-100"
  style="background: url('/assets/readville.jpg') no-repeat center center/cover;">
    <div class="w-full max-w-sm p-6 bg-white/50 rounded-lg shadow-md relative overflow-hidden backdrop-blur-lg">
      <!-- Teacher Registration Form -->
      <h2 class="text-2xl font-semibold text-center text-gray-700">Teacher Registration</h2>
      
      {#if errorMessage}
        <p class="mt-2 text-sm text-red-500">{errorMessage}</p>
      {/if}
      {#if successMessage}
        <p class="mt-2 text-sm text-green-500">{successMessage}</p>
      {/if}
  
      <form class="mt-4" on:submit={handleRegister}>
        <div>
          <label for="teacher-name" class="block text-sm font-medium text-gray-600">Full Name</label>
          <input 
            id="teacher-name"
            type="text" 
            bind:value={teacherName} 
            required 
            class="w-full mt-1 px-4 py-2 border rounded-md focus:ring focus:ring-lime-300" 
          />
        </div>
        <div class="mt-4">
          <label for="teacher-id" class="block text-sm font-medium text-gray-600">Teacher ID</label>
          <input 
            id="teacher-id"
            type="text" 
            bind:value={teacherId} 
            required 
            class="w-full mt-1 px-4 py-2 border rounded-md focus:ring focus:ring-lime-300" 
          />
        </div>
        <div class="mt-4 relative">
          <label for="teacher-password" class="block text-sm font-medium text-gray-600">Password</label>
          <input 
            id="teacher-password"
            type={showTeacherPassword ? "text" : "password"} 
            bind:value={teacherPassword} 
            required 
            class="w-full mt-1 px-4 py-2 border rounded-md focus:ring focus:ring-lime-300 pr-10" 
          />
          <button 
            type="button" 
            class="absolute top-9 right-3 text-gray-600"
            on:click={() => showTeacherPassword = !showTeacherPassword}
          >
            {showTeacherPassword ? '👁️' : '👁️‍🗨️'}
          </button>
        </div>
        <button 
          type="submit" 
          class="w-full px-4 py-2 mt-6 text-white bg-lime-600 rounded-md hover:bg-lime-800"
        >
          Register
        </button>
      </form>
      
      <p class="mt-4 text-sm text-center text-gray-600">
        Already have an account? 
        <a href="/login" class="text-lime-600 hover:underline">Login here</a>
      </p>
    </div>
  </div>
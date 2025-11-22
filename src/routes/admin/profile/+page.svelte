<!-- src/routes/admin/profile/+page.svelte -->
<script>
    import { Modal, Button, Toggle } from 'flowbite-svelte';
    import { Pencil, Mail, UserCircle, Camera, Clock, BarChart, Volume2, Moon, BarChart3, RefreshCcw, Lock, LogOut, Hash } from 'lucide-svelte';
    import { goto } from '$app/navigation';
    import SettingsModal from "../modals/settings.svelte";
    import { onMount } from 'svelte';
    
    let editModal = false;
    let showMenu = false;
    let popupModal = false;
    let settingsModal = false;
    
    // Initialize with loading state
    let name = 'Loading...';
    let email = 'Loading...';
    let role = 'Teacher';
    let userId = '';
  
    // Settings variables (kept for compatibility, not used in SettingsModal)
    let darkMode = false;
    let soundEnabled = true;
    let animationsEnabled = true;
    let difficulty = "Normal";
    let fontSize = "Medium";

    // Fetch teacher data client-side
    async function fetchTeacherInfo(){
      try{
        const { apiUrl } = await import('$lib/api_base');
        const res = await fetch(apiUrl('fetch_teacher.php'), { credentials: 'include' });
        const j = await res.json();
        if (j && j.success && j.data){
          name = j.data.name || '';
          email = j.data.email || '';
          userId = j.data.idNo || '';
        } else {
          // If no teacher session, redirect to login
          goto('/admin');
        }
      }catch(e){
        console.warn('Failed to fetch teacher info', e);
        // Redirect to login on error
        goto('/admin');
      }
    }

    onMount(() => {
      fetchTeacherInfo();
      fetchProfileCounts();
    });
  
    function toggleMenu() {
      showMenu = !showMenu;
    }
  
    function navigateToProfile() {
      goto('/admin/profile');
      showMenu = false; 
    }
  
    function logout() {
      console.log("User logged out");
      goto('../');
    }
    
    function saveChanges() {
      editModal = false;
    }
  
    function resetProgress() {
      if (confirm("Are you sure you want to reset all progress? This action cannot be undone.")) {
        alert("Game progress reset!");
      }
    }

  // Live total students (fetched from server)
  let totalStudents = 0;

  async function fetchProfileCounts(){
    try{
      const { apiUrl } = await import('$lib/api_base');
      const res = await fetch(apiUrl('fetch_students.php'));
      const students = await res.json();
      totalStudents = Array.isArray(students) ? students.length : 0;
    }catch(e){
      console.error('Failed to fetch student count', e);
      totalStudents = 0;
    }
  }
  </script>
  
  <div class="flex h-screen bg-gray-100">
    <div class="flex-1 flex flex-col">
      <div class="fixed top-0 left-64 w-[calc(100%-16rem)] bg-orange-500 text-white px-6 flex justify-between items-center shadow-md z-50 h-16">
        <h1 class="text-xl font-semibold">Profile</h1>
        <div class="relative p-2">
          <button class="w-10 h-10 rounded-full overflow-hidden border-2 border-white" on:click={toggleMenu}>
            <img src="/avatar.jpg" alt="User Avatar" class="w-full h-full rounded-full border-4 border-gray-300 object-cover">
          </button>
          {#if showMenu}
          <div class="absolute right-0 mt-2 w-48 bg-white text-black shadow-lg rounded-md py-2">
            <button on:click={navigateToProfile} class="block w-full text-left px-4 py-2 hover:bg-gray-200">Profile</button>
            <button on:click={() => (settingsModal = true)} class="block w-full text-left px-4 py-2 hover:bg-gray-200">Settings</button>
            <button on:click={() => (popupModal = true)} class="block w-full text-left px-4 py-2 hover:bg-gray-200">Logout</button>
          </div>
          {/if}
        </div>
      </div>
  
      <!-- Profile Section -->
      <div class="flex flex-col md:flex-row mt-20 px-6 space-x-6">
        <div class="w-full md:w-1/3 flex flex-col space-y-6">
          <div class="bg-white shadow-lg rounded-lg p-6">
            <div class="relative w-36 h-36 mx-auto mb-4">
              <img src="/avatar.jpg" alt="User Avatar" class="w-full h-full rounded-full border-4 border-gray-300 shadow-lg object-cover">
              <button class="absolute bottom-2 right-2 bg-gray-800 text-white p-2 rounded-full shadow-md hover:bg-gray-700 transition">
                <Camera class="w-5 h-5" />
              </button>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 text-center">{name}</h2>
            <p class="flex items-center justify-center text-gray-600 mt-2">
              <Mail class="w-5 h-5 text-gray-500 mr-2" /> {email}
            </p>
            <p class="flex items-center justify-center text-gray-600">
              <UserCircle class="w-5 h-5 text-gray-500 mr-2" /> {role}
            </p>
            <p class="flex items-center justify-center text-gray-600 mt-2">
              <Hash class="w-5 h-5 text-gray-500 mr-2" /> User ID: {userId}
            </p>
            <!-- <button on:click={() => (editModal = true)} class="mt-4 w-full px-6 py-2 bg-lime-500 text-white rounded-full flex items-center justify-center gap-2 shadow-md hover:bg-blue-600 transition">
              <Pencil class="w-5 h-5" /> Edit Profile
            </button> -->
          </div>
        </div>
        <div class="w-full md:w-2/3 bg-white shadow-lg rounded-lg p-6">
          <h3 class="text-xl font-semibold mb-4">Overview</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-gray-50 shadow rounded-lg flex items-center space-x-4">
              <BarChart class="w-10 h-10 text-blue-500" />
              <div>
                <p class="text-gray-600">Total System Level</p>
                <p class="text-2xl font-bold">3</p>
              </div>
            </div>
            <div class="p-4 bg-gray-50 shadow rounded-lg flex items-center space-x-4">
              <BarChart class="w-10 h-10 text-green-500" />
              <div>
                <p class="text-gray-600">Total Stories</p>
                <p class="text-2xl font-bold">9</p>
              </div>
            </div>
            <div class="p-4 bg-gray-50 shadow rounded-lg flex items-center space-x-4">
              <BarChart class="w-10 h-10 text-yellow-500" />
              <div>
                <p class="text-gray-600">Total Stories each Level</p>
                <p class="text-2xl font-bold">3</p>
              </div>
            </div>
            <div class="p-4 bg-gray-50 shadow rounded-lg flex items-center space-x-4">
              <BarChart class="w-10 h-10 text-red-500" />
              <div>
                <p class="text-gray-600">Total Students</p>
                <p class="text-2xl font-bold">{totalStudents}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Edit Profile Modal -->
  <Modal bind:open={editModal} size="sm">
    <h3 class="text-xl font-semibold mb-4">Edit Profile</h3>
    <!-- Add your edit form here -->
    <Button on:click={saveChanges}>Save</Button>
    <Button on:click={() => (editModal = false)} color="alternative">Cancel</Button>
  </Modal>
  
  <!-- Logout Confirmation Modal -->
  <Modal bind:open={popupModal} size="xs" autoclose>
    <div class="text-center">
      <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
        Are you sure you want to logout?
      </h3>
      <Button on:click={logout} color="red" class="me-2">Yes, I'm sure</Button>
      <Button on:click={() => (popupModal = false)} color="alternative">No, cancel</Button>
    </div>
  </Modal>
  
  <!-- Settings Modal -->
  <SettingsModal bind:open={settingsModal} bind:name bind:email />
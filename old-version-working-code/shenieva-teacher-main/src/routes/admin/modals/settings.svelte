<!-- src/modals/settings.svelte -->
<script lang="ts">
  import { Modal, Button, Input, Label } from 'flowbite-svelte';
  import { Pencil, Mail, Lock, LogOut, CheckCircle, XCircle } from 'lucide-svelte';
  import { goto } from '$app/navigation';

  export let open = false;
  export let name = '';
  export let email = '';

  let newName = name;
  let newEmail = email;
  let newPassword = '';
  let confirmPassword = '';
  let isSubmitting = false;
  let message = '';
  let messageType: 'success' | 'error' | '' = '';
  let confirmModal = false;
  let confirmCurrentPassword = '';

  function validateInputs() {
    if (!newName || newName.length < 2) {
      message = 'Name must be at least 2 characters.';
      messageType = 'error';
      return false;
    }
    if (!newEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newEmail)) {
      message = 'Please enter a valid email.';
      messageType = 'error';
      return false;
    }
    if (newPassword) {
      if (newPassword.length < 8) {
        message = 'New password must be at least 8 characters.';
        messageType = 'error';
        return false;
      }
      if (newPassword !== confirmPassword) {
        message = 'Passwords do not match.';
        messageType = 'error';
        return false;
      }
    }
    return true;
  }

  function initiateSave() {
    message = '';
    messageType = '';
    if (!validateInputs()) return;

    const nameChanged = newName.trim() !== name.trim();
    const emailChanged = newEmail.trim() !== email.trim();
    const passwordChanged = !!newPassword;

    if (nameChanged || emailChanged || passwordChanged) confirmModal = true;
    else {
      message = 'No changes to save.';
      messageType = 'error';
    }
  }

  async function confirmSave() {
    if (!confirmCurrentPassword) {
      message = 'Please enter your current password to confirm.';
      messageType = 'error';
      return;
    }

    isSubmitting = true;
    confirmModal = false;

    const payload = {
      name: newName.trim(),
      email: newEmail.trim(),
      currentPassword: confirmCurrentPassword,
      ...(newPassword ? { newPassword } : {})
    };

  // try SvelteKit route, fall back to PHP endpoint served by Apache (use apiUrl helper)
  const { apiUrl } = await import('$lib/api_base');
  const phpUrl = apiUrl('update_teacher.php');
  const endpoints = ['/api/update-profile', phpUrl];
    let lastError: unknown = null;

    try {
      for (const url of endpoints) {
        try {
          const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'include'
          });

          const ct = res.headers.get('content-type') || '';
          if (!ct.includes('application/json')) {
            const text = await res.text();
            console.error('Non-JSON response from', res.url, res.status, text);
            lastError = new Error('Non-JSON response from server');
            if (url === phpUrl) break; // no more fallbacks
            continue; // try next endpoint
          }

          const data = await res.json();
          if (!res.ok) throw new Error(data?.message || 'Update failed');

          // success
          name = newName;
          email = newEmail;
          message = newPassword ? 'Profile and password updated successfully!' : 'Profile updated successfully!';
          messageType = 'success';
          newPassword = '';
          confirmPassword = '';
          confirmCurrentPassword = '';
          return;
        } catch (err) {
          console.warn('Error calling endpoint:', url, err);
          lastError = err;
        }
      }

      throw lastError ?? new Error('Failed to update profile');
    } catch (err) {
      message = err instanceof Error ? err.message : 'Failed to save changes. Please try again.';
      messageType = 'error';
    } finally {
      isSubmitting = false;
    }
  }

  async function logout() {
    await fetch('/logout', { method: 'POST' });
    message = 'Logging out...';
    messageType = 'success';
    setTimeout(() => {
      open = false;
      goto('/login');
    }, 500);
  }

  async function fetchTeacherInfo(){
    try{
  const { apiUrl } = await import('$lib/api_base');
  const res = await fetch(apiUrl('fetch_teacher.php'), { credentials: 'include' });
      const j = await res.json();
      if (j && j.success && j.data){
        // update both exported props and local inputs
        name = j.data.name || '';
        email = j.data.email || '';
        newName = name;
        newEmail = email;
      }
    }catch(e){
      console.warn('Failed to fetch teacher info', e);
    }
  }

  $: if (open) {
    // reset transient fields
    newPassword = '';
    confirmPassword = '';
    confirmCurrentPassword = '';
    message = '';
    messageType = '';
    confirmModal = false;

    // if parent hasn't provided name/email, fetch from server so modal shows correct values
    if (name && email) {
      newName = name;
      newEmail = email;
    } else {
      // fetch teacher info client-side (non-blocking)
      fetchTeacherInfo();
    }
  }
</script>

  <Modal bind:open size="md" class="backdrop-blur-lg bg-white/90 rounded-2xl shadow-lg">
    <div class="p-8">
      <h3 class="text-2xl font-semibold text-gray-900 mb-2">Settings</h3>
      <p class="text-gray-500 mb-6">Update your profile details below.</p>
  
      {#if message && !confirmModal}
        <div class="mb-6 p-4 rounded-lg flex items-center space-x-3 {messageType === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}">
          {#if messageType === 'success'}
            <CheckCircle class="w-5 h-5" />
          {:else}
            <XCircle class="w-5 h-5" />
          {/if}
          <span class="text-sm font-medium">{message}</span>
        </div>
      {/if}
  
      <div class="space-y-6">
        <div>
          <Label for="name" class="flex items-center space-x-2 mb-2 text-gray-700 font-medium">
            <Pencil class="w-4 h-4 text-gray-500" />
            <span>Name</span>
          </Label>
          <Input
            id="name"
            bind:value={newName}
            placeholder="Enter your name"
            class="w-full border-gray-200 rounded-lg py-3 px-4 text-gray-800 bg-gray-50 focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition"
            disabled={isSubmitting}
          />
        </div>
  
        <div>
          <Label for="email" class="flex items-center space-x-2 mb-2 text-gray-700 font-medium">
            <Mail class="w-4 h-4 text-gray-500" />
            <span>Email</span>
          </Label>
          <Input
            id="email"
            bind:value={newEmail}
            placeholder="Enter your email"
            type="email"
            class="w-full border-gray-200 rounded-lg py-3 px-4 text-gray-800 bg-gray-50 focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition"
            disabled={isSubmitting}
          />
        </div>
  
        <div>
          <Label class="flex items-center space-x-2 mb-2 text-gray-700 font-medium">
            <Lock class="w-4 h-4 text-gray-500" />
            <span>Change Password (Optional)</span>
          </Label>
          <div class="space-y-4">
            <Input
              bind:value={newPassword}
              placeholder="New Password"
              type="password"
              class="w-full border-gray-200 rounded-lg py-3 px-4 text-gray-800 bg-gray-50 focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition"
              disabled={isSubmitting}
            />
            <Input
              bind:value={confirmPassword}
              placeholder="Confirm New Password"
              type="password"
              class="w-full border-gray-200 rounded-lg py-3 px-4 text-gray-800 bg-gray-50 focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition"
              disabled={isSubmitting}
            />
          </div>
        </div>
      </div>
  
      <div class="flex justify-between items-center mt-8">
        <button
          on:click={logout}
          class="flex items-center space-x-2 text-red-600 hover:text-red-800 transition font-medium disabled:opacity-50"
          disabled={isSubmitting}
        >
          <LogOut class="w-4 h-4" />
          <span>Logout</span>
        </button>
        <div class="space-x-3">
          <Button
            on:click={initiateSave}
            color="primary"
            disabled={isSubmitting}
            class="flex items-center space-x-2 px-5 py-2.5 rounded-lg bg-orange-600 hover:bg-orange-700 shadow-md transition"
          >
            {#if isSubmitting && !confirmModal}
              <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h8a8 8 0 01-16 0z"></path>
              </svg>
            {/if}
            <span>Save Changes</span>
          </Button>
          <Button
            on:click={() => (open = false)}
            color="alternative"
            disabled={isSubmitting}
            class="px-5 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 shadow-md transition"
          >
            Cancel
          </Button>
        </div>
      </div>
    </div>
  </Modal>
  
  <Modal bind:open={confirmModal} size="xs" class="backdrop-blur-lg bg-white/90 rounded-xl shadow-lg" autoclose={false}>
    <div class="p-6 text-center">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirm Changes</h3>
      <p class="text-gray-600 mb-6">Please enter your current password to confirm your updates.</p>
  
      <Input
        bind:value={confirmCurrentPassword}
        placeholder="Current Password"
        type="password"
        class="w-full border-gray-200 rounded-lg py-3 px-4 text-gray-800 bg-gray-50 focus:ring-2 focus:ring-orange-400 focus:border-orange-400 transition mb-6"
        disabled={isSubmitting}
      />
  
      <div class="flex justify-center space-x-3">
        <Button
          on:click={confirmSave}
          color="primary"
          disabled={isSubmitting}
          class="flex items-center space-x-2 px-5 py-2.5 rounded-lg bg-orange-600 hover:bg-orange-700 shadow-md transition"
        >
          {#if isSubmitting}
            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h8a8 8 0 01-16 0z"></path>
            </svg>
          {/if}
          <span>Confirm</span>
        </Button>
        <Button
          on:click={() => (confirmModal = false)}
          color="alternative"
          disabled={isSubmitting}
          class="px-5 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 shadow-md transition"
        >
          Cancel
        </Button>
      </div>
    </div>
  </Modal>
  
  <style>
    .animate-spin {
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
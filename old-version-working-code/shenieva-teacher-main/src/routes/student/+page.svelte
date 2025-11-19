<!-- src/routes/student/+page.svelte -->
<script lang="ts">
    import NameInput from './components/NameInput.svelte';
    import GenderSelect from './components/GenderSelect.svelte';
    import WelcomeMessage from './components/WelcomeMessage.svelte';
    import Dashboard from './dashboard/+page.svelte';
  
    let step: 'name' | 'gender' | 'done' = 'name';
    let name = '';
    let gender: 'boy' | 'girl' | null = null;
  
    function handleNameSubmit(submittedName: string) {
      name = submittedName;
      step = 'gender';
    }
  
    function handleGenderSelect(selectedGender: 'boy' | 'girl') {
      gender = selectedGender;
      step = 'done';
    }
  </script>
  
  <div class="flex items-center justify-center min-h-screen bg-gray-100"
  style="background: url('/assets/school-bg.gif') no-repeat center center/cover;">
    {#if step === 'name'}
      <NameInput on:submit={(e) => handleNameSubmit(e.detail)} />
    {:else if step === 'gender'}
      <GenderSelect on:select={(e) => handleGenderSelect(e.detail)} />
    {:else if step === 'done'}
      <WelcomeMessage {name} {gender} />
      {:else if step === 'done'}
      <Dashboard/>
    {/if}
  </div>
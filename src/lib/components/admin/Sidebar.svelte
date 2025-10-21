<script>
  import { Sidebar, SidebarGroup, SidebarItem, SidebarWrapper, SidebarDropdownItem, SidebarDropdownWrapper } from 'flowbite-svelte';
  import { ChartPieSolid, UsersSolid, FileCopyAltSolid, ClipboardCheckSolid, ArrowRightToBracketOutline } from 'flowbite-svelte-icons';
  import { Button, Modal } from 'flowbite-svelte';
  import { ExclamationCircleOutline } from 'flowbite-svelte-icons';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { derived } from 'svelte/store';


  let popupModal = false;

  function logout() {
    console.log("User logged out");
    goto('../');
  }

  // Reactive current path
  const currentPath = derived(page, $page => $page.url.pathname);

  function isActive(path) {
  return $currentPath === path || ($currentPath === '/admin' && path === '/admin/dashboard')
    ? 'bg-gray-600 text-white hover:bg-gray-700'
    : 'hover:bg-lime-500 hover:text-white';
}

  function isDropdownActive(paths) {
    return paths.includes($currentPath)
      ? 'bg-gray-400 text-white hover:bg-gray-400' // Active dropdown
      : 'hover:bg-lime-500 hover:text-white';
  }
</script>

<Sidebar>
  <SidebarWrapper class="h-screen bg-lime-100 flex flex-col rounded-none">
    <SidebarGroup>
      <h1 class="text-xl font-bold text-orange-500 p-5">SHENIEVIA READS</h1>
    </SidebarGroup>

    <div class="flex-1 overflow-y-auto">
      <SidebarGroup border>
        <SidebarItem label="Dashboard" class={isActive('/admin/dashboard')} href="/admin/dashboard">
          <svelte:fragment slot="icon">
            <ChartPieSolid class="w-6 h-6" />
          </svelte:fragment>
        </SidebarItem>

        <SidebarItem label="Students" class={isActive('/admin/students')} href="/admin/students">
          <svelte:fragment slot="icon">
            <UsersSolid class="w-6 h-6" />
          </svelte:fragment>
        </SidebarItem>

        <SidebarDropdownWrapper label="Quizzes" class={isDropdownActive(['/admin/quizzes/story1', '/admin/quizzes/story2', '/admin/quizzes/story3'])}>
          <svelte:fragment slot="icon">
            <FileCopyAltSolid class="w-6 h-6" />
          </svelte:fragment>
          <SidebarDropdownItem label="Level 1" class={isActive('/admin/quizzes/story1')} href="/admin/quizzes/story1" />
          <SidebarDropdownItem label="Level 2" class={isActive('/admin/quizzes/story2')} href="/admin/quizzes/story2" />
          <SidebarDropdownItem label="Level 3" class={isActive('/admin/quizzes/story3')} href="/admin/quizzes/story3" />
        </SidebarDropdownWrapper>

        <SidebarItem label="Attendance" class={isActive('/admin/record')} href="/admin/record">
          <svelte:fragment slot="icon">
            <ClipboardCheckSolid class="w-6 h-6" />
          </svelte:fragment>
        </SidebarItem>
      </SidebarGroup>
    </div>

    <SidebarGroup border class="border-t-gray-500">
      <SidebarItem label="Log Out" class="hover:bg-red-500 hover:text-white" on:click={() => (popupModal = true)}>
        <svelte:fragment slot="icon">
          <ArrowRightToBracketOutline class="w-6 h-6 dark:text-gray-400 group-hover:text-white dark:group-hover:text-white" />
        </svelte:fragment>
      </SidebarItem>
      <br><br><br>
    </SidebarGroup>
  </SidebarWrapper>
</Sidebar>

<Modal bind:open={popupModal} size="xs" autoclose>
  <div class="text-center">
    <ExclamationCircleOutline class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200" />
    <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
      Are you sure you want to logout?
    </h3>
    <Button on:click={logout} color="red" class="me-2">Yes, I'm sure</Button>
    <Button on:click={() => (popupModal = false)} color="alternative">No, cancel</Button>
  </div>
</Modal>

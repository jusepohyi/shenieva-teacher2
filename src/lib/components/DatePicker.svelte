<script>
  import { createEventDispatcher, onMount } from 'svelte';
  export let value = ''; // ISO yyyy-mm-dd
  export let placeholder = 'MM/DD/YYYY';
  const dispatch = createEventDispatcher();

  let open = false;
  let viewDate = value ? new Date(value) : new Date();
  /** @type {HTMLElement|null} */
  let inputEl = null;
  /** @type {HTMLElement|null} */
  let popupEl = null;
  let popupStyle = '';

  function isoFromParts(y, m, d) {
    return `${y}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
  }

  function selectDay(d) {
    const iso = isoFromParts(viewDate.getFullYear(), viewDate.getMonth()+1, d);
    value = iso;
    dispatch('change', { value: iso });
    open = false;
  }

  function clearIfDoubleClick(e) {
    if (e.detail === 2) {
      value = '';
      dispatch('change', { value: '' });
    }
  }

  function prevMonth() {
    viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1);
  }

  function nextMonth() {
    viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1);
  }

  function isoToLabel(iso) {
    if (!iso) return '';
    const m = iso.match(/(\d{4})-(\d{2})-(\d{2})/);
    if (!m) return '';
    return `${m[2]}/${m[3]}/${m[1]}`;
  }

  function daysInMonth(y, m) { return new Date(y, m, 0).getDate(); }

  $: year = viewDate.getFullYear();
  $: month = viewDate.getMonth();
  $: firstWeekday = new Date(year, month, 1).getDay();
  $: days = daysInMonth(year, month+1);

  // close on outside click
  let root;
  onMount(() => {
    const onDoc = (e) => { if (root && !root.contains(e.target)) open = false; };
    document.addEventListener('click', onDoc);
    return () => document.removeEventListener('click', onDoc);
  });

  function openPopup() {
    open = true;
    // wait one tick using setTimeout to let DOM update
    setTimeout(() => {
      try {
        // compute position relative to viewport to avoid clipping by parent overflow
        if (!inputEl || !popupEl) return;
        // @ts-ignore DOM rect
        const r = inputEl.getBoundingClientRect();
        const popupH = popupEl.offsetHeight || 220;
        const spaceBelow = window.innerHeight - r.bottom;
        let top = (spaceBelow >= popupH) ? (r.bottom + 6) : (r.top - popupH - 6);
        if (top < 6) top = 6;
        let left = r.left;
        // ensure popup doesn't overflow horizontally
        const popupW = Math.max(popupEl.offsetWidth || 240, r.width);
        if (left + popupW > window.innerWidth - 6) left = window.innerWidth - popupW - 6;
        if (left < 6) left = 6;
        popupStyle = `position:fixed; top:${top}px; left:${left}px; min-width:${popupW}px; z-index:9999;`;
      } catch (e) {
        // ignore measurement errors
      }
    }, 0);
  }
</script>

<div class="relative inline-block" bind:this={root}>
  <div class="flex items-center">
    <input bind:this={inputEl} readonly class="p-2 border rounded-md text-sm cursor-pointer" type="text" placeholder={placeholder} value={isoToLabel(value)} on:click={() => openPopup()} />
  </div>

  {#if open}
  <div bind:this={popupEl} role="dialog" tabindex="-1" style={popupStyle} class="bg-white border rounded shadow p-3" on:click|stopPropagation>
      <div class="flex items-center justify-between mb-2">
        <button class="px-2" on:click={prevMonth}>&lt;</button>
        <div class="font-semibold">{viewDate.toLocaleString(undefined, { month: 'long' })} {year}</div>
        <button class="px-2" on:click={nextMonth}>&gt;</button>
      </div>
      <div class="grid grid-cols-7 gap-1 text-center text-xs">
        <div class="font-medium">Su</div><div class="font-medium">Mo</div><div class="font-medium">Tu</div><div class="font-medium">We</div><div class="font-medium">Th</div><div class="font-medium">Fr</div><div class="font-medium">Sa</div>
        {#each Array(firstWeekday) as _}
          <div></div>
        {/each}
        {#each Array(days) as _, i}
          {@const day = i+1}
          {@const iso = isoFromParts(year, month+1, day)}
          {@const isToday = (new Date()).toISOString().slice(0,10) === iso}
          {@const isSelected = value === iso}
          <div>
            <button type="button" class="w-8 h-8 rounded hover:bg-gray-200 flex items-center justify-center {isToday ? 'border border-lime-400' : ''} {isSelected ? 'bg-lime-500 text-white' : ''}" on:click={() => selectDay(day)}>{day}</button>
          </div>
        {/each}
      </div>
    </div>
  {/if}
</div>

<style>
  /* small component-local styles kept minimal */
</style>

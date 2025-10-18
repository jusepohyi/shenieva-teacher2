# Scene Background Music Persistence Fix 🎵

## Problem
When students retake a quiz from the village scene, the background music (village theme) stops playing after clicking "Try Again" because `location.replace()` causes a full page reload.

## Root Cause
1. Student is in **village** with "village" music playing 🏘️
2. Opens story modal → completes quiz (still on village page)
3. Clicks "Try Again" → `location.replace('/student/play?level=X')`
4. Page reloads to `/student/play` (different page, music state lost) ❌
5. Music doesn't resume because audio state was not preserved

## Solution: Audio State Persistence

Added two new methods to the audio store to save and restore the current track across page reloads using `sessionStorage`:

### 1. Save Current Track Before Navigation
```javascript
// In audio_store.js
saveCurrentTrack() {
    update(state => {
        if (state.currentTrack && typeof sessionStorage !== 'undefined') {
            sessionStorage.setItem('audioTrack', state.currentTrack);
            sessionStorage.setItem('audioMuted', String(state.isMuted));
            console.log(`💾 Saved audio state: ${state.currentTrack}, muted: ${state.isMuted}`);
        }
        return state;
    });
}
```

### 2. Restore Track After Page Reload
```javascript
// In audio_store.js
restoreSavedTrack() {
    if (typeof sessionStorage === 'undefined') return;
    
    const savedTrack = sessionStorage.getItem('audioTrack');
    const savedMuted = sessionStorage.getItem('audioMuted') === 'true';
    
    if (savedTrack) {
        console.log(`🔄 Restoring audio state: ${savedTrack}, muted: ${savedMuted}`);
        
        update(state => ({
            ...state,
            isMuted: savedMuted
        }));
        
        if (!savedMuted && (savedTrack === 'default' || savedTrack === 'village')) {
            this.playTrack(savedTrack);
        }
        
        // Clear after restoring (one-time use)
        sessionStorage.removeItem('audioTrack');
        sessionStorage.removeItem('audioMuted');
    }
}
```

## Files Modified

### 1. Audio Store: `src/lib/store/audio_store.js`
- Added `saveCurrentTrack()` method
- Added `restoreSavedTrack()` method
- Uses `sessionStorage` for persistence (survives page reload but not tab close)

### 2. Level 1: `src/routes/student/Levels/Level1/slide_last.svelte`
- Added import: `import { audioStore } from '$lib/store/audio_store';`
- Added `audioStore.saveCurrentTrack()` before both retake navigations (2 locations)

### 3. Level 2: `src/routes/student/Levels/Level2/slide_last.svelte`
- Added import: `import { audioStore } from '$lib/store/audio_store';`
- Added `audioStore.saveCurrentTrack()` before both retake navigations (2 locations)

### 4. Level 3: `src/routes/student/Levels/Level3/slide_last.svelte`
- Added import: `import { audioStore } from '$lib/store/audio_store';`
- Added `audioStore.saveCurrentTrack()` before both retake navigations (2 locations)

### 5. Play Page: `src/routes/student/play/+page.svelte`
- Added import: `import { audioStore } from '$lib/store/audio_store';`
- Added `onMount(() => { audioStore.restoreSavedTrack(); })`

## How It Works

### Flow: Retake from Village

**Before Fix:**
```
1. Village page → 'village' music playing 🎵
2. Complete quiz → Click "Try Again"
3. location.replace('/student/play?level=2')
4. Page reloads → All JS state lost ❌
5. /student/play loads → No music playing 🔇
```

**After Fix:**
```
1. Village page → 'village' music playing 🎵
2. Complete quiz → Click "Try Again"
3. audioStore.saveCurrentTrack() → sessionStorage.setItem('audioTrack', 'village') 💾
4. location.replace('/student/play?level=2')
5. Page reloads → All JS state lost
6. /student/play loads → onMount() fires
7. audioStore.restoreSavedTrack() → sessionStorage.getItem('audioTrack') === 'village'
8. audioStore.playTrack('village') → 'village' music resumes! 🎵✅
```

### Flow: Retake from Dashboard

**Scenario:**
```
1. Dashboard page → 'default' music playing 🎵
2. Navigate to /student/play → Select Level 2
3. Complete quiz → Click "Try Again"
4. audioStore.saveCurrentTrack() → sessionStorage.setItem('audioTrack', 'default')
5. location.replace('/student/play?level=2')
6. Page reloads → audioStore.restoreSavedTrack()
7. 'default' music resumes! 🎵✅
```

## Why sessionStorage?

We use `sessionStorage` instead of `localStorage` because:

✅ **Persists across page reloads** (same tab/window)
✅ **Cleared when tab closes** (no stale state across sessions)
✅ **One-time use** (we remove it after restoring)
✅ **Perfect for navigation state**

vs. `localStorage`:
- Would persist forever
- Could cause music to auto-play on next login unexpectedly
- Requires manual cleanup logic

## Console Logs

When working correctly, you'll see:

```
💾 Saved audio state: village, muted: false    (before navigation)
🔄 Restoring audio state: village, muted: false (after page load)
🎵 Playing village music                        (music resumes)
```

## Testing Checklist

### Test 1: Village Music Persistence
1. ✅ Login → Navigate to village
2. ✅ Verify "village" music is playing
3. ✅ Click on a scene → Complete quiz
4. ✅ Click "Try Again"
5. ✅ **Expected**: Village music continues playing after page reload

### Test 2: Default Music Persistence
1. ✅ Login → Stay on dashboard ("default" music)
2. ✅ Navigate to /student/play
3. ✅ Open Level 2 story → Complete quiz
4. ✅ Click "Try Again"
5. ✅ **Expected**: Default music continues playing

### Test 3: Muted State Persistence
1. ✅ Mute the audio (click audio control button)
2. ✅ Complete quiz → Click "Try Again"
3. ✅ **Expected**: Audio remains muted after reload

### Test 4: Multiple Retakes
1. ✅ Complete quiz → Retake → Retake again
2. ✅ **Expected**: Music stays consistent across all retakes

### Test 5: Cross-Level Retakes
1. ✅ In village with "village" music
2. ✅ Complete Level 1 → Retake
3. ✅ Complete Level 2 → Retake
4. ✅ Complete Level 3 → Retake
5. ✅ **Expected**: Village music persists throughout all retakes

## Edge Cases Handled

### 1. No Saved Track
If `sessionStorage.getItem('audioTrack')` returns `null`:
- No music will auto-play
- User can manually start music with audio control button
- Normal behavior (not an error)

### 2. Invalid Track Name
If saved track is not 'default' or 'village':
- Will be ignored
- No music plays
- Prevents errors from corrupted data

### 3. Audio Muted
If saved state shows `audioMuted: true`:
- Music will NOT auto-play
- Muted state is preserved
- User can unmute manually

### 4. Multiple Tabs
sessionStorage is **per-tab**, so:
- Each tab has its own audio state
- No conflicts between tabs
- One tab's retake doesn't affect another

## Related Files

### Audio Management
- `src/lib/store/audio_store.js` - Audio store with singleton elements
- `src/lib/components/AudioControl.svelte` - UI control button

### Music Triggers
- `src/routes/student/dashboard/+page.svelte` - Plays 'default'
- `src/routes/student/village/+page.svelte` - Plays 'village'
- `src/routes/student/game/trash_*/+page.svelte` - Stops global, plays game music

### Retake Flows
- All `slide_last.svelte` files in Levels 1-3
- All use `audioStore.saveCurrentTrack()` before `location.replace()`

## Troubleshooting

### Music doesn't resume after retake
**Check:**
1. Open browser console (F12)
2. Look for: `💾 Saved audio state: ...`
3. After reload, look for: `🔄 Restoring audio state: ...`
4. If missing, check that `audioStore.saveCurrentTrack()` is called before navigation

### Music plays wrong track
**Check:**
1. Verify which page you're on when clicking "Try Again"
2. Village page should save 'village'
3. Other pages should save 'default'
4. Check sessionStorage in DevTools: Application → Session Storage → audioTrack

### Music auto-plays when it shouldn't
**Check:**
1. Look at sessionStorage - it should be cleared after restore
2. If persisting, check that `sessionStorage.removeItem()` is called
3. Clear sessionStorage manually: `sessionStorage.clear()`

## Browser Compatibility

✅ Chrome/Edge: Full support
✅ Firefox: Full support  
✅ Safari: Full support
✅ Opera: Full support

sessionStorage is supported in all modern browsers.

## Performance Notes

- sessionStorage operations are synchronous but very fast (< 1ms)
- Only stores 2 small strings (track name + muted boolean)
- No impact on page load performance
- Automatic cleanup after restoration

## Future Improvements

Potential enhancements:
- Save/restore volume level
- Save/restore playback position (resume from same timestamp)
- Support for more audio tracks
- Fade in/out on track changes

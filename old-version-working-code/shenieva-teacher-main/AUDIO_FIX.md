# Background Music Fix 🎵

## Problem
Background music from trash games continued playing even after:
- Exiting the game
- Navigating to other pages
- Logging out

## Root Causes

### 1. Singleton Audio Elements Not Destroyed
The `audioStore` uses singleton `HTMLAudioElement` instances that persist across navigation:
```javascript
let defaultBGM = null;  // Created once, never destroyed
let villageBGM = null;  // Created once, never destroyed
```

When you logout, `audioStore.stopAll()` only **paused** the audio, but didn't destroy the elements.

### 2. Trash Game Audio Not Properly Cleaned
Each trash game creates its own `bgMusic` element but only paused it in `onDestroy`, without clearing the source.

## Solutions Implemented

### 1. Added `audioStore.destroy()` Method
New method in `src/lib/store/audio_store.js`:

```javascript
// Completely destroy audio elements (call on logout)
destroy() {
    console.log('🗑️ Destroying audio elements');
    
    // Stop and remove all audio
    if (defaultBGM) {
        defaultBGM.pause();
        defaultBGM.src = '';      // Clear the source
        defaultBGM.load();        // Force unload
        defaultBGM = null;        // Remove reference
    }
    if (villageBGM) {
        villageBGM.pause();
        villageBGM.src = '';
        villageBGM.load();
        villageBGM = null;
    }
    
    currentlyPlaying = null;
    initialized = false;         // Reset initialization flag
    
    update(state => ({
        ...state,
        currentTrack: null,
        isPlaying: false
    }));
}
```

**Why this works:**
- `pause()` - Stops playback
- `src = ''` - Clears the audio source URL
- `load()` - Forces the browser to unload the audio data
- `= null` - Removes the JavaScript reference
- `initialized = false` - Allows re-initialization on next login

### 2. Updated Logout to Call `destroy()`
Modified `src/routes/student/dashboard/+page.svelte`:

```javascript
function confirmLogout() {
    // Completely destroy audio elements to prevent music from continuing
    audioStore.destroy();  // Changed from audioStore.stopAll()
    
    // ... rest of logout logic
}
```

### 3. Enhanced Trash Game Cleanup
Updated all three trash games (`trash_1`, `trash_2`, `trash_3`):

```javascript
onDestroy(() => {
    console.log('🧹 Cleaning up trash game X');
    
    // Stop game audio completely
    if (bgMusic) {
        bgMusic.pause();
        bgMusic.currentTime = 0;
        bgMusic.src = '';      // ← ADDED: Clear source
    }
    if (collectSound) {
        collectSound.pause();
        collectSound.src = '';  // ← ADDED: Clear source
    }
    
    // Resume global background music when leaving game
    audioStore.playTrack('default');
});
```

## Files Modified

1. **src/lib/store/audio_store.js**
   - Enhanced `stopAll()` with console logging and proper reset
   - Added new `destroy()` method for complete cleanup

2. **src/routes/student/dashboard/+page.svelte**
   - Changed `audioStore.stopAll()` to `audioStore.destroy()` in logout

3. **src/routes/student/game/trash_1/+page.svelte**
   - Enhanced `onDestroy()` to clear audio sources

4. **src/routes/student/game/trash_2/+page.svelte**
   - Enhanced `onDestroy()` to clear audio sources

5. **src/routes/student/game/trash_3/+page.svelte**
   - Enhanced `onDestroy()` to clear audio sources

## Testing Checklist

### Test 1: Trash Game Exit
1. Login as student
2. Play any trash game
3. Exit the game (back to village/dashboard)
4. **Expected**: No music playing from the game

### Test 2: Logout After Trash Game
1. Login as student
2. Play trash game
3. Exit game
4. Logout
5. **Expected**: Complete silence, no music playing

### Test 3: Navigation During Game
1. Login as student
2. Start trash game
3. Navigate away using browser back button
4. **Expected**: Game music stops, default music resumes

### Test 4: Multiple Trash Games
1. Play trash_1, exit
2. Play trash_2, exit
3. Play trash_3, exit
4. **Expected**: Only one audio source playing at a time

### Test 5: Re-login After Logout
1. Login → Play game → Logout
2. Login again as different student
3. **Expected**: Audio works correctly, no leftover music from previous session

## Console Logs to Watch For

When working correctly, you should see:

```
🔇 Stopping all audio          (when stopping music)
🗑️ Destroying audio elements   (on logout)
🧹 Cleaning up trash game X    (when exiting game)
🎵 Playing background music    (when music starts)
```

## How Audio Lifecycle Works Now

### On Login
```
1. Student logs in
2. Dashboard mounts → audioStore.playTrack('default')
3. Audio store initializes if needed
4. Default BGM starts playing
```

### During Trash Game
```
1. Navigate to trash game
2. onMount() → audioStore.stopAll()  (stops default BGM)
3. Trash game creates its own bgMusic element
4. Game plays (if music is started in game logic)
```

### On Exit Trash Game
```
1. onDestroy() fires
2. Trash game bgMusic.pause() → .src = '' (complete cleanup)
3. audioStore.playTrack('default') (resume default music)
```

### On Logout
```
1. confirmLogout() called
2. audioStore.destroy() (complete audio destruction)
3. All audio elements set to null
4. initialized flag reset to false
5. Navigate to login page (no audio)
```

### On Re-login
```
1. New student logs in
2. audioStore.playTrack('default') called
3. Audio store re-initializes (because initialized = false)
4. Fresh audio elements created
5. Music plays normally
```

## Why Previous Approach Failed

**Old code:**
```javascript
onDestroy(() => {
    if (bgMusic) {
        bgMusic.pause();  // Only paused, didn't clear source
        bgMusic.currentTime = 0;
    }
    audioStore.playTrack('default');  // Started new music
})
```

**Problems:**
- `bgMusic.pause()` kept the audio data loaded in memory
- Source file remained attached to the element
- Browser could continue buffering/playing in background
- Singleton audio elements (`defaultBGM`, `villageBGM`) never destroyed on logout

**New code:**
```javascript
onDestroy(() => {
    if (bgMusic) {
        bgMusic.pause();
        bgMusic.currentTime = 0;
        bgMusic.src = '';  // ← Clears the source
    }
})
```

**Benefits:**
- Completely releases audio resources
- Browser unloads the audio file from memory
- No background playback possible
- Clean state for next game session

## Additional Notes

### Memory Leaks Prevention
By clearing `src` and setting elements to `null`, we:
- Free up browser memory
- Prevent audio file caching issues
- Avoid multiple audio streams conflicting
- Ensure clean state between sessions

### Browser Compatibility
This approach works across all modern browsers:
- Chrome/Edge: ✅
- Firefox: ✅
- Safari: ✅
- Opera: ✅

### Audio Auto-play Policy
The audio store properly handles browser auto-play restrictions:
```javascript
defaultBGM.play()
    .then(() => console.log('🎵 Playing'))
    .catch(err => console.warn('⚠️ Audio play blocked:', err.message));
```

Users must interact with the page (click audio button) before music can play.

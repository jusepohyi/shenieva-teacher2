# 🎮 Asset Preloading Analysis for Readville Village

**Date**: November 19, 2025  
**Purpose**: Analyze asset structure and recommend optimal preloading strategy

---

## 📊 Current Asset Inventory

### Walking Animation Sprites
**Location**: `/assets/Level_Walkthrough/shenievia/{gender}/`

#### Boy Character:
- **Forward** (walking right): `1.png`, `2.png`, `3.png` (3 frames)
- **Back** (walking left): `1.png`, `2.png`, `3.png` (3 frames)
- **Front** (idle/facing camera): `1.png` (1 frame)

#### Girl Character:
- Same structure as boy (3 forward, 3 back, 1 front)

**Total Walking Sprites**: 7 frames per character × 2 genders = **14 PNG files**
**WebP Converted**: ✅ Available in `/converted/assets/Level_Walkthrough/shenievia/`

---

### Scene Backgrounds
**Location**: `/assets/Level_Walkthrough/places/`

1. **school.webp** - Readville Village School
2. **plain.webp** - Plains (used twice: scenes 1 and 7)
3. **sarisaristore.webp** - Sari-Sari Store (Level 1)
4. **houses1.webp** - Village Houses (first set)
5. **wetmarket.webp** - Wet Market (Level 2)
6. **houses2.webp** - Village Houses (second set)
7. **plaza.webp** - Plaza (Level 3)
8. **home.webp** - Shenievia's Home (exterior)
9. **home-inside.webp** - Home Interior

**Total Scene Backgrounds**: **9 unique WebP files**  
*(Plain.webp is reused in scenes 1 and 7)*

---

### Gift System Assets
**Location**: `/converted/assets/Level_Walkthrough/gift/`

- **gift-box.webp** - Gift box icon
- **gifts/{item}.webp** - Individual gift images (pencils, notebooks, etc.)

**Estimate**: ~10-15 gift item images

---

### Story/Level Assets
**Levels**: Level 1, Level 2, Level 3  
**Stories**: Story 1 (subdivided into story1-1, story1-2, story1-3), Story 2, Story 3

**Total**: 476 WebP images (35.31 MB)

---

## 🎯 Walking Animation Requirements

### Critical Performance Needs:

1. **Frame Switching Speed**: 260ms per frame (as per code: `setInterval(..., 260)`)
2. **Smooth Animation**: All 3 frames must be pre-cached in memory
3. **Direction Changes**: Front sprite (1.png) must load instantly when changing direction
4. **No Stuttering**: Any delay in frame loading = broken walking animation

### Current Animation Flow:
```javascript
// Walking right: cycles through forward/1.png → forward/2.png → forward/3.png
// Walking left: cycles through back/1.png → back/2.png → back/3.png
// Direction change: shows front/1.png → then new direction
```

**Problem**: If frames aren't preloaded, you'll see:
- ❌ Stuttering when switching from frame 1 → 2 → 3
- ❌ Character "freezing" when changing direction
- ❌ Blank spaces or old frames sticking

---

## 💡 Recommended Preloading Strategy

### **Option A: Village-Entry Preloader** (RECOMMENDED)

**When**: Single big loading screen when entering Readville Village  
**What to preload**:

1. **All Walking Sprites** (14 images) - **CRITICAL**
   - Boy: forward (1-3), back (1-3), front (1)
   - Girl: forward (1-3), back (1-3), front (1)
   - **Why**: Walking animations must be instant

2. **All Scene Backgrounds** (9 images)
   - School, plains, sari-sari store, houses1, wet market, houses2, plaza, home exterior, home interior
   - **Why**: User walks through all scenes in one session

3. **UI/Gift System** (~15 images)
   - Gift box, gift items
   - **Why**: Used in home interior, better UX if instant

**Total to Preload**: ~38 images (walking + scenes + gifts)  
**Estimated Size**: 5-8 MB (WebP compressed)  
**Load Time**: 3-5 seconds on average connection

#### Implementation:
```javascript
// Preload all Readville Village assets before showing the scene
const villageAssets = [
  // Walking sprites for current gender
  `/converted/assets/Level_Walkthrough/shenievia/${gender}/forward/1.webp`,
  `/converted/assets/Level_Walkthrough/shenievia/${gender}/forward/2.webp`,
  `/converted/assets/Level_Walkthrough/shenievia/${gender}/forward/3.webp`,
  `/converted/assets/Level_Walkthrough/shenievia/${gender}/back/1.webp`,
  `/converted/assets/Level_Walkthrough/shenievia/${gender}/back/2.webp`,
  `/converted/assets/Level_Walkthrough/shenievia/${gender}/back/3.webp`,
  `/converted/assets/Level_Walkthrough/shenievia/${gender}/front/1.webp`,
  
  // All scene backgrounds
  '/assets/Level_Walkthrough/places/school.webp',
  '/assets/Level_Walkthrough/places/plain.webp',
  '/assets/Level_Walkthrough/places/sarisaristore.webp',
  '/assets/Level_Walkthrough/places/houses1.webp',
  '/assets/Level_Walkthrough/places/wetmarket.webp',
  '/assets/Level_Walkthrough/places/houses2.webp',
  '/assets/Level_Walkthrough/places/plaza.webp',
  '/assets/Level_Walkthrough/places/home.webp',
  '/converted/assets/Level_Walkthrough/places/home-inside.webp'
  // Add gift box and common UI elements
];

// Preload with progress tracking
let loaded = 0;
const total = villageAssets.length;

Promise.all(
  villageAssets.map(url => {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => {
        loaded++;
        loadingProgress = Math.floor((loaded / total) * 100);
        loadingText = `Loading assets... ${loaded}/${total}`;
        resolve();
      };
      img.onerror = reject;
      img.src = url;
    });
  })
).then(() => {
  console.log('All village assets loaded!');
  showLoading = false;
  // Start the village experience
});
```

---

### **Option B: Lazy Loading Per Scene** (NOT RECOMMENDED)

**When**: Preload assets only when entering each scene  
**What to preload**: Current scene background + walking sprites

❌ **Problems**:
- Still need to preload ALL walking sprites (same 14 images)
- Adds loading delays between scene transitions
- Breaks immersion with frequent loading screens
- Walking animations MUST be preloaded anyway

**Verdict**: No advantage over Option A, worse UX

---

### **Option C: Service Worker + PWA** (FUTURE ENHANCEMENT)

**When**: After initial deployment success  
**What**: Cache all assets for offline use

✅ **Benefits**:
- Instant loads on return visits
- Works offline
- Progressive Web App capabilities

⚠️ **Complexity**:
- Requires service worker setup
- Cache invalidation strategy
- More testing needed

**Recommendation**: Implement later after basic preloading works

---

## 🔧 Implementation Plan

### Phase 1: Fix Walking Sprite Paths ✅ URGENT
**Issue**: `village/+page.svelte` line 1052 uses `/assets/` instead of `/converted/assets/`

**Fix Required**:
```javascript
// CURRENT (WRONG):
const basePath = `/assets/Level_Walkthrough/shenievia/${gender}`;

// SHOULD BE:
const basePath = `/converted/assets/Level_Walkthrough/shenievia/${gender}`;
```

**Impact**: Walking animations currently load PNG instead of WebP!

---

### Phase 2: Enhance Loading Screen ✅ READY
**Current**: Already has loading screen with progress (lines 1150-1270)  
**Enhancement Needed**: Replace simulated loading with REAL asset preloading

**Changes**:
1. Replace setTimeout simulation with actual Image() preloading
2. Track real progress of asset downloads
3. Update `loadingProgress` and `loadingText` based on actual loaded assets
4. Only hide loading screen when ALL assets are ready

---

### Phase 3: Browser Cache Optimization ✅ AUTOMATIC
Once assets are loaded once, browser caches them automatically.

**No code needed** - HTTP headers handle this

**Netlify Cache Headers** (optional enhancement):
```toml
# netlify.toml
[[headers]]
  for = "/converted/assets/*"
  [headers.values]
    Cache-Control = "public, max-age=31536000, immutable"
```

---

## 📈 Expected Performance

### Before Preloading:
- ❌ Walking animation stutters (frames load on-demand)
- ❌ Scene backgrounds flicker when transitioning
- ❌ First direction change shows delay
- ⚠️ Total load time: Spread across user interaction (feels slow)

### After Preloading:
- ✅ Smooth walking animation (all frames in memory)
- ✅ Instant scene transitions (backgrounds pre-cached)
- ✅ Responsive direction changes (front sprite ready)
- ✅ Total load time: 4-5 seconds upfront, then instant everything

---

## 🎯 Answers to Your Questions

### 1. How many walking frames per character?
**Answer**: 7 frames per character
- 3 forward (right)
- 3 back (left)  
- 1 front (idle/turning)

### 2. How many different scenes have walking animations?
**Answer**: 10 scenes total (9 village scenes + home interior)
- All scenes use the same walking sprite system
- Walking sprites are shared across ALL scenes

### 3. Are character sprites shared across scenes?
**Answer**: ✅ YES - Same 7 sprites used in ALL scenes!
- Only need to preload once at village entry
- Character sprites never change between scenes
- Only backgrounds change

### 4. Loading screen preference?
**Answer**: ✅ One big loading at Readville Village entry
- User confirmed: "One big loading when entering the Readville Village"
- Current loading screen already exists (just needs real preloading)

---

## 🚀 Next Steps

### Immediate Actions:
1. ✅ **Fix sprite paths** - Change `/assets/` to `/converted/assets/` in village page
2. ✅ **Implement real preloading** - Replace simulated loading with actual asset loading
3. ✅ **Test walking animations** - Verify smooth frame switching

### Future Enhancements:
- 🔜 Service Worker for offline support
- 🔜 Netlify cache headers for faster repeat visits
- 🔜 Preload Level 1/2/3 assets on-demand (when user enters those levels)

---

## 📝 Summary

**Best Strategy**: **One big preloader at Village entry**

**What to Load**:
- 14 walking sprites (7 per gender, load only current gender)
- 9 scene backgrounds
- ~15 UI/gift images

**Total**: ~38 images, 5-8 MB, 4-5 second load

**Why This Works**:
- Walking animations MUST be preloaded (critical for smooth gameplay)
- All scenes are visited in one session (makes sense to preload all)
- One-time cost, then instant experience
- User confirmed preference for this approach

**Result**: Smooth walking, instant scene transitions, polished UX ✨

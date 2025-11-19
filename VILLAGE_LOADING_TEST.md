# 🧪 Village Asset Preloading - Test Guide

**Date**: November 19, 2025  
**Status**: Ready for Testing

---

## ✅ Implementation Verified

I've reviewed the code and confirmed the asset preloading is **correctly implemented**:

### Code Flow:
1. ✅ `preloadVillageAssets()` function exists (lines 93-167)
2. ✅ Called in `onMount()` with `await` (line 179)
3. ✅ Loading screen shows during preload (line 1193)
4. ✅ Progress tracking updates `loadingProgress` and `loadingText`
5. ✅ Walking sprites use `/converted/` WebP paths (line 1068)

---

## 🎮 How to Test

### Step 1: Navigate to Village
1. Open browser: **http://localhost:5174**
2. Login as a student
3. Go to Dashboard
4. Click **"Visit Readville Village"** or similar button

### Step 2: Watch Loading Screen
You should see:
- ✅ **Loading screen appears** with Readville Village title
- ✅ **Character preview** (boy/girl front sprite)
- ✅ **Progress bar** filling up 0% → 100%
- ✅ **Loading text** changes:
  - "Loading village assets..."
  - "Loading character sprites... 3/16"
  - "Loading scenes... 12/16"
  - "Almost ready... 16/16"
  - "Ready!"
- ✅ **Percentage** shows real progress (not simulated)

### Step 3: Test Walking
After loading completes:
1. ✅ Press **RIGHT arrow** → Character walks right smoothly
2. ✅ Verify 3 frames cycle without stuttering
3. ✅ Press **LEFT arrow** → Character walks left smoothly
4. ✅ Change direction → Front sprite shows instantly
5. ✅ **No freezing, blank spaces, or delays**

### Step 4: Check Browser Console
Open DevTools (F12) and check for:
- ✅ **No errors** in console
- ✅ Look for: `"All village assets loaded!"` (if added)
- ❌ Watch for: Failed to load warnings

### Step 5: Test Scene Transitions
1. ✅ Walk from School → Plains → Sari-Sari Store
2. ✅ Each background should appear **instantly**
3. ✅ No white flashes or loading delays

---

## 🔍 What to Look For

### ✅ Success Indicators:
- Loading screen shows **real progress** (not timed simulation)
- Progress counts from 1/16 → 16/16
- Walking animation is **perfectly smooth**
- Frame switching happens without delay
- Scene backgrounds load instantly

### ❌ Failure Indicators:
- Loading stuck at 0%
- Console shows "Failed to load: /converted/..." errors
- Walking animation stutters or freezes
- Blank character when changing direction
- Scene backgrounds flicker or delay

---

## 🐛 Common Issues & Solutions

### Issue 1: Loading Stuck at 0%
**Problem**: Images not loading  
**Check**: 
- DevTools Network tab - are files downloading?
- Console errors - missing files?
**Solution**: Verify WebP files exist in `/static/converted/assets/`

### Issue 2: Walking Stutters
**Problem**: Frames not preloaded correctly  
**Check**: 
- Are all 7 sprite files loaded?
- Console warnings about failed loads?
**Solution**: Check walking sprite paths are correct

### Issue 3: Scene Backgrounds Missing
**Problem**: Background paths incorrect  
**Check**: 
- Are scene backgrounds in `/static/assets/Level_Walkthrough/places/`?
- Console errors for missing files?
**Solution**: Verify background file paths

---

## 📊 Expected Asset List

The preloader should load exactly **16 files**:

### Character Sprites (7 files):
1. `/converted/assets/Level_Walkthrough/shenievia/{gender}/forward/1.webp`
2. `/converted/assets/Level_Walkthrough/shenievia/{gender}/forward/2.webp`
3. `/converted/assets/Level_Walkthrough/shenievia/{gender}/forward/3.webp`
4. `/converted/assets/Level_Walkthrough/shenievia/{gender}/back/1.webp`
5. `/converted/assets/Level_Walkthrough/shenievia/{gender}/back/2.webp`
6. `/converted/assets/Level_Walkthrough/shenievia/{gender}/back/3.webp`
7. `/converted/assets/Level_Walkthrough/shenievia/{gender}/front/1.webp`

### Scene Backgrounds (9 files):
8. `/assets/Level_Walkthrough/places/school.webp`
9. `/assets/Level_Walkthrough/places/plain.webp`
10. `/assets/Level_Walkthrough/places/sarisaristore.webp`
11. `/assets/Level_Walkthrough/places/houses1.webp`
12. `/assets/Level_Walkthrough/places/wetmarket.webp`
13. `/assets/Level_Walkthrough/places/houses2.webp`
14. `/assets/Level_Walkthrough/places/plaza.webp`
15. `/assets/Level_Walkthrough/places/home.webp`
16. `/converted/assets/Level_Walkthrough/places/home-inside.webp`

**Note**: Gender is determined by student data (boy/girl)

---

## 🔬 Advanced Testing

### Test 1: Network Throttling
1. Open DevTools → Network tab
2. Set throttling to "Slow 3G"
3. Enter village
4. **Expected**: Loading takes longer but progress shows accurately

### Test 2: Failed Image
1. Rename one sprite file temporarily
2. Enter village
3. **Expected**: Console warning but loading continues (doesn't hang)

### Test 3: Browser Cache
1. Enter village once (loads all assets)
2. Exit village
3. Re-enter village
4. **Expected**: Much faster (browser serves from cache)

### Test 4: Different Genders
1. Test with **boy character** → Should load boy sprites
2. Test with **girl character** → Should load girl sprites
3. **Expected**: Only loads sprites for current gender

---

## 📝 Test Results Template

```
Date: ___________
Browser: ___________
Student Gender: ___________

✅ Loading screen appears
✅ Progress bar shows 0-100%
✅ Loading text updates correctly
✅ Progress counts 1/16 → 16/16
✅ Walking right is smooth (3 frames)
✅ Walking left is smooth (3 frames)
✅ Direction change shows front sprite
✅ Scene transitions are instant
✅ No console errors
✅ Browser cache works on re-entry

Issues found:
_________________________________
_________________________________

Overall status: ✅ PASS / ❌ FAIL
```

---

## 🎯 Quick Test Checklist

- [ ] Visit village from dashboard
- [ ] Loading screen shows
- [ ] Progress counts 1/16 → 16/16
- [ ] "Ready!" message appears at 100%
- [ ] Village scene loads
- [ ] Press RIGHT → smooth walking
- [ ] Press LEFT → smooth walking
- [ ] Change direction → instant front sprite
- [ ] Move to next scene → instant background
- [ ] No console errors
- [ ] Re-enter village → faster load

---

## ✅ Conclusion

The asset preloading system is **correctly implemented** in the code:
- Real image preloading (not simulated)
- Progress tracking
- WebP sprite paths
- Error handling

**Next Step**: Test it live at **http://localhost:5174** to verify it works as expected!

If there are any issues, check:
1. Browser console for errors
2. Network tab for failed file loads
3. File paths in `/static/converted/` folder

---

**Status**: Ready for manual testing! 🚀

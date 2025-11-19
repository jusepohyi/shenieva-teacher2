# 🚀 Quick WebP Testing Guide - Your Approach

## What This Does

Updates all image paths to point to `/converted/` folder:
- ✅ No file moving required
- ✅ Easy rollback with Git
- ✅ Test WebP quickly
- ✅ Clean up later if it works

---

## 🎯 Quick Start (3 Steps)

### Step 1: Preview Changes (30 seconds)
```powershell
# See what will be changed (safe, no modifications)
.\use-converted-webp.ps1 -DryRun
```

**This shows you examples like:**
```
"/trash_collect_game/trash/01-Egg.png"
→ "/converted/trash_collect_game/trash/01-Egg.webp"

"/assets/LEVEL_3/STORY_1/PIC1.jpg"
→ "/converted/assets/LEVEL_3/STORY_1/PIC1.webp"
```

---

### Step 2: Apply Changes (30 seconds)
```powershell
# Update all paths to use /converted/ folder
.\use-converted-webp.ps1
```

**This will update ~200-300 references in your code.**

---

### Step 3: Test Locally (5 minutes)
```powershell
# Start dev server
npm run dev
```

**Visit these pages and check for broken images:**
- http://localhost:5173/student/game/trash_1
- http://localhost:5173/student/game/trash_2
- http://localhost:5173/student/game/trash_3
- http://localhost:5173/student/play (Levels 1, 2, 3)
- http://localhost:5173/student/village (Gift shop)

**Open browser console (F12) - check for:**
- ❌ 404 errors (images not found)
- ✅ No errors = everything works!

---

## 📋 What Gets Changed

### Before:
```javascript
// Game sprites
src="/trash_collect_game/ground/soil.png"
src="/trash_collect_game/trash/01-Egg.png"

// Level images
image: "/assets/LEVEL_3/STORY_1/PIC1.jpg"

// Gift shop
image: '/assets/Level_Walkthrough/gift/gifts/pencil.png'

// Backgrounds
image: "../../assets/school-bg.gif"
```

### After:
```javascript
// Game sprites
src="/converted/trash_collect_game/ground/soil.webp"
src="/converted/trash_collect_game/trash/01-Egg.webp"

// Level images
image: "/converted/assets/LEVEL_3/STORY_1/PIC1.webp"

// Gift shop
image: '/converted/assets/Level_Walkthrough/gift/gifts/pencil.webp'

// Backgrounds
image: "../../converted/assets/school-bg.webp"
```

---

## ⚠️ Manual Checks Required

After running the script, search for these patterns:

### Template Literals with Variables:
```javascript
// Search in trash game files for patterns like:
`/trash_collect_game/trash/${name}.png`

// Should become:
`/converted/trash_collect_game/trash/${name}.webp`

// The script handles paths, but double-check extensions in template literals
```

**Files to manually verify:**
- `src/routes/student/game/trash_1/+page.svelte` (line ~140)
- `src/routes/student/game/trash_2/+page.svelte` (line ~140)
- `src/routes/student/game/trash_3/+page.svelte` (line ~140)

**Look for this pattern:**
```javascript
Array(25).fill(null).map((_, i) => 
  loadImage(`/converted/trash_collect_game/trash/${...}.png`)  // ← Change .png to .webp
)
```

---

## ✅ Testing Checklist

### Local Testing:
- [ ] Run script: `.\use-converted-webp.ps1`
- [ ] Review changes: `git diff src/`
- [ ] Start dev: `npm run dev`
- [ ] Test trash game 1 (no broken images)
- [ ] Test trash game 2 (no broken images)
- [ ] Test trash game 3 (no broken images)
- [ ] Test Level 1 slides (images load)
- [ ] Test Level 2 slides (images load)
- [ ] Test Level 3 slides (images load)
- [ ] Test gift shop (items display)
- [ ] Test village (backgrounds load)
- [ ] Check console (no 404 errors)

### Build Testing:
- [ ] Build: `npm run build`
- [ ] Preview: `npm run preview`
- [ ] Visit http://localhost:4173 (production build)
- [ ] Test same pages as above
- [ ] Verify WebP files are used (check Network tab)

### If All Good:
- [ ] Commit: `git add . && git commit -m "Use WebP images from /converted/ folder"`
- [ ] Push: `git push origin main`
- [ ] Wait for Netlify deploy
- [ ] Test on live Netlify site
- [ ] Check Network tab (confirm WebP loading)

---

## 🔄 Easy Rollback

If something breaks:

```powershell
# Undo all code changes
git checkout -- src/

# Or if you already committed:
git revert HEAD
```

---

## 📊 File Structure (No Changes)

```
static/
├── converted/              ← WebP files (used by updated code)
│   ├── assets/
│   └── trash_collect_game/
├── assets/                 ← Original PNG/JPG (unused for now)
└── trash_collect_game/     ← Original PNG (unused for now)
```

**Benefit:** Both versions exist, easy to switch back!

---

## 🎯 What Happens on Netlify

When you push to GitHub and Netlify builds:

1. ✅ Netlify copies entire `static/` folder to production
2. ✅ Your code references `/converted/...` paths
3. ✅ Users download WebP files (smaller, faster)
4. ✅ Original PNG/JPG files are also copied but unused

**Note:** Repository will be larger (has both versions) but users only download WebP.

---

## 🧹 Clean Up Later (Optional)

After 1 week of stability on Netlify:

```powershell
# Move WebP to proper locations
.\migrate-webp-files.ps1

# Update paths (remove /converted/)
.\update-to-webp-references.ps1

# Delete originals
Remove-Item -Path "static\assets" -Recurse -Force
Remove-Item -Path "static\trash_collect_game" -Recurse -Force
Remove-Item -Path "static\converted" -Recurse -Force

# Rename converted to proper structure
# (Or just keep it - works fine!)
```

---

## 💡 Pro Tips

### 1. Test Incrementally
```powershell
# Test one game first
npm run dev
# Visit only trash_3 game
# If it works, test others
```

### 2. Use Browser DevTools
- Open Network tab (F12)
- Filter by "Images"
- Verify `.webp` files are loading
- Check file sizes (should be smaller)

### 3. Check Console
- Look for 404 errors
- Look for "Failed to load resource"
- No errors = success! ✅

---

## 🆘 Troubleshooting

### Issue: Images not loading
**Check:**
1. WebP files exist in `static/converted/` folder
2. Paths match exactly (case-sensitive)
3. Browser console shows actual error

### Issue: Build fails
**Solution:**
```powershell
npm run build 2>&1 | Out-File build-errors.txt
# Check build-errors.txt for details
```

### Issue: Some images work, some don't
**Likely cause:** Template literal not updated
**Fix:** Manually check trash game files for `${...}.png`

---

## ✨ Expected Results

After deployment:
- ✅ All images load correctly
- ✅ WebP files are used (check Network tab)
- ✅ 18.8% smaller downloads
- ✅ Faster page loads
- ✅ Better mobile performance

---

## 🎉 Ready to Start?

```powershell
# 1. Preview
.\use-converted-webp.ps1 -DryRun

# 2. Apply
.\use-converted-webp.ps1

# 3. Test
npm run dev
```

Then test in your browser and push to GitHub! 🚀

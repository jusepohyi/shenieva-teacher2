# 🚀 WebP Migration - Quick Start

## 📊 Analysis Complete!

**You've successfully converted:**
- ✅ **476 WebP files**
- ✅ **35.31 MB total** (down from 43.48 MB)
- ✅ **8.17 MB saved** (18.8% reduction)

---

## ⚡ 3-Step Migration Process

### Step 1: Move WebP Files to Correct Locations (1 minute)

```powershell
# Test first (safe preview)
.\migrate-webp-files.ps1 -DryRun

# If it looks good, run for real
.\migrate-webp-files.ps1
```

**What this does:**
- ✅ Backs up original PNG/JPG files to `_original_images_backup/`
- ✅ Moves WebP files from `static/converted/` to `static/`
- ✅ Removes duplicate `trash_collect_game` from assets
- ✅ Cleans up the `converted/` folder

---

### Step 2: Update Code References (1 minute)

```powershell
# Preview changes first
.\update-to-webp-references.ps1 -DryRun

# Apply changes
.\update-to-webp-references.ps1
```

**What this does:**
- ✅ Scans all `.svelte` files
- ✅ Changes `.png` → `.webp`
- ✅ Changes `.jpg` → `.webp`
- ✅ Changes `.gif` → `.webp`
- ✅ Updates all image references automatically

---

### Step 3: Test & Deploy (5 minutes)

```powershell
# Test locally
npm run dev
```

**Visit these pages to verify:**
- http://localhost:5173/student/game/trash_1 ← Check game sprites
- http://localhost:5173/student/game/trash_2
- http://localhost:5173/student/game/trash_3
- http://localhost:5173/student/play ← Check level images
- http://localhost:5173/student/village ← Check gift shop

**Look for:**
- ✅ All images load correctly
- ✅ No broken images (check browser console)
- ✅ Games work normally

**Then build and deploy:**
```powershell
# Build for production
npm run build

# Preview build
npm run preview

# If all good, commit
git add .
git commit -m "Migrate all images to WebP format - 18.8% size reduction"
git push origin main
```

---

## 📁 Folder Structure After Migration

### BEFORE (Current):
```
static/
├── converted/           ← Contains all WebP files (temporary)
│   ├── assets/
│   └── trash_collect_game/
├── assets/              ← Original PNG/JPG files
└── trash_collect_game/  ← Original PNG files
```

### AFTER (Migration):
```
static/
├── _original_images_backup/  ← Backup of originals (can delete later)
│   ├── assets/
│   └── trash_collect_game/
├── assets/              ← WebP files (replaced)
│   ├── LEVEL_1/
│   ├── LEVEL_2/
│   ├── LEVEL_3/
│   ├── Level_Walkthrough/
│   ├── story1/
│   └── (no trash_collect_game here - it's a duplicate)
└── trash_collect_game/  ← WebP files (replaced)
    ├── boy/
    ├── girl/
    ├── ground/
    ├── house/
    ├── trash/
    └── trees/
```

---

## ⚠️ Important Notes

### Duplicate Detected ⚠️
Your conversion created **two locations** for trash_collect_game:
1. `static/trash_collect_game/` ← **Correct** (used by code)
2. `static/assets/trash_collect_game/` ← **Incorrect** (duplicate)

**The migration script will remove the duplicate automatically.**

### What Gets Backed Up:
- All original PNG/JPG/GIF files → `static/_original_images_backup/`
- **Don't delete backups until you've verified deployment on Netlify!**

### What Gets Updated in Code:
The script updates these patterns automatically:
```javascript
// Image paths
"/trash_collect_game/ground/soil.png" → "/trash_collect_game/ground/soil.webp"
"/assets/LEVEL_3/STORY_1/PIC1.jpg" → "/assets/LEVEL_3/STORY_1/PIC1.webp"

// Src attributes
src="/assets/school-bg.gif" → src="/assets/school-bg.webp"

// Image properties
image: "vendor1.png" → image: "vendor1.webp"
```

---

## 🔍 Manual Checks Required

After running the scripts, search for these patterns manually:

### 1. Template Literals with Variables
```javascript
// Search for: ${...}.png
// Example from trash games:
`/trash_collect_game/trash/${name}.png` → .webp`
```

**Files to check:**
- `src/routes/student/game/trash_1/+page.svelte`
- `src/routes/student/game/trash_2/+page.svelte`
- `src/routes/student/game/trash_3/+page.svelte`

### 2. Computed Filenames
```javascript
// Example:
const filename = number + ".png";  // Change to .webp
```

---

## 🐛 Troubleshooting

### Script execution blocked?
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Images not loading?
1. Check browser console for 404 errors
2. Verify WebP files exist: `ls static/trash_collect_game/`
3. Clear browser cache (Ctrl+Shift+R)
4. Check file paths match exactly (case-sensitive)

### Want to rollback?
```powershell
# Restore original images
Remove-Item -Path "static\assets" -Recurse -Force
Remove-Item -Path "static\trash_collect_game" -Recurse -Force
Copy-Item -Path "static\_original_images_backup\*" -Destination "static\" -Recurse -Force

# Revert code changes
git checkout -- src/
```

---

## ✅ Final Checklist

**Pre-Migration:**
- [ ] Review `WEBP_MIGRATION_ANALYSIS.md` for details
- [ ] Commit current changes to Git (safety)

**Migration:**
- [ ] Run `migrate-webp-files.ps1 -DryRun` (preview)
- [ ] Run `migrate-webp-files.ps1` (execute)
- [ ] Verify folder structure looks correct
- [ ] Run `update-to-webp-references.ps1 -DryRun` (preview)
- [ ] Run `update-to-webp-references.ps1` (execute)

**Testing:**
- [ ] Test trash_1 game locally
- [ ] Test trash_2 game locally
- [ ] Test trash_3 game locally
- [ ] Test Level 1/2/3 slides
- [ ] Test gift shop
- [ ] Test village backgrounds
- [ ] Check browser console (no 404s)

**Deployment:**
- [ ] Run `npm run build` (should succeed)
- [ ] Run `npm run preview` (test production)
- [ ] Commit: `git add . && git commit -m "Migrate to WebP"`
- [ ] Push: `git push origin main`
- [ ] Verify Netlify deployment succeeds
- [ ] Test live site on Netlify
- [ ] Check Network tab (confirm WebP loading)
- [ ] Delete `_original_images_backup/` (after 1 week of stability)

---

## 📈 Expected Results

### Performance Improvements:
- ✅ **18.8% smaller** total image size
- ✅ **Faster page loads** on Netlify
- ✅ **Better SEO scores** (Core Web Vitals)
- ✅ **Reduced bandwidth** usage

### File Count:
- **Before:** 502 PNG/JPG/GIF files (43.48 MB)
- **After:** 476 WebP files (35.31 MB)
- **Savings:** 8.17 MB

---

## 🎯 Ready to Start?

Just run these two commands:

```powershell
# 1. Move files
.\migrate-webp-files.ps1

# 2. Update code
.\update-to-webp-references.ps1
```

Then test and deploy! 🚀

---

## 📖 More Info

See **WEBP_MIGRATION_ANALYSIS.md** for complete technical details.

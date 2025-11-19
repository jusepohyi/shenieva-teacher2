# 🚀 WebP Conversion Quick Start

## TL;DR - Fast Track

### 1️⃣ Install Tool (One-time)
```powershell
# Install Scoop (if not installed)
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
Invoke-RestMethod -Uri https://get.scoop.sh | Invoke-Expression

# Install WebP converter
scoop install main/libwebp
```

### 2️⃣ Test First (Recommended)
```powershell
# Preview what will happen (no changes made)
.\convert-to-webp.ps1 -DryRun
```

### 3️⃣ Convert Images
```powershell
# Convert all images to WebP (keeps .original backups)
.\convert-to-webp.ps1
```

### 4️⃣ Update Code References
```powershell
# Automatically update .svelte files
.\update-image-references.ps1
```

### 5️⃣ Test Locally
```powershell
npm run dev
```

**Visit:** http://localhost:5173/student/game/trash_3

**Check:**
- ✅ Game sprites load correctly
- ✅ Backgrounds display properly
- ✅ No broken images in console

### 6️⃣ Build & Deploy
```powershell
# Build for production
npm run build

# Preview production build
npm run preview

# If all good, commit and push
git add .
git commit -m "Convert images to WebP for better performance"
git push origin main
```

---

## 📊 Expected Results

**Before:**
- Total images: ~502 files
- Total size: ~150 MB

**After:**
- Total images: ~502 files (WebP)
- Total size: ~105 MB
- **Savings: ~45 MB (30%)**

---

## 🎯 Custom Quality Settings

```powershell
# Higher quality (90%) - larger files
.\convert-to-webp.ps1 -Quality 90

# Lower quality (75%) - smaller files
.\convert-to-webp.ps1 -Quality 75

# Don't keep originals (saves disk space)
.\convert-to-webp.ps1 -KeepOriginals:$false
```

---

## 🔄 Rollback (If Needed)

```powershell
# Restore original PNG/JPG files
Get-ChildItem -Recurse -Filter *.original | ForEach-Object {
    $original = $_.FullName
    $restored = $original -replace '\.original$', ''
    Copy-Item $original $restored -Force
}

# Revert code changes
git checkout -- src/
```

---

## ⚠️ Important Notes

### GIF Files (Animated)
The script **skips GIF files** by default because:
- `school-bg.gif`
- `readville.gif`
- `ribbon-gif.gif`

**To convert animated GIFs:**
```powershell
# Requires gif2webp (not included)
gif2webp -q 85 static/assets/school-bg.gif -o static/assets/school-bg.webp
```

### Manual Code Checks
After running `update-image-references.ps1`, verify these patterns:

**Dynamic paths with variables:**
```javascript
// BEFORE
const name = "trash";
const img = `/trash_collect_game/${name}.png`;

// AFTER (manual fix needed)
const img = `/trash_collect_game/${name}.webp`;
```

**Template literals:**
```javascript
// Check these files manually:
// • src/routes/student/game/trash_*/+page.svelte (lines with .map())
// • Any computed filename logic
```

---

## 🐛 Troubleshooting

### "cwebp is not recognized"
```powershell
# Check if installed
cwebp -version

# If not found, install via Scoop
scoop install main/libwebp
```

### Script execution blocked
```powershell
# Allow script execution (one-time)
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Images not loading after conversion
1. Check browser console for errors
2. Verify WebP files exist in `build/` folder
3. Clear browser cache (Ctrl+Shift+R)
4. Check file paths are correct (case-sensitive)

---

## 📁 Files Included

| File | Purpose |
|------|---------|
| `convert-to-webp.ps1` | Converts images to WebP |
| `update-image-references.ps1` | Updates code references |
| `WEBP_CONVERSION_GUIDE.md` | Full documentation |
| `WEBP_QUICK_START.md` | This file (quick reference) |

---

## ✅ Checklist

- [ ] Install libwebp (`scoop install main/libwebp`)
- [ ] Run dry run test (`.\convert-to-webp.ps1 -DryRun`)
- [ ] Convert images (`.\convert-to-webp.ps1`)
- [ ] Update code (`.\update-image-references.ps1`)
- [ ] Test locally (`npm run dev`)
- [ ] Build (`npm run build`)
- [ ] Preview (`npm run preview`)
- [ ] Commit changes
- [ ] Push to GitHub
- [ ] Verify Netlify deployment

---

## 🆘 Need Help?

See full guide: `WEBP_CONVERSION_GUIDE.md`

**Common Issues:**
- Tool not found → Install libwebp
- Quality too low → Use `-Quality 90`
- Want to keep originals → Already default behavior
- Need to rollback → See rollback section above

---

## 🎉 Done!

After conversion, your Netlify site will:
- ✅ Load 30% faster
- ✅ Use less bandwidth
- ✅ Improve SEO/performance scores
- ✅ Provide better mobile experience

**Ready to convert? Just run:**
```powershell
.\convert-to-webp.ps1
```

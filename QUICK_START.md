# 🚀 Quick Start: Deploy to Netlify

## TL;DR - 5 Minute Deploy

```bash
# 1. Install dependencies (if not done)
npm install

# 2. Build the site
npm run build

# 3. Test it locally
npm run preview
# Open http://localhost:4173 and check if images load

# 4. Deploy to Netlify
# Drag the 'build' folder to https://app.netlify.com/drop
```

---

## ✅ What's Been Fixed

- Fixed asset paths for trash collection games
- Added Netlify configuration (`netlify.toml`)
- Improved Vite build settings
- Created deployment documentation

---

## ⚠️ What You Need to Know

### ✅ Will Work on Netlify (Free)
- All static pages
- Images and animations
- Audio playback
- Village navigation
- Client-side features

### ❌ Won't Work (PHP Backend Required)
- Student/Teacher login
- Quiz submissions
- Database operations
- Attendance tracking

### 💡 Solution
You need **TWO hosts**:
1. **Netlify** - Free (frontend/static files)
2. **Hostinger** or **InfinityFree** - $0-10/month (PHP + MySQL)

---

## 📁 Files to Deploy

### To Netlify
```
build/           ← Entire folder (drag & drop)
```

### To Hostinger (for PHP backend)
```
src/lib/api/     ← Upload to public_html/api/
```

---

## 🔗 Connect Frontend to Backend

After deploying both:

1. **Open:** `src/lib/api_base.ts` (or create it)

2. **Add:**
```typescript
const API_BASE_URL = import.meta.env.PROD 
  ? 'https://YOUR-HOSTINGER-DOMAIN.com/api'
  : 'http://localhost/shenieva-teacher/src/lib/api';

export function apiUrl(endpoint: string): string {
  return `${API_BASE_URL}/${endpoint}`;
}
```

3. **Update** all PHP files with CORS:
```php
// In src/lib/api/cors.php
$allowed_origins = [
    'https://your-site.netlify.app',  // Add your Netlify URL
    'http://localhost:5173'
];
```

4. **Rebuild & redeploy** to Netlify

---

## 📖 Full Documentation

- **`NETLIFY_DEPLOYMENT.md`** - Complete step-by-step guide
- **`ASSET_PATH_FIX_SUMMARY.md`** - Technical details of fixes
- **`netlify.toml`** - Netlify configuration

---

## 🐛 Troubleshooting

**Problem:** Images don't load

**Check:**
```
Browser DevTools → Network tab
Look for 404 errors
```

**Fix:** Paths should be `/assets/image.png` NOT `/assets/assets/image.png`

---

**Problem:** "Failed to fetch" errors

**Reason:** PHP backend not connected

**Fix:** Deploy PHP to Hostinger and update `API_BASE_URL`

---

## ✨ That's It!

Your frontend is ready for Netlify. For the full experience with login/quizzes, follow the complete guide in `NETLIFY_DEPLOYMENT.md`.

# 🚀 Netlify Deployment Guide for Shenieva Reads

## ⚠️ Critical: PHP Backend Won't Work on Netlify

**Netlify is a static hosting service** and cannot run PHP code. Your backend API (`src/lib/api/*.php`) will NOT work.

### Options:

#### Option 1: Separate Backend (Recommended)
- Keep Netlify for frontend (free)
- Host PHP backend separately:
  - **Hostinger** (your current plan)
  - **InfinityFree** (free PHP hosting)
  - **Vercel** (with PHP support via functions)
  
#### Option 2: Migrate to Serverless
- Convert PHP endpoints to **Netlify Functions** (JavaScript/TypeScript)
- Rewrite all 142 PHP files to Node.js
- Use **Supabase** or **PlanetScale** for MySQL database

#### Option 3: Keep Everything on Hostinger
- Deploy both frontend AND backend to Hostinger
- Cheaper and simpler than splitting

---

## 🔧 Asset Path Issues Fixed

### Problem
Files in `/static/` folder are accessed with `/assets/` prefix, which doesn't work on Netlify.

### Solution Applied

**Before:**
```svelte
<img src="/assets/readville.jpg" />  ❌
loadImage('/assets/trash_collect_game/boy.png')  ❌
```

**After:**
```svelte
<img src="/assets/readville.jpg" />  ✅ (already in static/assets/)
loadImage('/trash_collect_game/boy.png')  ✅ (in static/trash_collect_game/)
```

### Files Modified
- ✅ `netlify.toml` (created - Netlify config)
- ✅ `vite.config.js` (improved asset handling)
- ✅ `src/routes/student/game/trash_3/+page.svelte` (fixed paths)
- 📝 `fix-asset-paths.ps1` (automated fix script)

---

## 📦 Build Steps

### 1. Fix All Asset Paths (One-Time)

Run the PowerShell script to fix remaining paths:

```powershell
.\fix-asset-paths.ps1
```

Or manually fix paths in these files:
- `src/routes/student/game/trash_2/+page.svelte`
- `src/routes/student/game/trash_1/+page.svelte` (if exists)
- Any other files with `/assets/trash_collect_game/`

### 2. Update Package.json

Ensure build script is correct:

```json
{
  "scripts": {
    "dev": "vite dev",
    "build": "vite build",
    "preview": "vite preview"
  }
}
```

### 3. Build Locally

```bash
npm install
npm run build
```

This creates a `build/` folder with your static site.

### 4. Test Build Locally

```bash
npm run preview
```

Open `http://localhost:4173` and test:
- ✅ All images load
- ✅ Audio plays
- ✅ Navigation works
- ❌ PHP API calls will fail (expected)

---

## 🌐 Netlify Deployment

### Method 1: Drag & Drop (Easiest)

1. Go to [Netlify](https://app.netlify.com/)
2. Drag the `build/` folder to "Sites"
3. Done! Your site is live

### Method 2: GitHub Integration (Recommended)

1. Push code to GitHub:
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin https://github.com/nalsemper/shenieva-teacher.git
   git push -u origin main
   ```

2. Connect to Netlify:
   - Go to Netlify Dashboard
   - Click "Add new site" → "Import an existing project"
   - Choose GitHub → Select repo
   
3. Build settings (auto-detected from `netlify.toml`):
   - **Build command:** `npm run build`
   - **Publish directory:** `build`
   - **Click "Deploy"**

### Method 3: Netlify CLI

```bash
# Install Netlify CLI
npm install -g netlify-cli

# Login
netlify login

# Deploy
netlify deploy --prod
```

---

## 🔗 Connect Frontend to PHP Backend

Since PHP won't work on Netlify, you need to call your Hostinger-hosted API:

### Update API Base URL

Create or update `src/lib/api_base.ts`:

```typescript
// For production (Netlify frontend → Hostinger backend)
const API_BASE_URL = import.meta.env.PROD 
  ? 'https://your-hostinger-domain.com/api'
  : 'http://localhost/shenieva-teacher/src/lib/api';

export function apiUrl(endpoint: string): string {
  return `${API_BASE_URL}/${endpoint}`;
}
```

### Upload PHP to Hostinger

1. **Via FTP/File Manager:**
   - Upload `src/lib/api/` folder to Hostinger
   - Place in `public_html/api/` or similar

2. **Enable CORS:**
   
   Update `src/lib/api/cors.php`:
   
   ```php
   <?php
   // Allow your Netlify domain
   $allowed_origins = [
       'https://your-netlify-site.netlify.app',
       'https://your-custom-domain.com',
       'http://localhost:5173',  // For dev
   ];

   $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
   if (in_array($origin, $allowed_origins)) {
       header("Access-Control-Allow-Origin: $origin");
   }
   
   header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
   header('Access-Control-Allow-Headers: Content-Type, Authorization');
   header('Access-Control-Allow-Credentials: true');
   
   if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
       http_response_code(200);
       exit;
   }
   ?>
   ```

---

## 🧪 Testing Checklist

### Local Build Test
- [ ] `npm run build` succeeds
- [ ] `npm run preview` shows site correctly
- [ ] All images load
- [ ] All audio files play
- [ ] Village scenes render
- [ ] Trash collection game loads

### Netlify Test (After Deploy)
- [ ] Site loads at Netlify URL
- [ ] Images load (check browser console for 404s)
- [ ] Background music works
- [ ] Student login works (if API is connected)
- [ ] Quiz pages load
- [ ] Game canvases render

---

## 🐛 Common Issues

### 1. "404 Not Found" for Assets

**Symptom:** Images/audio don't load

**Fix:** Check paths in browser DevTools Network tab
- Should be: `/assets/readville.jpg`
- NOT: `/assets/assets/readville.jpg`

### 2. "Failed to fetch" API Errors

**Symptom:** Login/quiz submission fails

**Fix:** 
1. Ensure PHP backend is live on Hostinger
2. Check CORS headers
3. Verify API_BASE_URL in `api_base.ts`

### 3. Blank Page After Build

**Symptom:** Netlify shows blank page

**Fix:** Check `netlify.toml` has:
```toml
[[redirects]]
  from = "/*"
  to = "/index.html"
  status = 200
```

### 4. Build Fails on Netlify

**Symptom:** "Build failed" error

**Fix:**
1. Check Node version (Netlify uses Node 18 by default)
2. Add to `netlify.toml`:
   ```toml
   [build.environment]
     NODE_VERSION = "18"
   ```

---

## 📊 Deployment Architecture

### Current (Local)
```
Browser → Vite Dev Server (localhost:5173)
           ↓
        XAMPP (localhost/shenieva-teacher/src/lib/api)
           ↓
        MySQL (localhost:3306)
```

### Recommended (Production)
```
Browser → Netlify (Static Frontend)
           ↓ (API calls)
        Hostinger (PHP Backend + MySQL)
```

---

## 💰 Cost Comparison

| Service | Frontend | Backend | Database | Total/Month |
|---------|----------|---------|----------|-------------|
| **Option 1** | Netlify (Free) | Hostinger | Hostinger | ~$3-10 |
| **Option 2** | Netlify (Free) | Netlify Functions | Supabase (Free) | $0-25 |
| **Option 3** | Hostinger | Hostinger | Hostinger | ~$3-10 |

---

## 🚀 Quick Deploy Checklist

1. ✅ Run `.\fix-asset-paths.ps1`
2. ✅ Test with `npm run build && npm run preview`
3. ✅ Push to GitHub
4. ✅ Connect GitHub repo to Netlify
5. ✅ Deploy!
6. ✅ Upload PHP files to Hostinger
7. ✅ Update CORS and API_BASE_URL
8. ✅ Test login/quiz functionality

---

## 📞 Support

If you encounter issues:

1. **Check Netlify deploy logs** - Shows build errors
2. **Check browser console** - Shows runtime errors
3. **Check Network tab** - Shows failed API calls
4. **Verify file paths** - Use browser DevTools

---

## 🎯 Next Steps

Based on your choice:

### If Using Netlify (Frontend Only):
1. Run the asset path fix script
2. Deploy to Netlify
3. Set up Hostinger for PHP backend
4. Connect the two

### If Using Hostinger (Full Stack):
1. Build locally: `npm run build`
2. Upload `build/` folder to Hostinger `public_html/`
3. Upload `src/lib/api/` to `public_html/api/`
4. Done!

---

Good luck with your deployment! 🎉

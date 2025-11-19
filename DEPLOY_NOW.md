# ✅ READY TO DEPLOY - Follow These Steps

Since you're using **GitHub → Netlify**, here's your exact deployment process:

---

## 📝 Step 1: Configure Your API URL (1 minute)

Edit `.env.production` and replace with **your actual Hostinger domain**:

```bash
VITE_API_BASE=https://YOUR-DOMAIN.com/src/lib/api
```

**Example:**
```bash
VITE_API_BASE=https://shenieviareads.com/src/lib/api
```

---

## 🚀 Step 2: Push to GitHub (1 minute)

```bash
git add .
git commit -m "Fix asset paths and configure production API"
git push origin main
```

---

## 🌐 Step 3: Connect to Netlify (3 minutes)

### First Time Setup:

1. **Go to:** https://app.netlify.com
2. **Click:** "Add new site" → "Import an existing project"
3. **Choose:** "Deploy with GitHub"
4. **Authorize:** Netlify to access your GitHub
5. **Select repo:** `nalsemper/shenieva-teacher`
6. **Build settings** (auto-detected from `netlify.toml`):
   - Build command: `npm run build` ✅
   - Publish directory: `build` ✅
   - These are already configured in your `netlify.toml`!
7. **Click:** "Deploy site"

### Watch the build:
- Netlify will show build logs
- Wait 3-5 minutes for first deploy
- You'll get a URL like: `https://random-name-123.netlify.app`

---

## 🔐 Step 4: Update CORS on Hostinger (2 minutes)

1. **Copy your Netlify URL** (from Step 3)
2. **Edit on Hostinger:** `src/lib/api/cors.php`
3. **Add your Netlify URL:**

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

$allowed_origins = [
    'https://random-name-123.netlify.app',  // ← Your Netlify URL here
    'http://localhost:5173',
    'http://localhost:5174',
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>
```

4. **Save the file** on Hostinger

---

## ✅ Step 5: Test Your Deployment (2 minutes)

Open your Netlify URL and test:

### ✅ Frontend Tests:
- [ ] Homepage loads
- [ ] Images display
- [ ] Background music plays
- [ ] No 404 errors (check browser console F12)

### ✅ Backend Tests:
- [ ] Student login works
- [ ] Quiz submission works
- [ ] Teacher dashboard loads
- [ ] No CORS errors in console

---

## 🔄 Future Updates (Automatic!)

Every time you push to GitHub:

```bash
# Make changes to your code
git add .
git commit -m "Your update message"
git push origin main
```

**Netlify automatically:**
1. Detects the push
2. Builds your site
3. Deploys if successful
4. Sends you notification

**No manual deployment needed!** 🎉

---

## 🐛 If Something Goes Wrong

### Build Fails on Netlify

1. **Check Netlify build logs**
2. **Common issue:** Dependency conflicts
3. **Fix:** Add to `netlify.toml`:

```toml
[build.environment]
  NPM_FLAGS = "--legacy-peer-deps"
```

4. **Push update to trigger rebuild**

### Login Doesn't Work

1. **Open browser console (F12)**
2. **Look for errors:**
   - ❌ "blocked by CORS" → Check Step 4 (CORS)
   - ❌ "Failed to fetch" → Check API URL in `.env.production`
   - ❌ "404 Not Found" → Check Hostinger PHP files exist

### Images Don't Load

1. **Check browser console**
2. **Look for 404 errors**
3. **Paths should be:** `/assets/` and `/trash_collect_game/`
4. **Already fixed!** ✅

---

## 💡 Optional: Use Netlify Environment Variables

**For better security**, instead of putting API URL in `.env.production`:

1. **Netlify Dashboard** → Site settings → Environment variables
2. **Add variable:**
   - Key: `VITE_API_BASE`
   - Value: `https://your-hostinger-domain.com/src/lib/api`
3. **Remove `.env.production` from repo** (add to `.gitignore`)
4. **Redeploy**

This keeps your API URL out of public GitHub repo.

---

## 📊 Your Deployment Architecture

```
┌──────────────┐
│   You Code   │
└──────┬───────┘
       │
       │ git push
       ▼
┌──────────────┐
│   GitHub     │ ← Your code repository
└──────┬───────┘
       │
       │ webhook (automatic)
       ▼
┌──────────────┐
│   Netlify    │ ← Builds & hosts frontend
│   (Free)     │
└──────┬───────┘
       │
       │ fetch() API calls (HTTPS)
       ▼
┌──────────────┐
│  Hostinger   │ ← PHP Backend + MySQL
│  ($3-10/mo)  │
└──────────────┘
```

---

## 🎉 You're All Set!

Your deployment workflow:
1. ✅ Code on local machine
2. ✅ Push to GitHub
3. ✅ Netlify auto-deploys
4. ✅ Connected to Hostinger backend

**Total cost:** ~$3-10/month (just Hostinger)
**Deployment time:** Automatic, ~3-5 minutes per push

Happy deploying! 🚀

# 🚀 GitHub → Netlify Automatic Deployment

## ✅ Your Setup
- Code: On GitHub ✅
- Backend: On Hostinger ✅
- Frontend Deploy: GitHub → Netlify (automatic)

## 🎯 Quick Setup (5 minutes)

### Step 1: Configure API URL

Edit `.env.production` with your Hostinger domain:

```bash
VITE_API_BASE=https://YOUR-HOSTINGER-DOMAIN.com/src/lib/api
```

**Examples:**
- `VITE_API_BASE=https://shenieviareads.com/src/lib/api`
- `VITE_API_BASE=https://yourdomain.com/api`

### Step 2: Commit & Push Changes

```bash
git add .
git commit -m "Configure production API URL and fix asset paths"
git push origin main
```

### Step 3: Connect GitHub to Netlify

1. Go to [Netlify Dashboard](https://app.netlify.com)
2. Click **"Add new site"** → **"Import an existing project"**
3. Choose **"Deploy with GitHub"**
4. Select your repository: `nalsemper/shenieva-teacher`
5. Configure build settings (Netlify auto-detects from `netlify.toml`):
   - **Build command:** `npm run build`
   - **Publish directory:** `build`
   - **Base directory:** (leave empty)
6. Click **"Deploy site"**

### Step 4: Add Environment Variables in Netlify

In Netlify dashboard → Site settings → Environment variables:

Add:
- **Key:** `VITE_API_BASE`
- **Value:** `https://your-hostinger-domain.com/src/lib/api`

(This overrides the .env.production file)

### Step 5: Update CORS on Hostinger

After deployment, get your Netlify URL (e.g., `https://amazing-app-123.netlify.app`)

Edit `src/lib/api/cors.php` on Hostinger:

```php
<?php
$allowed_origins = [
    'https://amazing-app-123.netlify.app',  // Your actual Netlify URL
    'https://your-custom-domain.com',        // If you add a custom domain
    'http://localhost:5173',                 // For local dev
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

---

## 🔄 How It Works

```
You push to GitHub
    ↓
GitHub triggers webhook
    ↓
Netlify automatically:
  1. Pulls latest code
  2. Runs: npm install --legacy-peer-deps
  3. Runs: npm run build
  4. Deploys build/ folder
    ↓
Your site is live! 🎉
```

---

## ⚙️ Netlify Build Settings

Your `netlify.toml` already configures everything:

```toml
[build]
  command = "npm run build"
  publish = "build"

[[redirects]]
  from = "/*"
  to = "/index.html"
  status = 200
```

**Netlify will read this automatically!**

---

## 🎨 Optional: Custom Domain

### If you have a custom domain:

1. In Netlify: **Domain settings** → **Add custom domain**
2. Add DNS records (Netlify provides instructions)
3. Wait for SSL certificate (automatic, ~5 minutes)
4. Update CORS on Hostinger with your custom domain

---

## 📝 .gitignore Check

Make sure these are in `.gitignore`:

```gitignore
.env
.env.local
.env.production.local
node_modules/
build/
.svelte-kit/
```

**Don't commit:**
- `.env.production` with real credentials (use Netlify env vars instead)
- `node_modules/`
- `build/` folder

---

## 🔧 Netlify Build Configuration

### Option 1: Use netlify.toml (Already Done ✅)

Your `netlify.toml` is already configured correctly!

### Option 2: Override in Netlify Dashboard

If you need to override:
1. Site settings → Build & deploy → Build settings
2. Build command: `npm run build`
3. Publish directory: `build`
4. Node version: 18 (or leave default)

---

## 🚨 Important: Environment Variables

### For Security, Use Netlify Env Vars (Recommended)

**Don't hardcode** API URL in `.env.production` if it contains sensitive info.

Instead:
1. Netlify Dashboard → Site settings → Environment variables
2. Add: `VITE_API_BASE` = `https://your-hostinger.com/api`
3. Remove `.env.production` from Git (add to `.gitignore`)

### Why?
- Env vars in Netlify are **encrypted**
- Not visible in public GitHub repo
- Can be different per branch (production/staging)

---

## 🎯 Full Deployment Checklist

### Before First Deploy:
- [ ] `.env.production` configured (or Netlify env var set)
- [ ] All changes committed to GitHub
- [ ] `netlify.toml` exists in repo root
- [ ] `.gitignore` excludes sensitive files

### Netlify Setup:
- [ ] Connected GitHub repo to Netlify
- [ ] Build settings configured
- [ ] Environment variables set (if needed)
- [ ] Deployment successful

### After Deploy:
- [ ] Got Netlify URL
- [ ] Updated CORS on Hostinger
- [ ] Tested login from Netlify site
- [ ] Tested quiz submission
- [ ] No errors in browser console

---

## 📊 Deployment Logs

To check build status:
1. Netlify Dashboard → Deploys
2. Click latest deploy
3. View build log

**Common issues:**
- Build failed: Check for TypeScript/lint errors
- Deploy succeeded but site broken: Check environment variables
- API calls fail: Check CORS configuration

---

## 🔄 Future Updates

Every time you push to GitHub:
```bash
git add .
git commit -m "Your changes"
git push origin main
```

Netlify will **automatically**:
1. Detect the push
2. Build your site
3. Deploy if successful
4. Rollback if failed

---

## 🌿 Branch Deployments (Optional)

You can deploy different branches:
- `main` → Production site
- `develop` → Preview site
- `feature/*` → Branch previews

In Netlify:
1. Site settings → Build & deploy → Branches
2. Enable branch deploys
3. Each branch gets its own URL

---

## 🐛 Troubleshooting

### Build Fails on Netlify

**Check:**
1. Netlify build log for errors
2. Does it build locally? (`npm run build`)
3. Are all dependencies in `package.json`?
4. Is Node version compatible?

**Fix:**
Add to `netlify.toml`:
```toml
[build.environment]
  NODE_VERSION = "18"
  NPM_FLAGS = "--legacy-peer-deps"
```

### Site Deploys But Shows Blank Page

**Check:**
1. Browser console for errors
2. Netlify redirects are configured
3. `[[redirects]]` section in `netlify.toml`

### API Calls Fail

**Check:**
1. CORS enabled on Hostinger
2. Netlify URL in allowed origins
3. API URL in environment variables
4. HTTPS (not HTTP)

---

## 💡 Pro Tips

1. **Deploy Preview:** Every PR gets a preview URL
2. **Rollback:** Click previous deploy → "Publish deploy"
3. **Split Testing:** Test features with % of traffic
4. **Analytics:** Enable Netlify Analytics ($9/month)
5. **Forms:** Netlify can handle form submissions

---

## 🎉 Summary

With GitHub → Netlify:
- ✅ **Push to GitHub** → Auto-deploy
- ✅ **Free hosting** for frontend
- ✅ **Automatic SSL** certificate
- ✅ **CDN** worldwide
- ✅ **Rollback** to any version
- ✅ **Preview** for pull requests

Just push your code and Netlify does the rest! 🚀

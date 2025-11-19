# 🚀 QUICK DEPLOY CARD - Netlify + Hostinger

## ✅ Your Setup
- Frontend: Ready for Netlify
- Backend: Already on Hostinger ✅
- Database: Already on Hostinger ✅

## 🎯 3 Steps to Deploy

### Step 1: Configure API URL (2 minutes)

Edit `.env.production` and replace with YOUR Hostinger domain:

```bash
VITE_API_BASE=https://YOUR-DOMAIN.com/src/lib/api
```

Examples:
- `https://shenieviareads.com/src/lib/api`
- `https://yourdomain.hostinger.com/api`

### Step 2: Update CORS on Hostinger (5 minutes)

Edit `src/lib/api/cors.php` on Hostinger:

```php
$allowed_origins = [
    'https://YOUR-APP.netlify.app',  // Add after Step 3
    'http://localhost:5173',
];
```

**Initially:** Add `'*'` for testing, then replace with actual Netlify URL

### Step 3: Build & Deploy (3 minutes)

```bash
# Build
npm run build

# Deploy: Drag 'build' folder to:
https://app.netlify.com/drop
```

## 🧪 Test Checklist

Visit your Netlify URL and test:
- [ ] Login works
- [ ] Quiz submission works
- [ ] No CORS errors (F12 console)

## 🐛 If Login Fails

1. **Check API URL**: Open browser DevTools → Network tab → See what URL is being called
2. **Check CORS**: Look for "blocked by CORS" error in console
3. **Test PHP directly**: Visit `https://yourdomain.com/src/lib/api/cors_test.php`

## 📞 Need Help?

Read detailed guides:
- `HOSTINGER_NETLIFY_SETUP.md` - Complete walkthrough
- `ASSET_PATH_FIX_SUMMARY.md` - Technical details

---

**That's it!** Your app will be live on Netlify (free) connected to Hostinger (backend). 🎉

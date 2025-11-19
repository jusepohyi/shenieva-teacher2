# 🚀 Deploying to Netlify with Hostinger Backend

## Your Current Setup

✅ **PHP Backend**: Already on Hostinger
✅ **MySQL Database**: Already on Hostinger
🔧 **Frontend**: Needs to be deployed to Netlify

This guide will connect your Netlify frontend to your existing Hostinger backend.

---

## 📋 Prerequisites

Before you start, you need:

1. **Your Hostinger domain/URL** where PHP files are hosted
2. **Access to Hostinger File Manager** or FTP
3. **Your PHP files path** on Hostinger (e.g., `public_html/src/lib/api/`)

---

## Step 1: Configure API URL for Production

### 1.1 Find Your Hostinger API Path

First, determine where your PHP files are located on Hostinger:

Common locations:
- `public_html/src/lib/api/` (if you uploaded the whole structure)
- `public_html/api/` (if you moved API folder to root)
- `public_html/shenieva-teacher/src/lib/api/`

Test by visiting: `https://YOUR-DOMAIN.com/PATH-TO-API/cors_test.php`

Example:
- `https://shenieviareads.com/src/lib/api/cors_test.php`
- `https://yourdomain.com/api/cors_test.php`

### 1.2 Update .env.production

Edit `.env.production` in your project root and replace with your actual domain:

```bash
# YOUR ACTUAL HOSTINGER DOMAIN
VITE_API_BASE=https://yourdomain.com/src/lib/api
```

**Real Examples:**
```bash
# If PHP is in public_html/src/lib/api/
VITE_API_BASE=https://shenieviareads.com/src/lib/api

# If PHP is in public_html/api/
VITE_API_BASE=https://shenieviareads.com/api

# If using subdomain
VITE_API_BASE=https://app.yourdomain.com/api
```

---

## Step 2: Update CORS on Hostinger

Your PHP backend needs to accept requests from your Netlify domain.

### 2.1 Edit cors.php on Hostinger

Using Hostinger File Manager or FTP, edit `src/lib/api/cors.php`:

```php
<?php
// CORS Configuration for Netlify + Hostinger
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Add your Netlify domains here
$allowed_origins = [
    // Your Netlify URL (you'll add this after Step 3)
    'https://your-app-name.netlify.app',
    
    // Your custom domain (if you have one)
    'https://yourdomain.com',
    
    // Keep these for local development
    'http://localhost:5173',
    'http://localhost:5174',
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    // During initial setup, you can temporarily allow all origins
    // Remove this after adding your Netlify URL above
    header("Access-Control-Allow-Origin: *");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>
```

### 2.2 Verify CORS is Working

Create a test file on Hostinger: `src/lib/api/cors_test.php`

```php
<?php
require_once __DIR__ . '/cors.php';
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'CORS is working!',
    'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'No origin header',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
```

Test it: `https://yourdomain.com/src/lib/api/cors_test.php`

You should see:
```json
{
  "success": true,
  "message": "CORS is working!",
  "origin": "No origin header",
  "timestamp": "2025-11-19 10:00:00"
}
```

---

## Step 3: Build and Deploy to Netlify

### 3.1 Build Your Frontend

In your project folder:

```bash
# Make sure .env.production has your Hostinger domain
npm run build
```

This creates a `build/` folder with your static site.

### 3.2 Deploy to Netlify (Drag & Drop)

1. Go to **https://app.netlify.com/drop**
2. Drag the `build/` folder onto the page
3. Wait for deployment (usually 1-2 minutes)
4. Get your Netlify URL (e.g., `https://silly-name-123.netlify.app`)

### 3.3 Update CORS with Your Netlify URL

Now that you have your Netlify URL, go back to Hostinger and update `cors.php`:

```php
$allowed_origins = [
    'https://silly-name-123.netlify.app',  // Your actual Netlify URL
    'https://yourdomain.com',
    'http://localhost:5173',
    'http://localhost:5174',
];
```

**Important:** Remove the `header("Access-Control-Allow-Origin: *");` line for security!

---

## Step 4: Test Your Deployment

### 4.1 Open Your Netlify Site

Visit your Netlify URL and test:

1. **Homepage loads** ✅
2. **Images display** ✅
3. **Background music plays** ✅

### 4.2 Test Student Login

1. Go to student login
2. Enter credentials
3. Try to log in

**If it works** ✅ - Great! Backend is connected!

**If it fails** ❌ - Check browser console (F12):
- Look for CORS errors
- Look for network errors
- Verify API URL is correct

### 4.3 Test Quiz Submission

1. Complete a quiz
2. Submit answers
3. Check if ribbons are saved

---

## 🐛 Troubleshooting Common Issues

### Issue 1: "Failed to fetch" or Network Error

**Symptoms:**
- Login button doesn't work
- Console shows: `Failed to fetch`
- Console shows: `ERR_NAME_NOT_RESOLVED`

**Solutions:**

1. **Check .env.production:**
   ```bash
   # Make sure this matches your Hostinger domain
   VITE_API_BASE=https://yourdomain.com/src/lib/api
   ```

2. **Rebuild after changing .env.production:**
   ```bash
   npm run build
   # Redeploy the new build/ folder to Netlify
   ```

3. **Verify PHP files are accessible:**
   - Open: `https://yourdomain.com/src/lib/api/cors_test.php`
   - Should return JSON, not 404

---

### Issue 2: CORS Policy Error

**Symptoms:**
- Console shows: `blocked by CORS policy`
- Console shows: `Access-Control-Allow-Origin`

**Solutions:**

1. **Verify cors.php is included** in all PHP endpoints:
   ```php
   <?php
   require_once __DIR__ . '/cors.php';  // Must be at the top!
   ```

2. **Check allowed origins** in `cors.php`:
   ```php
   $allowed_origins = [
       'https://your-actual-netlify-url.netlify.app',  // ← Check this!
   ];
   ```

3. **Test CORS directly:**
   ```bash
   curl -H "Origin: https://your-netlify-url.netlify.app" \
        -H "Access-Control-Request-Method: POST" \
        -H "Access-Control-Request-Headers: Content-Type" \
        -X OPTIONS \
        https://yourdomain.com/src/lib/api/login_student.php
   ```

---

### Issue 3: Mixed Content Error

**Symptoms:**
- Console shows: `Mixed Content`
- Console shows: `http://` request blocked on `https://` page

**Solution:**

Make sure API URL uses `https://`:
```bash
# ✅ CORRECT
VITE_API_BASE=https://yourdomain.com/api

# ❌ WRONG
VITE_API_BASE=http://yourdomain.com/api
```

---

### Issue 4: Database Connection Failed

**Symptoms:**
- Login returns: `Database connection failed`
- Console shows: 500 error

**Solutions:**

1. **Check Hostinger database credentials** in `conn.php`:
   ```php
   $servername = 'localhost';  // Usually 'localhost' on Hostinger
   $username   = 'u207191294_shenievia';  // Your DB username
   $password   = 'YOUR_DB_PASSWORD';      // Your DB password
   $database   = 'u207191294_shenieva_db'; // Your DB name
   ```

2. **Verify database exists** in Hostinger cPanel → phpMyAdmin

3. **Check error logs** in Hostinger cPanel → Error Logs

---

## 📊 Architecture Diagram

```
┌─────────────────┐
│   Browser       │
│                 │
└────────┬────────┘
         │
         │ (HTTPS)
         │
         ▼
┌─────────────────┐
│   Netlify       │  ← Static Files (HTML, CSS, JS, Images)
│   (Frontend)    │
└────────┬────────┘
         │
         │ fetch() API calls
         │ (HTTPS)
         │
         ▼
┌─────────────────┐
│   Hostinger     │  ← PHP Backend + MySQL
│   (Backend)     │
│                 │
│  ┌──────────┐   │
│  │ PHP API  │   │
│  └────┬─────┘   │
│       │         │
│       ▼         │
│  ┌──────────┐   │
│  │  MySQL   │   │
│  │ Database │   │
│  └──────────┘   │
└─────────────────┘
```

---

## 🎯 Final Checklist

### Before Deploying:
- [ ] Updated `.env.production` with Hostinger domain
- [ ] Ran `npm run build` successfully
- [ ] Tested build locally with `npm run preview`

### On Hostinger:
- [ ] PHP files are uploaded
- [ ] Database is imported
- [ ] Database credentials in `conn.php` are correct
- [ ] `cors.php` exists in `src/lib/api/`
- [ ] Can access: `https://yourdomain.com/src/lib/api/cors_test.php`

### On Netlify:
- [ ] Deployed `build/` folder
- [ ] Got Netlify URL
- [ ] Added Netlify URL to `cors.php` on Hostinger
- [ ] Homepage loads without errors
- [ ] Student login works
- [ ] Quiz submission works
- [ ] Teacher dashboard works

---

## 🚀 Deployment Commands Summary

```bash
# 1. Configure API URL
# Edit .env.production with your Hostinger domain

# 2. Build for production
npm run build

# 3. Deploy to Netlify
# Drag build/ folder to https://app.netlify.com/drop

# 4. Update CORS on Hostinger
# Add your Netlify URL to cors.php

# 5. Test everything!
```

---

## 📱 Custom Domain (Optional)

If you want to use your own domain instead of `xyz.netlify.app`:

1. In Netlify dashboard → Domain settings
2. Add custom domain
3. Update DNS records (Netlify will guide you)
4. Update CORS on Hostinger with new domain
5. Rebuild if using custom domain in config

---

## 💡 Pro Tips

1. **Test CORS first** before deploying to Netlify
2. **Use browser DevTools Network tab** to debug API calls
3. **Check Hostinger error logs** for PHP errors
4. **Keep localhost in CORS** for local development
5. **Use HTTPS everywhere** - avoid mixed content errors

---

## 🎉 Success!

Once everything is connected:
- Students can log in from anywhere
- Quizzes are saved to your Hostinger database
- Teachers can view analytics
- System is fully functional with free hosting (Netlify) + cheap backend (Hostinger)

**Cost:** ~$3-10/month (just Hostinger)

Good luck! 🚀

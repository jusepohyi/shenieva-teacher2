# 🔧 Fix CORS Error - Upload to Hostinger

## Problem
Your Hostinger backend is blocking requests from localhost because the `cors.php` file doesn't include `http://localhost:5173`.

## Solution
Upload the updated `cors.php` file to Hostinger.

---

## Quick Fix Instructions

### Method 1: Upload via Hostinger File Manager

1. **Login to Hostinger**
   - Go to: https://hpanel.hostinger.com

2. **Open File Manager**
   - Click "File Manager" in your hosting control panel

3. **Navigate to API Folder**
   - Go to: `public_html/src/lib/api/`
   - (Or wherever you uploaded your PHP files)

4. **Upload/Replace cors.php**
   - Click "Upload"
   - Select file: `c:\xampp\htdocs\shenieva-teacher\src\lib\api\cors.php`
   - Confirm replace if asked

5. **Test Login**
   - Go back to http://localhost:5174/login
   - Try logging in again

---

### Method 2: Quick Edit Online (Faster)

1. **Login to Hostinger File Manager**

2. **Navigate to:** `public_html/src/lib/api/cors.php`

3. **Right-click → Edit**

4. **Find line 17** and change it to:
   ```php
   $allowed = [
       'http://localhost:5173',
       'http://localhost:5174',
       'https://darkred-dinosaur-537713.hostingersite.com',
       'https://shenieviareads.netlify.app',
       'https://shenieviareads.fun',
   ];
   ```

5. **Save** (Ctrl+S or click Save)

6. **Test login** at http://localhost:5174/login

---

### Method 3: Temporary Debug Mode (Quick Test)

If you want to test first without uploading:

1. **Edit `login_student.php` on Hostinger**
2. **Add this at the very top** (line 2, right after `<?php`):
   ```php
   putenv('ALLOW_CORS_DEBUG=1');
   ```

3. **Save**

4. **Test login** - should work from any localhost port

5. **Remove the line** after confirming it works

⚠️ **Warning:** Debug mode allows ALL origins - only use for testing!

---

## File Location on Hostinger

**Path:** `/public_html/src/lib/api/cors.php`

**Alternative paths** (check which one exists):
- `/public_html/api/cors.php`
- `/public_html/backend/cors.php`
- `/home/u207191294/public_html/src/lib/api/cors.php`

---

## After Uploading

1. **Clear browser cache** (Ctrl+Shift+R)
2. **Try login** at http://localhost:5174/login
3. **Check console** - CORS error should be gone

---

## Expected Result

✅ **Before:** CORS error, can't login  
✅ **After:** Login works, connects to Hostinger database

---

## Verification

After uploading, check if it works:

1. Open: http://localhost:5174/login
2. Open DevTools (F12) → Console
3. Try logging in
4. **Should NOT see:** "blocked by CORS policy"
5. **Should see:** Successful login or valid error messages

---

## Need Help?

If you can't access Hostinger file manager:
- Use FTP client (FileZilla)
- Host: ftp.darkred-dinosaur-537713.hostingersite.com
- Username/Password: Your Hostinger credentials

---

**File to upload:** `c:\xampp\htdocs\shenieva-teacher\src\lib\api\cors.php`  
**Destination:** `/public_html/src/lib/api/cors.php` on Hostinger

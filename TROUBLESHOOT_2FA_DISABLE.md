# 2FA Disable Troubleshooting Guide

## Problem: Can't Disable 2FA

If you're unable to disable two-factor authentication, follow this step-by-step troubleshooting guide.

## ✅ Pre-Flight Checks

### 1. Verify All Components Are Present
Run the test script to verify everything is in place:
```bash
php simple_2fa_test.php
```

**Expected Output:**
- ✅ All routes registered
- ✅ Controller methods exist
- ✅ Template modal present
- ✅ CSRF protection enabled

### 2. Check Symfony Environment
```bash
php bin/console debug:router app_2fa_disable
php bin/console cache:clear
```

### 3. Verify Database Connection
```bash
php bin/console doctrine:schema:validate
```

## 🔍 Step-by-Step Testing

### Step 1: Access Profile Page
1. **Start the server**:
   ```bash
   php -S localhost:8000 -t public
   ```

2. **Log in** to your application with your credentials

3. **Navigate to profile**: `http://localhost:8000/profile/edit`

4. **Check 2FA Status**:
   - Look for "Authentification à deux facteurs (2FA)" section
   - Should show current status (Activé/Désactivé)

### Step 2: Open Disable Modal
1. **Click the "Désactiver" button** in the 2FA section
2. **Modal should open** with password confirmation form
3. **Check browser console** (F12) for any JavaScript errors

### Step 3: Submit Disable Form
1. **Enter your password** in the modal form
2. **Click "Désactiver la 2FA" button**
3. **Check network tab** for the form submission

**Expected Request:**
- **URL**: `POST /2fa/disable`
- **Headers**: Should include CSRF token
- **Body**: Should include `password` and `_token`

## 🐛 Common Issues & Solutions

### Issue 1: Modal Doesn't Open
**Symptoms**: Clicking "Désactiver" does nothing
**Causes**: 
- Bootstrap JavaScript not loaded
- Modal ID mismatch
- JavaScript errors

**Solutions**:
```javascript
// Check browser console (F12)
console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');

// Manual modal trigger
const modal = new bootstrap.Modal(document.getElementById('disable2FAModal'));
modal.show();
```

### Issue 2: Form Submission Fails
**Symptoms**: 
- 403 Forbidden error
- "Invalid CSRF token" message
- Form redirects without disabling 2FA

**Causes**:
- Missing CSRF token
- Expired CSRF token
- Network issues

**Solutions**:
```php
// Check CSRF token in template
{{ csrf_token('2fa_disable') }}

// Check CSRF validation in controller
if (!$this->isCsrfTokenValid('2fa_disable', $token)) {
    $this->addFlash('error', 'Invalid CSRF token.');
    return $this->redirectToRoute('app_profile_edit');
}
```

### Issue 3: Password Verification Fails
**Symptoms**:
- "Invalid password" message
- Password seems correct but gets rejected

**Causes**:
- User password changed recently
- Case sensitivity issues
- Password hasher problems

**Solutions**:
```php
// Test password validation manually
$user = $this->getUser();
$passwordHasher = $container->get('security.password_hasher');
$isValid = $passwordHasher->isPasswordValid($user, 'your_password');
```

### Issue 4: 2FA Not Actually Disabled
**Symptoms**:
- Form submits successfully
- Success message appears
- But 2FA still shows as enabled

**Causes**:
- Database transaction not committed
- Entity not properly flushed
- Cache issues

**Solutions**:
```php
// Check database immediately after disable
$entityManager->refresh($user);
var_dump($user->isGoogleAuthEnabled()); // Should be false

// Clear all caches
php bin/console cache:clear
```

## 🛠️ Advanced Debugging

### Enable Debug Mode
Add this to your `.env.local`:
```bash
APP_ENV=dev
APP_DEBUG=true
```

### Check Symfony Logs
```bash
# Real-time log watching
php bin/console server:log

# Or check log file
tail -f var/log/dev.log
```

### Database Debugging
```bash
# Check if changes were actually saved
php bin/console doctrine:query:sql "SELECT google_auth_enabled FROM user WHERE email = 'your@email.com'"

# Check recent migrations
php bin/console doctrine:migrations:list
```

## 📋 Complete Test Script

Run this comprehensive test:
```bash
php test_2fa_disable_complete.php
```

This script tests:
- Service container setup
- Password validation
- CSRF token validation
- Database operations
- Entity state changes

## 🔄 Reset Procedure

If nothing works, try a complete reset:

### 1. Clear Everything
```bash
php bin/console cache:clear --no-warmup
rm -rf var/cache/*
rm -rf var/log/*
```

### 2. Reset 2FA Manually
```sql
-- Direct database reset
UPDATE user SET google_authenticator_secret = NULL, google_auth_enabled = 0 WHERE email = 'your@email.com';
```

### 3. Verify Reset
```bash
php bin/console doctrine:query:sql "SELECT google_auth_enabled FROM user WHERE email = 'your@email.com'"
```

## 📞 Getting Help

If you're still stuck:

1. **Check the exact error message** you're seeing
2. **Run the test scripts** provided above
3. **Check browser developer tools** for network requests
4. **Verify your user account** has the correct role
5. **Test with a different browser** (Chrome, Firefox, Edge)

## 🎯 Expected Working Behavior

When everything works correctly:
1. ✅ Modal opens when clicking "Désactiver"
2. ✅ Password validation works
3. ✅ CSRF token validation passes
4. ✅ Database updates successfully
5. ✅ Success message appears
6. ✅ Profile page shows 2FA as "Désactivé"
7. ✅ User can log in without 2FA prompt

---

**Last Updated**: February 24, 2026
**Status**: All components verified and functional

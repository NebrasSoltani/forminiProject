# Ngrok Setup Guide for OAuth Testing

## What is Ngrok?

Ngrok creates secure tunnels from public URLs to your local machine, making it perfect for testing OAuth callbacks during development.

## Quick Setup

### 1. Download Ngrok (Windows)

1. Go to https://ngrok.com/download
2. Download the Windows version
3. Extract the zip file
4. Place `ngrok.exe` in your project root or add to PATH

### 2. Configure Ngrok

1. Sign up for free at https://ngrok.com/signup
2. Get your authtoken from https://dashboard.ngrok.com/get-started/setup
3. Run: `ngrok authtoken YOUR_AUTH_TOKEN`

### 3. Start Your Symfony Server

```bash
php bin/console server:run
```

Your Symfony app will be running on `http://127.0.0.1:8000`

### 4. Start Ngrok

```bash
ngrok http 8000
```

Ngrok will give you a public URL like: `https://abc123.ngrok.io`

### 5. Update OAuth App Redirect URIs

For each OAuth provider, update the redirect URIs:

**Google:**
- `https://abc123.ngrok.io/connect/google/check`

**GitHub:**
- `https://abc123.ngrok.io/connect/github/check`

**LinkedIn:**
- `https://abc123.ngrok.io/connect/linkedin/check`

## Using Ngrok with Your Project

### Step 1: Start Symfony Server
```bash
php bin/console server:run 127.0.0.1:8000
```

### Step 2: Start Ngrok
```bash
ngrok http 8000
```

### Step 3: Update OAuth Apps
Copy the ngrok URL and update your OAuth app settings

### Step 4: Test OAuth
Visit `https://abc123.ngrok.io/login` and test OAuth buttons

## Ngrok Commands

### Start HTTP Tunnel
```bash
ngrok http 8000
```

### Start with Custom Subdomain (Paid)
```bash
ngrok http 8000 --subdomain=your-app
```

### View Request Logs
```bash
ngrok http 8000 --log=stdout
```

### Start Config File
```bash
ngrok start --all
```

## Configuration File (ngrok.yml)

Create `ngrok.yml` in your project:

```yaml
version: "2"
authtoken: YOUR_AUTH_TOKEN

tunnels:
  web:
    proto: http
    addr: 8000
    host_header: rewrite
    bind_tls: true
    subdomain: your-app-name
```

Then run:
```bash
ngrok start web
```

## Troubleshooting

### "Tunnel couldn't be established"
- Check if port 8000 is available
- Make sure Symfony server is running

### "Invalid Host Header"
Add this to your Symfony `.env`:
```bash
APP_ENV=dev
TRUSTED_PROXIES=127.0.0.1,127.0.0.1,::1
TRUSTED_HOSTS='^.*\.ngrok\.io$'
```

### "404 Not Found" on OAuth Callback
- Verify redirect URI matches exactly
- Check Symfony routes are working
- Ensure ngrok tunnel is active

## Security Notes

- Ngrok URLs are temporary and change each session
- Don't use ngrok URLs in production
- Free ngrok URLs are public - anyone can access them
- Consider using ngrok's paid features for persistent URLs

## Production Alternative

For production, you'll need:
- Real domain with SSL certificate
- Publicly accessible server
- Proper HTTPS configuration

## OAuth Provider Setup with Ngrok

### Google OAuth
1. Go to Google Cloud Console
2. Add ngrok URL to authorized redirect URIs
3. Example: `https://abc123.ngrok.io/connect/google/check`

### GitHub OAuth  
1. Go to GitHub Developer Settings
2. Update callback URL
3. Example: `https://abc123.ngrok.io/connect/github/check`

### LinkedIn OAuth
1. Go to LinkedIn Developer Portal
2. Add ngrok URL to redirect URLs
3. Example: `https://abc123.ngrok.io/connect/linkedin/check`

## Testing Checklist

- [ ] Symfony server running on port 8000
- [ ] Ngrok tunnel active
- [ ] OAuth apps updated with ngrok URLs
- [ ] Test OAuth flows work
- [ ] Check user creation in database
- [ ] Verify authentication persistence

## Quick Start Script

Create `start-ngrok.bat`:
```batch
@echo off
echo Starting Symfony server...
start cmd /k "php bin/console server:run 127.0.0.1:8000"

echo Waiting 3 seconds for server to start...
timeout /t 3 /nobreak

echo Starting ngrok...
ngrok http 8000
```

Run this script to start both servers automatically!

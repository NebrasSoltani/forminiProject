@echo off
echo Starting Symfony OAuth Development Environment...
echo.

echo Step 1: Starting Symfony server on port 8000...
start cmd /k "php bin/console server:run 127.0.0.1:8000"

echo Step 2: Waiting 3 seconds for server to start...
timeout /t 3 /nobreak > nul

echo Step 3: Starting ngrok tunnel...
echo.
echo ========================================
echo IMPORTANT: Copy the ngrok URL below!
echo Update your OAuth apps with this URL + /connect/{provider}/check
echo ========================================
echo.
ngrok http 8000

pause

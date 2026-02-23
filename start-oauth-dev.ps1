# OAuth Development Environment Starter
Write-Host "Starting Symfony OAuth Development Environment..." -ForegroundColor Green
Write-Host ""

# Step 1: Start Symfony server
Write-Host "Step 1: Starting Symfony server on port 8000..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoExit", "-Command", "php bin/console server:run 127.0.0.1:8000"

# Step 2: Wait for server to start
Write-Host "Step 2: Waiting 3 seconds for server to start..." -ForegroundColor Yellow
Start-Sleep -Seconds 3

# Step 3: Start ngrok
Write-Host "Step 3: Starting ngrok tunnel..." -ForegroundColor Yellow
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "IMPORTANT: Copy the ngrok URL below!" -ForegroundColor Red
Write-Host "Update your OAuth apps with this URL + /connect/{provider}/check" -ForegroundColor Red
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if ngrok exists in current directory
if (Test-Path ".\ngrok.exe") {
    .\ngrok.exe http 8000
} elseif (Test-Path ".\ngrok_temp\ngrok.exe") {
    .\ngrok_temp\ngrok.exe http 8000
} else {
    Write-Host "ngrok.exe not found! Please download ngrok from https://ngrok.com/download" -ForegroundColor Red
    Write-Host "and place ngrok.exe in your project root." -ForegroundColor Red
}

Read-Host "Press Enter to exit"

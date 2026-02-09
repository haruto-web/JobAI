@echo off
echo Stopping All Applications...
echo.

REM Kill all PHP processes (Laravel backends)
taskkill /F /IM php.exe >nul 2>&1

REM Kill all Node processes (React frontends)
taskkill /F /IM node.exe >nul 2>&1

echo.
echo ========================================
echo All Applications Stopped!
echo ========================================
echo.
pause

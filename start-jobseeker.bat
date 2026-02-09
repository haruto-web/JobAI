@echo off
echo Starting Jobseeker Application...
echo.

REM Start Backend (Port 8001)
start "Jobseeker Backend - Port 8001" cmd /k "cd jobseeker\backend && php artisan serve --port=8001"

REM Wait 3 seconds for backend to start
timeout /t 3 /nobreak >nul

REM Start Frontend (Port 3001)
start "Jobseeker Frontend - Port 3001" cmd /k "cd jobseeker\frontend && npm start"

echo.
echo ========================================
echo Jobseeker Application Started!
echo ========================================
echo.
echo Backend:  http://localhost:8001
echo Frontend: http://localhost:3001
echo.
echo Press any key to exit this window...
pause >nul

@echo off
echo Starting All Applications...
echo.

REM Start Jobseeker Backend (Port 8001)
start "Jobseeker Backend - Port 8001" cmd /k "cd jobseeker\backend && php artisan serve --port=8001"

REM Wait 2 seconds
timeout /t 2 /nobreak >nul

REM Start Employer Backend (Port 8002)
start "Employer Backend - Port 8002" cmd /k "cd employer\backend && php artisan serve --port=8002"

REM Wait 2 seconds
timeout /t 2 /nobreak >nul

REM Start Admin Backend (Port 8003)
start "Admin Backend - Port 8003" cmd /k "cd admin\backend && php artisan serve --port=8003"

REM Wait 3 seconds for backends to start
timeout /t 3 /nobreak >nul

REM Start Jobseeker Frontend (Port 3001)
start "Jobseeker Frontend - Port 3001" cmd /k "cd jobseeker\frontend && set PORT=3001 && npm start"

REM Wait 2 seconds
timeout /t 2 /nobreak >nul

REM Start Employer Frontend (Port 3002)
start "Employer Frontend - Port 3002" cmd /k "cd employer\frontend && set PORT=3002 && npm start"

REM Wait 2 seconds
timeout /t 2 /nobreak >nul

REM Start Admin Frontend (Port 3003)
start "Admin Frontend - Port 3003" cmd /k "cd admin\frontend && set PORT=3003 && npm start"

echo.
echo ========================================
echo All Applications Started!
echo ========================================
echo.
echo Jobseeker:
echo   Backend:  http://localhost:8001
echo   Frontend: http://localhost:3001
echo.
echo Employer:
echo   Backend:  http://localhost:8002
echo   Frontend: http://localhost:3002
echo.
echo Admin:
echo   Backend:  http://localhost:8003
echo   Frontend: http://localhost:3003
echo.
echo ========================================
echo Press any key to exit this window...
pause >nul

@echo off
echo Installing Dependencies for All Applications...
echo.

echo [1/3] Installing Jobseeker Frontend Dependencies...
cd jobseeker\frontend
call npm install
cd ..\..

echo.
echo [2/3] Installing Employer Frontend Dependencies...
cd employer\frontend
call npm install
cd ..\..

echo.
echo [3/3] Installing Admin Frontend Dependencies...
cd admin\frontend
call npm install
cd ..\..

echo.
echo ========================================
echo All Dependencies Installed!
echo ========================================
echo.
echo You can now run: start-all.bat
echo.
pause

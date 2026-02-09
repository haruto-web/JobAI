@echo off
echo Creating Admin User...
echo.
set /p email="Enter admin email: "
set /p name="Enter admin name: "
set /p password="Enter admin password: "
echo.
cd backend
php artisan admin:create "%email%" "%name%" "%password%"
echo.
pause
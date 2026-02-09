# 🚀 How to Run All Applications

## Quick Start

### Option 1: Run All at Once (Recommended)
Double-click: `start-all.bat`

This will open 6 terminal windows:
- 3 Backend servers (Laravel)
- 3 Frontend servers (React)

### Option 2: Run Individually

#### Jobseeker Application
```bash
# Terminal 1 - Backend
cd jobseeker/backend
php artisan serve --port=8001

# Terminal 2 - Frontend
cd jobseeker/frontend
npm start
```
Access: http://localhost:3001

#### Employer Application
```bash
# Terminal 1 - Backend
cd employer/backend
php artisan serve --port=8002

# Terminal 2 - Frontend
cd employer/frontend
npm start
```
Access: http://localhost:3002

#### Admin Application
```bash
# Terminal 1 - Backend
cd admin/backend
php artisan serve --port=8003

# Terminal 2 - Frontend
cd admin/frontend
npm start
```
Access: http://localhost:3003

## Stop All Applications

Double-click: `stop-all.bat`

This will stop all running PHP and Node processes.

## Access URLs

| Application | Frontend | Backend API |
|------------|----------|-------------|
| Jobseeker  | http://localhost:3001 | http://localhost:8001 |
| Employer   | http://localhost:3002 | http://localhost:8002 |
| Admin      | http://localhost:3003 | http://localhost:8003 |

## Troubleshooting

### Port Already in Use
If you get "EADDRINUSE" error:
1. Run `stop-all.bat` to kill all processes
2. Wait 5 seconds
3. Run `start-all.bat` again

### Frontend Not Starting
Make sure dependencies are installed:
```bash
cd [role]/frontend
npm install
```

### Backend Not Starting
Make sure you're in the correct directory and PHP is installed:
```bash
php --version
```

## Database

All applications share the same database:
- Database: `ai-job-recommendation_db`
- Make sure MySQL is running
- Database should already be migrated from original setup

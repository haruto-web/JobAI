# AI Job Recommendation - Project Structure

## Overview
This project is separated into 3 independent applications (Jobseeker, Employer, Admin), each with their own frontend and backend, but sharing the same database.

## Structure
```
ai-job-recommendation/
├── jobseeker/
│   ├── frontend/     (Port 3001)
│   └── backend/      (Port 8001)
├── employer/
│   ├── frontend/     (Port 3002)
│   └── backend/      (Port 8002)
└── admin/
    ├── frontend/     (Port 3003)
    └── backend/      (Port 8003)
```

## Shared Database
All three applications connect to the same database:
- **Database Name**: ai-job-recommendation_db
- **Host**: 127.0.0.1
- **Port**: 3306

## Port Configuration

### Backends (Laravel)
- Jobseeker: http://localhost:8001
- Employer: http://localhost:8002
- Admin: http://localhost:8003

### Frontends (React/Vite)
- Jobseeker: http://localhost:3001
- Employer: http://localhost:3002
- Admin: http://localhost:3003

## Running Applications

### To run Jobseeker:
```bash
# Backend
cd jobseeker/backend
php artisan serve --port=8001

# Frontend (new terminal)
cd jobseeker/frontend
npm run dev
```

### To run Employer:
```bash
# Backend
cd employer/backend
php artisan serve --port=8002

# Frontend (new terminal)
cd employer/frontend
npm run dev
```

### To run Admin:
```bash
# Backend
cd admin/backend
php artisan serve --port=8003

# Frontend (new terminal)
cd admin/frontend
npm run dev
```

## Next Steps
1. Copy your existing backend code to each backend folder
2. Copy your existing frontend code to each frontend folder
3. Customize routes and features for each role
4. Add role-specific middleware in backends
5. Customize UI for each user type in frontends

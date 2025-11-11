# Deployment Guide

## Frontend - Vercel

### Quick Deploy
1. Go to [vercel.com](https://vercel.com) and sign in
2. Click "Add New Project"
3. Import your GitHub repository
4. Configure:
   - **Root Directory**: `frontend`
   - **Framework**: Create React App
   - **Build Command**: `npm run build`
   - **Output Directory**: `build`

### Environment Variables
Add in Vercel dashboard:
```
REACT_APP_API_URL=https://your-backend.railway.app
```

### Custom Domain (Optional)
- Go to Project Settings → Domains
- Add your custom domain

---

## Backend - Railway

### Quick Deploy
1. Go to [railway.app](https://railway.app) and sign in
2. Click "New Project" → "Deploy from GitHub repo"
3. Select your repository
4. Railway auto-detects `Dockerfile` and `railway.json`

### Add Database
1. In your project, click "New" → "Database"
2. Select "PostgreSQL" (recommended) or "MySQL"
3. Railway auto-creates connection variables

### Environment Variables
Add in Railway dashboard:

```bash
# App Config
APP_KEY=                    # Generate: php artisan key:generate --show
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.railway.app

# Database (Auto-filled by Railway)
DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}

# CORS
FRONTEND_URL=https://your-frontend.vercel.app

# API Keys
OPENAI_API_KEY=your-openai-key

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

### Post-Deployment
1. Migrations run automatically (see Dockerfile CMD)
2. Check logs in Railway dashboard
3. Update CORS in `backend/config/cors.php` if needed

---

## Connect Frontend to Backend

### Update Frontend Environment
In Vercel, set:
```
REACT_APP_API_URL=https://your-backend-url.railway.app
```

### Update Backend CORS
In Railway, set:
```
FRONTEND_URL=https://your-frontend-url.vercel.app
```

---

## Troubleshooting

### Railway Issues
- **500 Error**: Check logs, verify APP_KEY is set
- **Database Connection**: Ensure DB variables use Railway references
- **Port Issues**: Railway auto-assigns PORT variable

### Vercel Issues
- **API Calls Fail**: Check REACT_APP_API_URL is correct
- **404 on Refresh**: vercel.json handles this (already configured)
- **Build Fails**: Check build logs, verify dependencies

---

## CLI Deployment (Alternative)

### Vercel CLI
```bash
cd frontend
npm i -g vercel
vercel login
vercel
```

### Railway CLI
```bash
npm i -g @railway/cli
railway login
railway link
railway up
```

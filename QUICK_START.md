# ⚡ Quick Start Deployment

## Prerequisites
- GitHub account
- Render account (https://render.com)
- Netlify account (https://netlify.com)

## 🚀 Deploy in 3 Steps

### Step 1: Push to GitHub (5 minutes)
```bash
cd c:\Users\venan\ai-job-recommendation
git init
git add .
git commit -m "Ready for deployment"
```
Create repo on GitHub, then:
```bash
git remote add origin https://github.com/YOUR_USERNAME/ai-job-recommendation.git
git push -u origin main
```

### Step 2: Deploy Backend on Render (10 minutes)
1. Go to https://render.com → **New +** → **Web Service**
2. Connect GitHub repo
3. Settings:
   - **Root Directory**: `backend`
   - **Build Command**: 
     ```
     composer install --no-dev --optimize-autoloader
     ```
   - **Start Command**: 
     ```
     php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
     ```
4. Add environment variables (copy from backend/.env, update these):
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://YOUR-APP.onrender.com`
   - `DB_*` (get from Railway/PlanetScale)
   - `FRONTEND_URL=https://YOUR-APP.netlify.app` (update after Step 3)
   - `CORS_ALLOWED_ORIGINS=https://YOUR-APP.netlify.app`
   - `SESSION_SECURE_COOKIE=true`
   - `SESSION_SAME_SITE=none`

5. Deploy! Copy your Render URL.

### Step 3: Deploy Frontend on Netlify (5 minutes)
1. Update `frontend/.env.production`:
   ```
   REACT_APP_API_URL=https://YOUR-APP.onrender.com/api
   ```
2. Commit and push:
   ```bash
   git add frontend/.env.production
   git commit -m "Update API URL"
   git push
   ```
3. Go to https://netlify.com → **Add new site** → **Import project**
4. Settings:
   - **Base directory**: `frontend`
   - **Build command**: `npm run build`
   - **Publish directory**: `frontend/build`
5. Environment variables:
   - `REACT_APP_API_URL=https://YOUR-APP.onrender.com/api`
6. Deploy!

### Step 4: Connect Frontend & Backend
1. Copy your Netlify URL (e.g., `https://your-app.netlify.app`)
2. Go to Render → Your service → Environment
3. Update:
   - `FRONTEND_URL=https://your-app.netlify.app`
   - `CORS_ALLOWED_ORIGINS=https://your-app.netlify.app`
   - `SANCTUM_STATEFUL_DOMAINS=your-app.netlify.app`
4. Redeploy backend

### Step 5: Update Google OAuth
1. Go to https://console.cloud.google.com/apis/credentials
2. Edit OAuth client
3. Add redirect URI: `https://YOUR-APP.onrender.com/auth/google/callback`

## ✅ Done!
Visit your Netlify URL and test your app!

## 📝 Need Database?
**Option 1: Railway (Recommended)**
1. Go to https://railway.app
2. New Project → Add MySQL
3. Copy connection details to Render env vars

**Option 2: PlanetScale**
1. Go to https://planetscale.com
2. Create database
3. Get connection string

## 🐛 Troubleshooting
- **CORS errors**: Check FRONTEND_URL and CORS_ALLOWED_ORIGINS match exactly
- **API not connecting**: Verify REACT_APP_API_URL in Netlify
- **Database errors**: Check DB credentials in Render
- **500 errors**: Check Render logs

## 💡 Tips
- Render free tier sleeps after 15 min (first request takes ~30s)
- Keep your .env file secure (never commit real keys)
- Use Render's "Manual Deploy" to redeploy after env changes

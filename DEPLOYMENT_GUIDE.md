# 🚀 Deployment Guide: Netlify + Render

## Part 1: Deploy Backend to Render (Do This First!)

### Step 1: Push Code to GitHub
```bash
cd c:\Users\venan\ai-job-recommendation
git init
git add .
git commit -m "Initial commit"
# Create a new repo on GitHub, then:
git remote add origin https://github.com/YOUR_USERNAME/ai-job-recommendation.git
git push -u origin main
```

### Step 2: Deploy Backend on Render
1. Go to https://render.com and sign up/login
2. Click **"New +"** → **"Web Service"**
3. Connect your GitHub repository
4. Configure:
   - **Name**: `ai-job-recommendation-backend`
   - **Root Directory**: `backend`
   - **Environment**: `PHP`
   - **Build Command**: 
     ```
     composer install --no-dev --optimize-autoloader && php artisan config:cache && php artisan route:cache
     ```
   - **Start Command**: 
     ```
     php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
     ```

### Step 3: Create MySQL Database on Render
1. In Render dashboard, click **"New +"** → **"PostgreSQL"** (or use external MySQL)
2. For MySQL, use **PlanetScale** (free) or **Railway**:
   - Go to https://railway.app
   - Create new project → Add MySQL
   - Copy connection details

### Step 4: Add Environment Variables in Render
Go to your web service → **Environment** tab → Add these:

```
APP_NAME=AI Job Recommendation
APP_ENV=production
APP_KEY=base64:eHKKtsk5UsMFVhXoz4arMGdLi7lcCAylzpnEU93YFI0=
APP_DEBUG=false
APP_URL=https://YOUR-APP-NAME.onrender.com

DB_CONNECTION=mysql
DB_HOST=<from-railway-or-planetscale>
DB_PORT=3306
DB_DATABASE=<your-db-name>
DB_USERNAME=<your-db-user>
DB_PASSWORD=<your-db-password>

OPENAI_API_KEY=<your-openai-key>

SESSION_DRIVER=cookie
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none

SANCTUM_STATEFUL_DOMAINS=your-app.netlify.app
FRONTEND_URL=https://your-app.netlify.app
CORS_ALLOWED_ORIGINS=https://your-app.netlify.app

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=b38906797@gmail.com
MAIL_PASSWORD=<your-mail-password>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=b38906797@gmail.com
MAIL_FROM_NAME="AI Job Recommendation"

GOOGLE_CLIENT_ID=<your-google-client-id>
GOOGLE_CLIENT_SECRET=<your-google-client-secret>
GOOGLE_REDIRECT_URI=https://YOUR-APP-NAME.onrender.com/auth/google/callback

JWT_SECRET=<generate-new-32-char-string>

GOOGLE_SEARCH_API_KEY=<your-search-api-key>
GOOGLE_SEARCH_ENGINE_ID=<your-search-engine-id>
```

5. Click **"Save Changes"** - Render will auto-deploy

---

## Part 2: Deploy Frontend to Netlify

### Step 1: Update Frontend Environment Variable
1. Open `frontend/.env.production` (create if doesn't exist)
2. Add:
   ```
   REACT_APP_API_URL=https://YOUR-APP-NAME.onrender.com/api
   ```

### Step 2: Deploy to Netlify
1. Go to https://netlify.com and sign up/login
2. Click **"Add new site"** → **"Import an existing project"**
3. Connect to GitHub and select your repository
4. Configure:
   - **Base directory**: `frontend`
   - **Build command**: `npm run build`
   - **Publish directory**: `frontend/build`
5. Add environment variable:
   - Key: `REACT_APP_API_URL`
   - Value: `https://YOUR-APP-NAME.onrender.com/api`
6. Click **"Deploy site"**

### Step 3: Update Backend CORS Settings
After Netlify gives you a URL (e.g., `https://your-app.netlify.app`):
1. Go back to Render → Your backend service → Environment
2. Update these variables:
   ```
   FRONTEND_URL=https://your-app.netlify.app
   SANCTUM_STATEFUL_DOMAINS=your-app.netlify.app
   CORS_ALLOWED_ORIGINS=https://your-app.netlify.app
   ```
3. Update Google OAuth redirect URI in Google Console:
   - Add: `https://YOUR-APP-NAME.onrender.com/auth/google/callback`

---

## Part 3: Final Steps

### Update Google OAuth Console
1. Go to https://console.cloud.google.com/apis/credentials
2. Edit your OAuth 2.0 Client
3. Add authorized redirect URIs:
   - `https://YOUR-APP-NAME.onrender.com/auth/google/callback`
   - `https://your-app.netlify.app`

### Test Your Deployment
1. Visit your Netlify URL: `https://your-app.netlify.app`
2. Try registering/logging in
3. Test job recommendations

---

## 🔧 Troubleshooting

### Backend Issues
- Check Render logs: Dashboard → Your service → Logs
- Common issues:
  - Database connection: Verify DB credentials
  - Missing APP_KEY: Run `php artisan key:generate` locally, copy to Render

### Frontend Issues
- Check Netlify deploy logs
- CORS errors: Verify CORS_ALLOWED_ORIGINS in backend
- API not connecting: Check REACT_APP_API_URL

### Database Migration
If migrations don't run automatically:
1. Go to Render → Your service → Shell
2. Run: `php artisan migrate --force`

---

## 💰 Cost Estimate
- **Render**: Free tier (sleeps after 15 min inactivity)
- **Netlify**: Free tier (100GB bandwidth/month)
- **Railway MySQL**: Free $5 credit/month
- **Total**: $0/month (with limitations)

For production without sleep: Render paid plan ($7/month)

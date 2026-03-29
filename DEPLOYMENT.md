# 🚀 Render Deployment Guide - Campus Navigator Backend

This guide explains how to deploy your Laravel API backend to Render using Docker.

## 📁 Files Created

All necessary files have been created in your project root:

1. **`Dockerfile`** - Main Docker configuration for building your Laravel app
2. **`.dockerignore`** - Excludes unnecessary files from Docker build
3. **`docker-entrypoint.sh`** - Startup script for permissions, migrations, and caching
4. **`render.yaml`** - Blueprint for Render service configuration (optional)

## 🔧 Prerequisites

1. **GitHub/GitLab Repository**: Your code must be in a Git repository
2. **Render Account**: Sign up at https://render.com
3. **Database**: Render provides free PostgreSQL, or use external MySQL/PostgreSQL

## 📦 Step-by-Step Deployment

### Step 1: Commit and Push Docker Files

```bash
git add Dockerfile .dockerignore docker-entrypoint.sh render.yaml routes/api.php
git commit -m "Add Docker configuration for Render deployment"
git push origin main
```

### Step 2: Create a Render Web Service

1. Go to https://dashboard.render.com
2. Click **"New +"** → **"Web Service"**
3. Connect your GitHub/GitLab repository
4. Configure the service:
   - **Name**: `campus-navigator-api` (or your preferred name)
   - **Region**: Choose closest to your users
   - **Branch**: `main`
   - **Runtime**: **Docker**
   - **Instance Type**: Free or Starter (recommended for production)

### Step 3: Set Environment Variables

In the Render dashboard, add these environment variables:

#### Required Variables:

```env
APP_NAME=Campus Navigator API
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:XXXXX  # Generate using: php artisan key:generate --show
APP_URL=https://your-app-name.onrender.com

LOG_CHANNEL=stdout
LOG_LEVEL=error

# Database (use Render's PostgreSQL or external MySQL)
DB_CONNECTION=pgsql
DB_HOST=your-db-host.onrender.com
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Cache & Queue (Redis recommended for production)
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Redis (use Render's Redis or external)
REDIS_HOST=your-redis-host.onrender.com
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# JWT Authentication
JWT_SECRET=your_jwt_secret_here  # Generate using: php artisan jwt:secret --show
JWT_TTL=60

# Firebase (paste your JSON credentials as single line)
FIREBASE_CREDENTIALS={"type":"service_account",...}

# Optional: Auto-run migrations
RUN_MIGRATIONS=true

# CORS
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SESSION_DOMAIN=.onrender.com
```

### Step 4: Create Database (Optional)

If using Render's managed database:

1. Click **"New +"** → **"PostgreSQL"**
2. Name it `campus-navigator-db`
3. Choose **Free** or **Starter** plan
4. After creation, copy connection details to your Web Service environment variables

### Step 5: Create Redis (Optional)

For better performance:

1. Click **"New +"** → **"Redis"**
2. Name it `campus-navigator-redis`
3. Choose **Free** plan (30MB)
4. Copy connection details to your Web Service environment variables

### Step 6: Deploy

1. Click **"Create Web Service"**
2. Render will automatically build and deploy your app
3. Wait for the build to complete (5-10 minutes for first deployment)
4. Your API will be available at: `https://your-app-name.onrender.com`

## ✅ Verify Deployment

Test your API endpoints:

```bash
# Health check
curl https://your-app-name.onrender.com/api/health

# Test login endpoint
curl -X POST https://your-app-name.onrender.com/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

## 🔒 Security Checklist

- ✅ **APP_DEBUG=false** in production
- ✅ **APP_ENV=production**
- ✅ Unique **APP_KEY** generated
- ✅ Strong **JWT_SECRET** generated
- ✅ Database credentials secured
- ✅ Firebase credentials stored securely
- ✅ CORS configured properly

## 📱 Connect Flutter Frontend

Update your Flutter app's API base URL:

```dart
// lib/config/api_config.dart
class ApiConfig {
  static const String baseUrl = 'https://your-app-name.onrender.com/api/v1';
}
```

## 🔄 Continuous Deployment

Render automatically redeploys when you push to your main branch:

```bash
git add .
git commit -m "Your changes"
git push origin main
# Render will auto-deploy!
```

## 🐛 Troubleshooting

### Issue: Build fails with "composer: command not found"
**Solution**: Dockerfile includes Composer installation. Rebuild from scratch.

### Issue: Permission denied on storage/logs
**Solution**: The `docker-entrypoint.sh` handles this. Check if script has execute permissions.

### Issue: JWT secret not found
**Solution**: Ensure `JWT_SECRET` is set in environment variables, or the entrypoint will generate it.

### Issue: Database connection refused
**Solution**: Check `DB_HOST`, `DB_PORT`, `DB_PASSWORD` are correct. Ensure database is in same region.

### Issue: 502 Bad Gateway
**Solution**: Check logs in Render dashboard. Usually means app crashed on startup. Verify all required env vars are set.

### View Logs:
```bash
# In Render dashboard, go to your service → "Logs" tab
# Or use Render CLI:
render logs -f campus-navigator-api
```

## 🚀 Performance Tips

1. **Use Redis**: Much faster than file-based cache
2. **Enable OPcache**: Already enabled in Dockerfile using PHP-FPM Alpine
3. **Optimize Autoloader**: Already done with `composer install --optimize-autoloader`
4. **Cache Config**: Handled by `docker-entrypoint.sh`
5. **Upgrade Plan**: Free tier sleeps after 15min inactivity. Use Starter plan for production.

## 📊 Monitoring

Add a health check in Render:
- **Health Check Path**: `/api/health`
- Render will automatically monitor and restart if unhealthy

## 🔐 Environment Variables Management

For sensitive values (Firebase credentials, API keys):

1. Never commit `.env` file
2. Use Render's environment variable dashboard
3. For Firebase: Either paste JSON as single line or use secret files

## 🌍 Custom Domain (Optional)

1. Go to your Web Service → **"Settings"** → **"Custom Domains"**
2. Add your domain (e.g., `api.campusnavigator.com`)
3. Configure DNS with your domain provider:
   - Type: `CNAME`
   - Name: `api`
   - Value: `your-app-name.onrender.com`

## 📚 Additional Resources

- [Render Docs](https://render.com/docs)
- [Laravel Deployment](https://laravel.com/docs/12.x/deployment)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)

## 🎉 You're Done!

Your Laravel API is now deployed and ready to serve your Flutter frontend!

**API Base URL**: `https://your-app-name.onrender.com/api/v1`

---

*Generated for Campus Navigator Backend - Deployed on Render with Docker*

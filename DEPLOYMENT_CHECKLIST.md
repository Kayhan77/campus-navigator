# ✅ Deployment Checklist

Use this checklist to ensure your deployment is complete and successful.

## Pre-Deployment (Local Preparation)

### 1. Generate Required Secrets
- [ ] Generate `APP_KEY`: Run `php artisan key:generate --show` and save the output
- [ ] Generate `JWT_SECRET`: Run `php artisan jwt:secret --show` and save the output
- [ ] Have Firebase credentials JSON ready

### 2. Test Docker Locally (Recommended)
- [ ] Install Docker Desktop
- [ ] Update `docker-compose.yml` with your APP_KEY and JWT_SECRET
- [ ] Run `docker-compose up -d`
- [ ] Test health endpoint: `curl http://localhost:10000/api/health`
- [ ] Verify response: `{"status":"ok",...}`
- [ ] Stop containers: `docker-compose down`

### 3. Commit Files to Git
```bash
git add Dockerfile .dockerignore docker-entrypoint.sh php.production.ini
git add render.yaml routes/api.php
git add DEPLOYMENT.md DOCKER_LOCAL_TESTING.md FILES_SUMMARY.md
git add docker-compose.yml DEPLOYMENT_CHECKLIST.md
git commit -m "Add Docker configuration for Render deployment"
git push origin main
```
- [ ] All files committed
- [ ] Pushed to GitHub/GitLab
- [ ] Repository is accessible

## Render Setup

### 4. Create Database (If using Render PostgreSQL)
- [ ] Go to https://dashboard.render.com
- [ ] Click "New +" → "PostgreSQL"
- [ ] Name: `campus-navigator-db`
- [ ] Plan: Free or Starter
- [ ] Region: Same as your API service
- [ ] Database created successfully
- [ ] Copy connection details (Host, Database, User, Password)

### 5. Create Redis (If using Render Redis)
- [ ] Click "New +" → "Redis"
- [ ] Name: `campus-navigator-redis`
- [ ] Plan: Free (30MB) or Starter
- [ ] Region: Same as your API service
- [ ] Redis created successfully
- [ ] Copy connection details (Host, Password)

### 6. Create Web Service
- [ ] Click "New +" → "Web Service"
- [ ] Connect GitHub/GitLab repository
- [ ] Repository: `campus-navigator-backend`
- [ ] Branch: `main`
- [ ] Name: `campus-navigator-api` (or your choice)
- [ ] Region: Choose closest to users (e.g., Oregon)
- [ ] Runtime: **Docker** (IMPORTANT!)
- [ ] Plan: Free (for testing) or Starter (for production)
- [ ] Health Check Path: `/api/health`

### 7. Configure Environment Variables

**Critical Variables** (Must Set):
- [ ] `APP_NAME=Campus Navigator API`
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY=base64:your_generated_key`
- [ ] `APP_URL=https://your-app-name.onrender.com`
- [ ] `JWT_SECRET=your_generated_jwt_secret`

**Database Variables** (From Step 4):
- [ ] `DB_CONNECTION=pgsql` (or `mysql`)
- [ ] `DB_HOST=your-db-host.onrender.com`
- [ ] `DB_PORT=5432` (or `3306` for MySQL)
- [ ] `DB_DATABASE=your_database_name`
- [ ] `DB_USERNAME=your_db_username`
- [ ] `DB_PASSWORD=your_db_password`

**Redis Variables** (From Step 5):
- [ ] `CACHE_STORE=redis`
- [ ] `QUEUE_CONNECTION=redis`
- [ ] `SESSION_DRIVER=redis`
- [ ] `REDIS_HOST=your-redis-host.onrender.com`
- [ ] `REDIS_PASSWORD=your_redis_password`
- [ ] `REDIS_PORT=6379`

**Firebase Variables**:
- [ ] `FIREBASE_CREDENTIALS={"type":"service_account",...}`
      (Paste entire JSON as single line)

**Optional Variables**:
- [ ] `RUN_MIGRATIONS=true` (Auto-run migrations on deploy)
- [ ] `LOG_CHANNEL=stdout`
- [ ] `LOG_LEVEL=error`
- [ ] `SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1`
- [ ] `SESSION_DOMAIN=.onrender.com`

### 8. Deploy
- [ ] Click "Create Web Service"
- [ ] Wait for build to complete (5-10 minutes first time)
- [ ] Check build logs for errors
- [ ] Service status shows "Live"

## Post-Deployment Verification

### 9. Test Endpoints
```bash
# Replace with your actual Render URL
export API_URL="https://your-app-name.onrender.com"

# Test health check
curl $API_URL/api/health

# Test public endpoints
curl $API_URL/api/v1/buildings
curl $API_URL/api/v1/events

# Test authentication (if you have seeded users)
curl -X POST $API_URL/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password"}'
```

- [ ] Health check returns `{"status":"ok"}`
- [ ] Public endpoints return valid responses
- [ ] Authentication works correctly
- [ ] No 500 errors

### 10. Monitor & Optimize
- [ ] Check Render logs for any warnings/errors
- [ ] Verify database connections are working
- [ ] Verify Redis cache is functioning
- [ ] Test all critical API endpoints
- [ ] Set up alerts (if needed)

### 11. Connect Flutter Frontend
Update your Flutter app's API configuration:

```dart
class ApiConfig {
  static const String baseUrl = 'https://your-app-name.onrender.com/api/v1';
}
```

- [ ] Flutter app connected to production API
- [ ] Test login from Flutter app
- [ ] Test all API calls from Flutter app
- [ ] Verify push notifications work (if implemented)

## Security Final Check

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production` is set
- [ ] Strong `APP_KEY` generated
- [ ] Strong `JWT_SECRET` generated
- [ ] Database password is secure
- [ ] `.env` file is NOT committed to Git
- [ ] All sensitive data is in Render environment variables
- [ ] HTTPS is enabled (automatic on Render)
- [ ] CORS is configured properly

## Performance Optimization (Optional)

- [ ] Upgrade from Free to Starter plan (no cold starts)
- [ ] Enable Redis for caching
- [ ] Monitor response times
- [ ] Set up custom domain (if needed)
- [ ] Enable CDN for static assets (if applicable)

## Troubleshooting (If Issues Occur)

### Build Failed
- [ ] Check Render build logs
- [ ] Verify Dockerfile syntax
- [ ] Ensure all files are committed to Git
- [ ] Check PHP version compatibility

### Service Crashes on Startup
- [ ] Check Render logs tab
- [ ] Verify all required environment variables are set
- [ ] Check database connection variables
- [ ] Verify `APP_KEY` is set correctly

### 502 Bad Gateway
- [ ] Check if service is "Live"
- [ ] Verify port 10000 is exposed
- [ ] Check startup logs for errors
- [ ] Ensure database is accessible

### Database Connection Errors
- [ ] Verify database host and credentials
- [ ] Ensure database and API are in same region
- [ ] Check if database is "Available"
- [ ] Test connection manually

## Success Indicators

Your deployment is successful when:
- ✅ Build completes without errors
- ✅ Service status is "Live" (green indicator)
- ✅ Health check endpoint responds
- ✅ API endpoints return valid data
- ✅ Flutter app can connect and authenticate
- ✅ No errors in Render logs
- ✅ Database migrations ran successfully
- ✅ Redis cache is connected

## 🎉 Deployment Complete!

Once all checkboxes are marked, your Laravel API is successfully deployed and ready for production use!

**API URL**: `https://your-app-name.onrender.com/api/v1`
**Health Check**: `https://your-app-name.onrender.com/api/health`

## Next Steps

1. Monitor your application using Render's built-in metrics
2. Set up custom domain (optional)
3. Configure backup strategy for database
4. Set up monitoring/alerting (optional)
5. Plan for scaling if traffic increases

## Support & Documentation

- **Render Docs**: https://render.com/docs
- **Laravel Deployment**: https://laravel.com/docs/deployment
- **Deployment Guide**: See [DEPLOYMENT.md](./DEPLOYMENT.md)
- **Local Testing**: See [DOCKER_LOCAL_TESTING.md](./DOCKER_LOCAL_TESTING.md)

---

*Last Updated: 2026-03-29*
*Campus Navigator Backend - Render Deployment*

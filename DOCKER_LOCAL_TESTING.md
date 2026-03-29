# 🧪 Local Docker Testing Guide

Test your Docker setup locally before deploying to Render.

## Prerequisites

- Docker installed ([Get Docker](https://docs.docker.com/get-docker/))
- Docker Compose installed (included with Docker Desktop)

## Quick Start

```bash
# 1. Generate APP_KEY
php artisan key:generate --show
# Copy the output and add to docker-compose.yml

# 2. Generate JWT_SECRET
php artisan jwt:secret --show
# Copy the output and add to docker-compose.yml

# 3. Start all services
docker-compose up -d

# 4. Wait for services to be ready (30-60 seconds)
docker-compose logs -f app

# 5. Test the API
curl http://localhost:10000/api/health
```

## Expected Output

```json
{
  "status": "ok",
  "timestamp": "2026-03-29T...",
  "service": "Campus Navigator API"
}
```

## Useful Commands

```bash
# View logs
docker-compose logs -f app

# Restart app
docker-compose restart app

# Run migrations manually
docker-compose exec app php artisan migrate

# Access database
docker-compose exec db mysql -u root -p campus_navigator

# Stop all services
docker-compose down

# Stop and remove volumes (fresh start)
docker-compose down -v
```

## Troubleshooting

### Error: "APP_KEY not set"
Generate key: `php artisan key:generate --show` and add to docker-compose.yml

### Error: "Connection refused" to MySQL
Wait 30-60 seconds for MySQL to initialize. Check logs: `docker-compose logs db`

### Error: "Permission denied"
Run: `docker-compose exec app chmod -R 775 storage bootstrap/cache`

## Testing API Endpoints

```bash
# Health check
curl http://localhost:10000/api/health

# Get buildings
curl http://localhost:10000/api/v1/buildings

# Login (requires seeded data)
curl -X POST http://localhost:10000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password"}'
```

## Clean Up

```bash
# Stop and remove everything
docker-compose down -v

# Remove Docker images
docker rmi campus-navigator-backend-app
```

## Next Steps

Once everything works locally, proceed with Render deployment using the **DEPLOYMENT.md** guide.

---

*For production deployment, see [DEPLOYMENT.md](DEPLOYMENT.md)*

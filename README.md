# Event-Driven Notification System

A scalable notification system built with Laravel 11 that processes and delivers messages through multiple channels (SMS, Email, Push) with high throughput and reliable delivery.

## Setup

### Prerequisites
- Docker 20.10+
- Docker Compose 2.0+

### Installation

1. Clone the repository
```bash
git clone <repository-url>
cd assessment
```

2. Copy environment file
```bash
cp .env.example .env
```

3. Configure webhook URL in `.env`
```env
WEBHOOK_URL=https://webhook.site/your-uuid-here
```

4. Start the application
```bash
docker-compose up -d --build
```

5. Run migrations
```bash
docker exec -it notification-app php artisan migrate --force
```

6. Generate application key
```bash
docker exec -it notification-app php artisan key:generate --force
```

The API will be available at `http://localhost:8000`

## Bonus Features

### Scheduled Notifications
Schedule notifications for future delivery:
```bash
POST /notifications
{
  "recipient": "+905551234567",
  "channel": "sms",
  "content": "Your appointment reminder",
  "scheduled_at": "2024-12-25 10:00:00"
}
```

### Failed Jobs Management
Monitor and retry failed notifications:
```bash
GET /failed-jobs              # List all failed jobs
GET /failed-jobs/{id}         # Get failed job details
POST /failed-jobs/{id}/retry  # Retry specific job
POST /failed-jobs/retry-all   # Retry all failed jobs
DELETE /failed-jobs/{id}      # Delete failed job
POST /failed-jobs/flush       # Delete all failed jobs
```

### CI/CD Pipeline
Automated testing and linting on every push via GitHub Actions.

## API Endpoints

**Base URL:** `http://localhost:8000/api/v1`

### Create Notification
```bash
POST /notifications
Content-Type: application/json

{
  "recipient": "+905551234567",
  "channel": "sms",
  "content": "Your verification code is 123456",
  "priority": "high"
}
```

### Batch Create (up to 1000)
```bash
POST /notifications/batch
Content-Type: application/json

{
  "notifications": [
    {
      "recipient": "+905551234567",
      "channel": "sms",
      "content": "Message 1"
    }
  ]
}
```

### Get Notification
```bash
GET /notifications/{id}
```

### Get Batch Notifications
```bash
GET /notifications/batch/{batchId}
```

### Cancel Notification
```bash
POST /notifications/{id}/cancel
```

### List Notifications
```bash
GET /notifications?status=sent&channel=sms&per_page=20
```

Query parameters:
- `status`: pending, processing, sent, failed, cancelled
- `channel`: sms, email, push
- `start_date`: YYYY-MM-DD
- `end_date`: YYYY-MM-DD
- `per_page`: 1-100

### System Metrics
```bash
GET /metrics
```

### Health Check
```bash
GET /health
```

### Failed Jobs
```bash
GET /failed-jobs
GET /failed-jobs/{id}
POST /failed-jobs/{id}/retry
POST /failed-jobs/retry-all
DELETE /failed-jobs/{id}
POST /failed-jobs/flush
```

## Testing

Run the test suite:
```bash
docker exec -it notification-app php artisan test
```

## Architecture

**Queue System:**
- 3 priority levels (high, normal, low)
- Redis-based queue processing
- Rate limiting: 100 messages/second per channel
- Automatic retry with exponential backoff

**Services:**
- `notification-app`: Laravel application (PHP 8.2)
- `notification-nginx`: Web server (port 8000)
- `notification-mysql`: Database (MySQL 8.0)
- `notification-redis`: Queue and cache
- `notification-queue-worker`: Async job processor
- `notification-scheduler`: Cron job handler

**Key Features:**
- Multi-channel support (SMS, Email, Push)
- Batch operations (up to 1000 notifications)
- Priority queue management
- Rate limiting per channel
- Idempotency support
- Retry logic with backoff
- Real-time metrics and monitoring
- Structured logging with correlation IDs

## Configuration

Key environment variables in `.env`:

```env
# Webhook Configuration
WEBHOOK_URL=https://webhook.site/your-uuid-here

# Rate Limiting
RATE_LIMIT_PER_CHANNEL=100
RATE_LIMIT_WINDOW=1

# Retry Configuration
MAX_RETRY_ATTEMPTS=3
RETRY_BACKOFF_SECONDS=60

# Batch Limits
BATCH_SIZE_LIMIT=1000
```

## Monitoring

**View Logs:**
```bash
docker logs -f notification-app
docker logs -f notification-queue-worker
```

**Queue Status:**
```bash
docker exec -it notification-app php artisan queue:monitor
```

**Failed Jobs:**
```bash
docker exec -it notification-app php artisan queue:failed
docker exec -it notification-app php artisan queue:retry all
```

## API Documentation

Full OpenAPI specification: `openapi.yaml`

View with Swagger Editor: https://editor.swagger.io/

## Troubleshooting

**Clear caches:**
```bash
docker exec -it notification-app php artisan config:clear
docker exec -it notification-app php artisan route:clear
docker exec -it notification-app php artisan cache:clear
```

**Restart services:**
```bash
docker-compose restart
```

**Stop services:**
```bash
docker-compose down
```

**Rebuild:**
```bash
docker-compose down
docker-compose up -d --build
```

## Content Limits

- SMS: 160 characters
- Email: 10,000 characters
- Push: 256 characters

## Technology Stack

- Laravel 11.31
- PHP 8.2
- MySQL 8.0
- Redis 7
- Nginx (Alpine)
- Docker & Docker Compose

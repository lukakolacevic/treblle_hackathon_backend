# Quick Start Guide

## Setup & Start Server

### 1. Create Database Migration
```bash
php bin/console make:migration
```

### 2. Run Migration
```bash
php bin/console doctrine:migrations:migrate
```

### 3. Start Symfony Server
```bash
symfony server:start
```

Or use PHP built-in server:
```bash
php -S localhost:8000 -t public/
```

## API Endpoints

### Health Check
```bash
curl http://localhost:8000/api/health
```

### Create a Log Entry
```bash
curl -X POST http://localhost:8000/api/logs \
  -H "Content-Type: application/json" \
  -d '{
    "method": "GET",
    "path": "/api/users",
    "response": 200,
    "response_time": 45
  }'
```

### Get All Logs
```bash
curl http://localhost:8000/api/logs
```

### Get Logs with Pagination
```bash
curl "http://localhost:8000/api/logs?limit=5&offset=0"
```

### Sorting

#### Sort by Created Date (descending - default)
```bash
curl "http://localhost:8000/api/logs?sort_by=created_at&sort_order=DESC"
```

#### Sort by Response Time (ascending)
```bash
curl "http://localhost:8000/api/logs?sort_by=response_time&sort_order=ASC"
```

### Filtering

#### Filter by HTTP Method
```bash
curl "http://localhost:8000/api/logs?filter_method=GET"
```

#### Filter by Response Code
```bash
curl "http://localhost:8000/api/logs?filter_response=200"
```

#### Filter by Time Range
```bash
curl "http://localhost:8000/api/logs?filter_time_from=2024-10-23T00:00:00&filter_time_to=2024-10-23T23:59:59"
```

#### Filter by Response Time Range (in milliseconds)
```bash
curl "http://localhost:8000/api/logs?filter_response_time_min=100&filter_response_time_max=500"
```

### Search

#### Search in Path
```bash
curl "http://localhost:8000/api/logs?search=users"
```

### Combined Example (All Features)
```bash
curl "http://localhost:8000/api/logs?sort_by=response_time&sort_order=DESC&filter_method=GET&filter_response=200&search=api&limit=20&offset=0"
```

### Get Single Log
```bash
curl http://localhost:8000/api/logs/1
```

### Delete a Log
```bash
curl -X DELETE http://localhost:8000/api/logs/1
```

### Test Endpoint (Auto-creates log)
```bash
# GET request
curl http://localhost:8000/api/test

# POST request
curl -X POST http://localhost:8000/api/test

# PUT request
curl -X PUT http://localhost:8000/api/test

# PATCH request
curl -X PATCH http://localhost:8000/api/test

# DELETE request
curl -X DELETE http://localhost:8000/api/test
```

## Available HTTP Methods
- GET
- POST
- PUT
- PATCH
- DELETE
- HEAD
- OPTIONS
- CONNECT
- TRACE

## Response Format

All responses are in JSON format:

**Success Response:**
```json
{
  "data": [...],
  "count": 10
}
```

**Error Response:**
```json
{
  "error": "Error message"
}
```

## Database

The project uses SQLite by default (check `var/data.db`).

To view logs in database:
```bash
php bin/console doctrine:query:sql "SELECT * FROM api_log"
```


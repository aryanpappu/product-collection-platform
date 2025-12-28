# Scalable Commerce Platform

A full-stack e-commerce platform with Laravel backend and React frontend, featuring merchant management, collection operations, and bulk product imports.

## Features

- Multi-merchant support with merchant selection
- Collection management with CRUD operations
- Bulk product operations via CSV upload
- Async job processing for large-scale operations
- Product import system with validation
- Real-time operation status tracking
- Error logging and reporting

## Technology Stack

### Backend
- **Framework:** Laravel 12.x
- **Database:** SQLite (configurable to MySQL/PostgreSQL)
- **Queue:** Database driver
- **Cache:** Database driver
- **PHP Version:** 8.2+

### Frontend
- **Framework:** React 19.2
- **Build Tool:** Vite 7.2
- **UI Library:** Material-UI (MUI) 7.3
- **State Management:** React Query (TanStack Query)
- **Routing:** React Router DOM 7.11
- **Form Handling:** Formik + Yup
- **HTTP Client:** Axios
- **Notifications:** React Toastify

## System Requirements

- **PHP:** > 8.2
- **Composer:** Latest version
- **Node.js:** >= 18.x
- **npm/yarn:** Latest version
- **SQLite** (or MySQL/PostgreSQL if preferred)

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd scalable-commerce-platform
```

### 2. Backend Setup (Laravel)

```bash
# Navigate to backend directory
cd backend

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database file (if using SQLite)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed the database 
php artisan db:seed

# Link storage (if needed)
php artisan storage:link
```

### 3. Frontend Setup (React)

```bash
# Navigate to frontend directory
cd frontend

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Update the API URL in .env if needed
# VITE_API_BASE_URL=http://localhost:8000/api
```

## Running the Application

### Start Backend Server

```bash
# From the backend directory
cd backend

# Start Laravel development server
php artisan serve
# Backend will run on http://localhost:8000

# In a separate terminal, start the queue worker
php artisan queue:work
```

### Start Frontend Server

```bash
# From the frontend directory
cd frontend

# Start Vite development server
npm run dev
# Frontend will run on http://localhost:5173
```

### Access the Application

- **Frontend:** http://localhost:5173
- **Backend API:** http://localhost:8000/api

## Environment Configuration

### Backend (.env)

Key configuration variables:

```env
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# For MySQL/PostgreSQL, uncomment and configure:
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=your_database
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

### Frontend (.env)

```env
VITE_API_BASE_URL=http://localhost:8000/api
```

## Project Structure

```
scalable-commerce-platform/
├── backend/                      # Laravel Backend
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/         # API Controllers
│   │   │   └── Requests/        # Form Request Validators
│   │   ├── Jobs/                # Queue Jobs
│   │   ├── Models/              # Eloquent Models
│   │   ├── Services/            # Business Logic Services
│   │   └── Validators/          # Custom Validators
│   ├── database/
│   │   └── migrations/          # Database Migrations
│   ├── routes/
│   │   └── api.php             # API Routes
│   └── ...
│
└── frontend/                    # React Frontend
    ├── src/
    │   ├── components/          # React Components
    │   │   ├── collections/    # Collection-related components
    │   │   ├── common/         # Shared components
    │   │   └── layout/         # Layout components
    │   ├── context/            # React Context providers
    │   ├── pages/              # Page components
    │   ├── services/           # API service layer
    │   ├── theme/              # MUI theme configuration
    │   ├── App.jsx             # Main app component
    │   └── main.jsx            # Entry point
    └── ...
```

## API Endpoints

### Merchants
- `GET /api/merchants` - List all merchants

### Collections
- `GET /api/collections` - List collections (paginated)
- `POST /api/collections` - Create collection
- `GET /api/collections/{id}` - Get collection details
- `PUT /api/collections/{id}` - Update collection
- `DELETE /api/collections/{id}` - Delete collection
- `POST /api/collections/{id}/upload-csv` - Upload CSV for bulk operations
- `GET /api/collections/{id}/operations` - List operations
- `GET /api/collections/{id}/operations/{operationId}` - Get operation status
- `GET /api/collections/{id}/operations/{operationId}/errors` - Get operation errors

### Imports
- `GET /api/imports` - List import jobs
- `POST /api/imports` - Create import job
- `GET /api/imports/{id}` - Get import details
- `GET /api/imports/{id}/logs` - Get import logs

## Development Workflow

### Backend Development

```bash
# Run tests
php artisan test

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Create new migration
php artisan make:migration create_table_name

# Create new model
php artisan make:model ModelName -m

# Create new controller
php artisan make:controller Api/ControllerName
```

### Frontend Development

```bash
# Run linter
npm run lint

# Build for production
npm run build

# Preview production build
npm run preview
```

## Queue Management

The application uses Laravel queues for processing bulk operations:

```bash
# Start queue worker
php artisan queue:work

# Process specific queue
php artisan queue:work --queue=high,default

# Monitor failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}
```

## Setting Up Supervisor (Production)

Supervisor is a process control system that keeps your Laravel queue workers running continuously in production. It automatically restarts workers if they fail or stop unexpectedly.

### Why Use Supervisor?

- Automatically restarts queue workers if they crash
- Keeps workers running in the background
- Manages multiple worker processes
- Provides easy start/stop/restart controls
- Logs worker output for debugging

### Installation

#### Ubuntu/Debian

```bash
sudo apt-get update
sudo apt-get install supervisor
```

#### CentOS/RHEL

```bash
sudo yum install supervisor
sudo systemctl enable supervisord
sudo systemctl start supervisord
```

#### macOS (using Homebrew)

```bash
brew install supervisor
brew services start supervisor
```

### Configuration

#### 1. Create Supervisor Configuration File

Create a new configuration file for your Laravel queue worker:

```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

#### 2. Add Configuration

Add the following configuration (adjust paths according to your setup):

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/scalable-commerce-platform/backend/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/scalable-commerce-platform/backend/storage/logs/worker.log
stopwaitsecs=3600
```

#### Configuration Options Explained:

- **process_name**: Unique name for each worker process
- **command**: The artisan command to run (adjust path to your backend directory)
- **autostart**: Start workers when Supervisor starts
- **autorestart**: Restart workers if they die
- **stopasgroup**: Kill all child processes when stopping
- **killasgroup**: Send kill signal to all child processes
- **user**: User to run the process (usually `www-data` or `nginx`)
- **numprocs**: Number of worker processes (adjust based on load)
- **redirect_stderr**: Redirect error output to stdout
- **stdout_logfile**: Log file location
- **stopwaitsecs**: Seconds to wait before killing process (should match max-time)

#### 3. Update Configuration Paths

Replace the following placeholders:

```bash
# Replace /path/to/scalable-commerce-platform with actual path
# For example:
command=php /var/www/scalable-commerce-platform/backend/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
stdout_logfile=/var/www/scalable-commerce-platform/backend/storage/logs/worker.log

# Update user if different from www-data
user=your-web-user
```

### Managing Supervisor

#### Reload Configuration

After creating or modifying the configuration file:

```bash
# Reread configuration files
sudo supervisorctl reread

# Update Supervisor with new configuration
sudo supervisorctl update
```

#### Start Workers

```bash
# Start all workers
sudo supervisorctl start laravel-worker:*

# Start specific worker
sudo supervisorctl start laravel-worker:laravel-worker_00
```

#### Stop Workers

```bash
# Stop all workers
sudo supervisorctl stop laravel-worker:*

# Stop specific worker
sudo supervisorctl stop laravel-worker:laravel-worker_00
```

#### Restart Workers

```bash
# Restart all workers (useful after code deployment)
sudo supervisorctl restart laravel-worker:*
```

#### Check Status

```bash
# Check status of all processes
sudo supervisorctl status

# Check specific program status
sudo supervisorctl status laravel-worker:*
```

### Monitor Logs

View worker logs in real-time:

```bash
# View supervisor logs
sudo tail -f /path/to/scalable-commerce-platform/backend/storage/logs/worker.log

# View Laravel logs
sudo tail -f /path/to/scalable-commerce-platform/backend/storage/logs/laravel.log
```

### Multiple Queue Configuration

If you need different workers for different queues (e.g., high priority, default):

```ini
[program:laravel-worker-high]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/scalable-commerce-platform/backend/artisan queue:work database --queue=high --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/scalable-commerce-platform/backend/storage/logs/worker-high.log
stopwaitsecs=3600

[program:laravel-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/scalable-commerce-platform/backend/artisan queue:work database --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/scalable-commerce-platform/backend/storage/logs/worker-default.log
stopwaitsecs=3600
```

### Deployment Best Practices

When deploying new code:

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
cd backend
composer install --optimize-autoloader --no-dev

# 3. Run migrations
php artisan migrate --force

# 4. Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart queue workers to pick up new code
sudo supervisorctl restart laravel-worker:*
```

### Troubleshooting Supervisor

#### Check if Supervisor is Running

```bash
sudo systemctl status supervisor
```

#### View Supervisor Logs

```bash
sudo tail -f /var/log/supervisor/supervisord.log
```

#### Common Issues

1. **Workers not starting:**
   - Check file permissions: `sudo chown -R www-data:www-data /path/to/backend/storage`
   - Verify PHP path in command: `which php`
   - Check configuration syntax: `sudo supervisorctl reread`

2. **Workers keep restarting:**
   - Check Laravel logs for errors
   - Verify database connection
   - Ensure proper permissions on storage directory

3. **Changes not taking effect:**
   - Always run `supervisorctl reread` and `supervisorctl update` after config changes
   - Restart workers: `sudo supervisorctl restart laravel-worker:*`

## Building for Production

### Backend

```bash
cd backend

# Optimize for production
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set appropriate permissions
chmod -R 755 storage bootstrap/cache
```

### Frontend

```bash
cd frontend

# Build production bundle
npm run build

# Output will be in dist/ directory
```

## Troubleshooting

### Common Issues

1. **Queue not processing:**
   - Ensure queue worker is running: `php artisan queue:work`
   - Check QUEUE_CONNECTION in .env

2. **CORS errors:**
   - Verify backend URL in frontend .env
   - Check Laravel CORS configuration

3. **Database errors:**
   - Run migrations: `php artisan migrate`
   - Check database connection in backend .env

4. **Frontend not loading:**
   - Verify API URL in frontend .env
   - Check if backend server is running



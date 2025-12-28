# Scalable Commerce Platform - Technical Documentation

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Design Decisions](#design-decisions)
3. [Setup Instructions](#setup-instructions)
4. [Current Implementation](#current-implementation)
5. [Scaling Strategy](#scaling-strategy)
6. [Performance Optimization](#performance-optimization)
7. [Monitoring and Observability](#monitoring-and-observability)

---

## Architecture Overview

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         Client Layer                            │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  React SPA (Vite)                                         │  │
│  │  - Material UI Components                                 │  │
│  │  - React Query (State Management & Caching)              │  │
│  │  - Axios (HTTP Client)                                    │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                         HTTPS/REST API
                              │
┌─────────────────────────────────────────────────────────────────┐
│                      Application Layer                          │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Laravel 12 Backend                                       │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐         │  │
│  │  │ Controllers│  │  Services  │  │ Validators │         │  │
│  │  └────────────┘  └────────────┘  └────────────┘         │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐         │  │
│  │  │   Models   │  │   Jobs     │  │  Requests  │         │  │
│  │  └────────────┘  └────────────┘  └────────────┘         │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                    ┌─────────┴─────────┐
                    │                   │
┌───────────────────▼─────┐   ┌────────▼──────────────────────┐
│   Database Layer        │   │   Queue Layer                 │
│  ┌──────────────────┐   │   │  ┌────────────────────────┐  │
│  │ MySQL/PostgreSQL │   │   │  │  Laravel Queue System  │  │
│  │ - Merchants      │   │   │  │  - Collection Jobs     │  │
│  │ - Products       │   │   │  │  - Import Jobs         │  │
│  │ - Collections    │   │   │  │  - Retry Logic         │  │
│  │ - Jobs/Logs      │   │   │  └────────────────────────┘  │
│  └──────────────────┘   │   │                               │
│                         │   │  Supervisor (Process Manager) │
└─────────────────────────┘   └───────────────────────────────┘
```

### System Components

#### 1. Frontend (React + Vite)

- **Single Page Application (SPA)** for fast, responsive user experience
- **React Query** for server state management with automatic caching and background refetching
- **Material-UI** for consistent, accessible UI components
- **Context API** for merchant selection state management

#### 2. Backend (Laravel)

- **RESTful API** following Laravel best practices
- **Service Layer** for business logic separation
- **Job Queue System** for asynchronous processing
- **Request Validation** using Form Requests and custom validators
- **Eloquent ORM** for database abstraction

#### 3. Data Layer

- **Relational Database** (MySQL/PostgreSQL/SQLite)
- **Database/ Redis Queue** for job management
- **Migration System** for version control of database schema

#### 4. Background Processing

- **Queue Workers** managed by Supervisor
- **Async Job Processing** for bulk operations
- **Error Logging** for failed operations
- **Retry Mechanism** for resilience

---

## Design Decisions

### 1. **Asynchronous Processing for Bulk Operations**

**Decision:** Use Laravel's queue system for all bulk operations (CSV uploads, collection operations)

**Rationale:**

- Prevents request timeouts for large datasets
- Allows users to continue working while operations process
- Provides better error handling and retry mechanisms
- Enables horizontal scaling of workers

**Implementation:**

- CSV uploads return immediately with job ID
- Background workers process files in chunks
- Real-time status updates via polling
- Detailed error logging for failed rows

### 2. **Chunk-Based Processing**

**Decision:** Process large datasets in configurable chunks (default 1000 records)

**Rationale:**

- Prevents memory exhaustion
- Allows for progress tracking
- Easier error recovery (only failed chunks need retry)
- Better database connection management

**Implementation:**

```php
// Example from CollectionCsvProcessor
public function processCsv(string $filePath, string $operationType)
{
    $chunkSize = config('app.csv_chunk_size', 1000);

    foreach ($this->readCsvInChunks($filePath, $chunkSize) as $chunk) {
        $this->processChunk($chunk, $operationType);
    }
}
```

### 3. **Database-Backed Queue**

**Decision:** Use database driver for queues initially

**Rationale:**

- Simple setup, no additional infrastructure
- Persistent job storage (survives server restarts)
- Easy monitoring via database queries
- Suitable for moderate load (< 1000 jobs/minute)

**Trade-offs:**

- Not as performant as Redis/RabbitMQ for high throughput
- Additional database load
- Plan to migrate to Redis for production scale

### 4. **Optimistic Concurrency Control**

**Decision:** Use database transactions with row-level locking for concurrent operations

**Rationale:**

- Prevents race conditions during simultaneous operations
- Maintains data integrity
- Standard SQL feature, no additional dependencies

**Implementation:**

```php
DB::transaction(function () use ($collection, $productIds) {
    // Row-level lock
    Collection::where('id', $collection->id)->lockForUpdate()->first();

    // Perform operations
    $this->attachProducts($collection, $productIds);
});
```

### 5. **Multi-Tenant Architecture (Merchant Isolation)**

**Decision:** Partition data by merchant_id, not separate databases

**Rationale:**

- Simpler infrastructure management
- Easier cross-merchant analytics
- Lower operational overhead
- Adequate for current scale

**Security:**

- All queries scoped by merchant_id
- API routes protected with merchant context
- Frontend enforces merchant selection

### 6. **CSV as Import Format**

**Decision:** Support CSV for bulk operations

**Rationale:**

- Universal format, supported by all systems
- Easy to generate from spreadsheets
- Low barrier to entry for users
- Streaming-friendly for large files

**Validation:**

- Header validation before processing
- Row-by-row validation with detailed errors
- Strict validation rules (SKU format, required fields)

### 7. **RESTful API Design**

**Decision:** Follow RESTful conventions for all API endpoints

**Rationale:**

- Industry standard, well understood
- Easy to document and test
- Client-agnostic (web, mobile, third-party integrations)
- Leverages HTTP semantics (status codes, methods)

**API Structure:**

```
GET    /api/collections              # List
POST   /api/collections              # Create
GET    /api/collections/{id}         # Show
PUT    /api/collections/{id}         # Update
DELETE /api/collections/{id}         # Delete
POST   /api/collections/{id}/upload-csv  # Bulk operation
```

### 8. **Error Logging and Observability**

**Decision:** Detailed error logging with structured data

**Rationale:**

- Debugging bulk operations requires granular error information
- Users need to know which specific rows failed
- Enables data quality improvements

**Implementation:**

- `collection_operation_logs` table stores errors by row
- Error messages include row number, field, and reason
- Frontend displays paginated error logs

---

## Setup Instructions

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+ or PostgreSQL 13+ (SQLite for development)
- Supervisor (for production)

### Local Development Setup

#### 1. Clone and Install

```bash
# Clone repository
git clone <repo-url>
cd scalable-commerce-platform

# Backend setup
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed

# Frontend setup
cd ../frontend
npm install
cp .env.example .env
```

#### 2. Configure Environment

**Backend (.env):**

```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
CACHE_STORE=database
```

**Frontend (.env):**

```env
VITE_API_BASE_URL=http://localhost:8000/api
```

#### 3. Run Development Servers

```bash
# Terminal 1: Backend
cd backend
php artisan serve

# Terminal 2: Queue Worker
cd backend
php artisan queue:work

# Terminal 3: Frontend
cd frontend
npm run dev
```

### Production Deployment

See [README.md](./README.md) for detailed production setup with Supervisor.

---

## Current Implementation

### Database Schema

#### Core Tables

**merchants**

- `id`: Primary key
- `name`: Merchant name
- `created_at`, `updated_at`: Timestamps

**products**

- `id`: Primary key
- `merchant_id`: Foreign key to merchants
- `sku`: Unique product identifier (per merchant)
- `title`: Product name
- `collection_id`: Nullable foreign key
- `created_at`, `updated_at`: Timestamps
- **Index:** `(merchant_id, sku)` for fast lookups

**collections**

- `id`: Primary key
- `merchant_id`: Foreign key to merchants
- `name`: Collection name
- `description`: Optional description
- `created_at`, `updated_at`: Timestamps
- **Index:** `merchant_id`

**collection_operation_jobs**

- `id`: Primary key
- `collection_id`: Foreign key to collections
- `operation_type`: Enum (add_products, remove_products)
- `status`: Enum (pending, processing, completed, failed)
- `total_rows`: Total records in CSV
- `processed_rows`: Successfully processed
- `failed_rows`: Failed count
- `file_path`: S3/local path to CSV
- `created_at`, `updated_at`: Timestamps

**collection_operation_logs**

- `id`: Primary key
- `operation_job_id`: Foreign key to operation jobs
- `row_number`: CSV row number
- `sku`: Product SKU
- `error_message`: Validation/processing error
- `created_at`: Timestamp

### API Endpoints

All endpoints require merchant context via headers or session.

#### Collections API

| Method | Endpoint                                         | Description                  | Response                 |
|--------|--------------------------------------------------|------------------------------|--------------------------|
| GET    | `/api/collections`                               | List collections (paginated) | `{ data: [], meta: {} }` |
| POST   | `/api/collections`                               | Create collection            | `{ data: {} }`           |
| GET    | `/api/collections/{id}`                          | Get collection details       | `{ data: {} }`           |
| PUT    | `/api/collections/{id}`                          | Update collection            | `{ data: {} }`           |
| DELETE | `/api/collections/{id}`                          | Delete collection            | `{ message: '' }`        |
| POST   | `/api/collections/{id}/upload-csv`               | Upload CSV for bulk op       | `{ job_id: 123 }`        |
| GET    | `/api/collections/{id}/operations`               | List operations              | `{ data: [], meta: {} }` |
| GET    | `/api/collections/{id}/operations/{opId}`        | Get operation status         | `{ data: {} }`           |
| GET    | `/api/collections/{id}/operations/{opId}/errors` | Get errors                   | `{ data: [], meta: {} }` |

### Background Jobs

**ProcessCollectionOperationJob**

- Processes CSV files for bulk add/remove operations
- Updates job status in real-time
- Logs errors per row
- Supports retry on failure

**Job Flow:**

```
1. User uploads CSV
2. Validation (headers, file size)
3. Job created, returns job_id
4. Worker picks up job
5. Reads CSV in chunks (1000 rows)
6. For each chunk:
   - Validate rows
   - Process valid rows
   - Log errors for invalid rows
   - Update progress
7. Mark job complete/failed
```

### Frontend Architecture

**Pages:**

- `MerchantSelectionPage`: Initial merchant selection
- `DashboardPage`: Overview (placeholder)
- `CollectionListPage`: Browse collections, create new
- `CollectionDetailPage`: View/edit collection, bulk operations
- `ImportsPage`: List import jobs
- `ImportDetailPage`: View import status/logs

**Key Components:**

- `CollectionFormDialog`: Create/edit collections (Formik + Yup)
- `CollectionCsvUpload`: CSV upload UI with drag-drop
- `CollectionOperationsPanel`: Real-time operation status
- `MerchantSwitcher`: Change active merchant
- `ProtectedRoute`: Auth wrapper for routes

**State Management:**

- React Query for server state (auto-caching, refetching)
- Context API for merchant selection
- Local state for UI interactions

---

## Scaling Strategy

### Overview: Handling Enterprise Scale

**Target Metrics:**

- **100+ concurrent merchants**
- **1M+ products per merchant** (100M+ total products)
- **10,000+ collection operations per hour** (~3 ops/second)

### Phase 1: Database Optimization (0-10M products)

#### 1.1 Indexing Strategy

**Critical Indexes:**

```sql
-- Products table
CREATE INDEX idx_products_merchant_sku ON products (merchant_id, sku);
CREATE INDEX idx_products_collection ON products (collection_id);
CREATE INDEX idx_products_merchant_collection ON products (merchant_id, collection_id);

-- Collections table
CREATE INDEX idx_collections_merchant ON collections (merchant_id);

-- Operation jobs
CREATE INDEX idx_operation_jobs_status ON collection_operation_jobs (status, created_at);
CREATE INDEX idx_operation_jobs_collection ON collection_operation_jobs (collection_id, created_at);

-- Operation logs
CREATE INDEX idx_operation_logs_job ON collection_operation_logs (operation_job_id);
```

#### 1.2 Query Optimization

**Avoid N+1 Queries:**

```php
// Bad
$collections = Collection::where('merchant_id', $merchantId)->get();
foreach ($collections as $collection) {
    echo $collection->products->count(); // N+1 query
}

// Good
$collections = Collection::where('merchant_id', $merchantId)
    ->withCount('products')
    ->get();
```

**Use Database Pagination:**

```php
// Cursor pagination for large datasets
Product::where('merchant_id', $merchantId)
    ->orderBy('id')
    ->cursorPaginate(1000);
```

#### 1.3 Database Configuration

**MySQL Configuration (my.cnf):**

```ini
[mysqld]
# Connection pool
max_connections = 500

# Buffer pool (70-80% of RAM for dedicated DB server)
innodb_buffer_pool_size = 8G
innodb_buffer_pool_instances = 8

# Log file size
innodb_log_file_size = 512M

# Query cache (for read-heavy workloads)
query_cache_size = 256M
query_cache_type = 1

# Connection timeout
wait_timeout = 28800
```

### Phase 2: Application Layer Scaling (10M-50M products)

#### 2.1 Horizontal Scaling - Application Servers

**Load Balancer Setup:**

```
                    ┌──────────────┐
                    │ Load Balancer│
                    │  (Nginx/ALB) │
                    └───────┬──────┘
                            │
           ┌────────────────┼────────────────┐
           │                │                │
    ┌──────▼─────┐   ┌─────▼──────┐  ┌─────▼──────┐
    │ App Server │   │ App Server │  │ App Server │
    │   Node 1   │   │   Node 2   │  │   Node 3   │
    └──────┬─────┘   └─────┬──────┘  └─────┬──────┘
           │                │                │
           └────────────────┼────────────────┘
                            │
                    ┌───────▼────────┐
                    │  Shared Cache  │
                    │    (Redis)     │
                    └────────────────┘
```

**Session Management:**

```php
// .env
SESSION_DRIVER=redis
CACHE_DRIVER=redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis-cluster.example.com
```

#### 2.2 Caching Strategy

**Multi-Layer Cache:**

```php
// 1. Query Result Cache (5 minutes)
$collection = Cache::remember(
    "collection:{$id}",
    300,
    fn() => Collection::with('merchant')->find($id)
);

// 2. Computed Values Cache (1 hour)
$productCount = Cache::remember(
    "collection:{$id}:product_count",
    3600,
    fn() => Product::where('collection_id', $id)->count()
);

// 3. API Response Cache (30 seconds for list views)
Route::get('/collections', [CollectionController::class, 'index'])
    ->middleware('cache.response:30');
```

**Cache Invalidation:**

```php
// Event-driven cache invalidation
class CollectionUpdated
{
    public function handle(Collection $collection)
    {
        Cache::forget("collection:{$collection->id}");
        Cache::forget("collection:{$collection->id}:product_count");
        Cache::tags(['merchant:' . $collection->merchant_id])->flush();
    }
}
```

#### 2.3 API Rate Limiting

**Protect Against Abuse:**

```php
// routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/collections/{id}/upload-csv', ...);
});

// Custom rate limits per merchant tier
Route::middleware(['throttle:rate_limit,1'])->group(function () {
    // Resolved from merchant tier
});
```

### Phase 3: Queue System Optimization (1K-10K ops/hour)

#### 3.1 Migration to Redis Queue

**Why Redis:**

- 10-100x faster than database queue
- Supports priorities
- Better for high-throughput workloads

**Configuration:**

```php
// .env
QUEUE_CONNECTION=redis
REDIS_QUEUE_HOST=redis-queue.example.com

// config/queue.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'queue',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 3600,
    'block_for' => null,
],
```

#### 3.2 Queue Prioritization

**Multiple Queues:**

```ini
# Supervisor config
[program:laravel-worker-critical]
command = php artisan queue:work redis --queue=critical --sleep=1 --tries=3
numprocs = 4

[program:laravel-worker-high]
command = php artisan queue:work redis --queue=high,default --sleep=3 --tries=3
numprocs = 8

[program:laravel-worker-low]
command = php artisan queue:work redis --queue=low --sleep=5 --tries=3
numprocs = 4
```

**Job Dispatching:**

```php
// Critical operations (user-initiated)
ProcessCollectionOperationJob::dispatch($job)
    ->onQueue('high');

// Background cleanup
CleanupOldLogsJob::dispatch()
    ->onQueue('low');
```

#### 3.3 Worker Auto-Scaling

**AWS Auto Scaling Policy:**

```yaml
# CloudWatch Metrics
- QueueDepth > 1000: Scale up workers
- QueueDepth < 100: Scale down workers
- CPU > 70%: Scale up
```

**Kubernetes Horizontal Pod Autoscaler:**

```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: queue-worker
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: laravel-queue-worker
  minReplicas: 5
  maxReplicas: 50
  metrics:
    - type: External
      external:
        metric:
          name: redis_queue_length
        target:
          type: AverageValue
          averageValue: "1000"
```

### Phase 4: Database Sharding (50M-1B products)

#### 4.1 Horizontal Sharding by Merchant

**Shard Key:** `merchant_id`

**Benefits:**

- Perfect data isolation
- Linear scalability
- Independent scaling per shard
- No cross-shard joins needed

**Implementation:**

```php
// config/database.php
'connections' => [
    'shard_1' => [
        'driver' => 'mysql',
        'host' => 'shard-1.example.com',
        'database' => 'commerce_shard_1',
    ],
    'shard_2' => [
        'driver' => 'mysql',
        'host' => 'shard-2.example.com',
        'database' => 'commerce_shard_2',
    ],
    // ... more shards
],

// app/Services/ShardResolver.php
class ShardResolver
{
    public function getShardForMerchant(int $merchantId): string
    {
        $shardCount = config('database.shard_count');
        $shardNumber = ($merchantId % $shardCount) + 1;
        return "shard_{$shardNumber}";
    }
}

// Usage
$shard = $shardResolver->getShardForMerchant($merchantId);
DB::connection($shard)->table('products')->where(...)->get();
```

**Routing Table:**

```
Merchant 1-1000   → Shard 1
Merchant 1001-2000 → Shard 2
Merchant 2001-3000 → Shard 3
...
```

#### 4.2 Read Replicas

**Master-Slave Replication:**

```php
// Write to master
DB::connection('master')->table('products')->insert(...);

// Read from replicas
DB::connection('replica')->table('products')->where(...)->get();

// Automatic read/write splitting
'mysql' => [
    'read' => [
        'host' => ['replica1.example.com', 'replica2.example.com'],
    ],
    'write' => [
        'host' => 'master.example.com',
    ],
],
```

### Phase 5: Advanced Optimizations (1B+ products)

#### 5.1 Search Index (Elasticsearch)

**For fast product search across millions of records:**

```php
// Index products in Elasticsearch
class ProductIndexer
{
    public function index(Product $product)
    {
        Elasticsearch::index([
            'index' => 'products',
            'id' => $product->id,
            'body' => [
                'merchant_id' => $product->merchant_id,
                'sku' => $product->sku,
                'title' => $product->title,
                'collection_id' => $product->collection_id,
            ],
        ]);
    }

    public function search(string $query, int $merchantId)
    {
        return Elasticsearch::search([
            'index' => 'products',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['match' => ['title' => $query]],
                            ['term' => ['merchant_id' => $merchantId]],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
```

#### 5.2 Object Storage for CSV Files

**Use S3/MinIO instead of local storage:**

```php
// config/filesystems.php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
    ],
],

// Upload to S3
$path = Storage::disk('s3')->put('csv-uploads', $file);

// Stream from S3 (memory-efficient)
$stream = Storage::disk('s3')->readStream($path);
foreach ($this->parseCsvStream($stream) as $row) {
    // Process row
}
```

#### 5.3 CDN for Static Assets

**Frontend distribution:**

```
┌──────────┐
│  Browser │
└────┬─────┘
     │
     ▼
┌──────────┐      Cache Miss     ┌────────────┐
│   CDN    │ ──────────────────► │ S3 Bucket  │
│ (CloudFront)                    │ (Origin)   │
└──────────┘                      └────────────┘
```

**Configuration:**

```env
# .env
ASSET_URL=https://cdn.example.com
```

#### 5.4 Database Connection Pooling

**PgBouncer/ProxySQL:**

```
┌──────────────┐
│ App Servers  │
│ (100 conns)  │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│  PgBouncer   │  Pool: 10 connections
│  (Pool)      │  per database
└──────┬───────┘
       │
       ▼
┌──────────────┐
│  PostgreSQL  │
│  (10 conns)  │
└──────────────┘
```

**Benefits:**

- Reduce database connections
- Faster connection times
- Better resource utilization

### Phase 6: Monitoring & Observability

#### 6.1 Metrics to Track

**Application Metrics:**

- Request rate (req/s)
- Response time (p50, p95, p99)
- Error rate
- Queue depth
- Job processing time

**Database Metrics:**

- Query execution time
- Connection pool utilization
- Cache hit ratio
- Slow query log

**Infrastructure Metrics:**

- CPU, Memory, Disk I/O
- Network throughput
- Worker count

#### 6.2 Tooling

**APM (Application Performance Monitoring):**

- New Relic / DataDog / Sentry
- Laravel Telescope (dev/staging)

**Logging:**

```php
// Structured logging
Log::info('Collection operation started', [
    'merchant_id' => $merchantId,
    'collection_id' => $collectionId,
    'operation_type' => $operationType,
    'row_count' => $rowCount,
]);
```

**Centralized Logging:**

- ELK Stack (Elasticsearch, Logstash, Kibana)
- CloudWatch Logs
- Papertrail

**Alerting:**

- Queue depth > 10,000
- Error rate > 5%
- Database connections > 80%
- Disk space < 20%

---

## Performance Optimization

### Current Bottlenecks and Solutions

#### 1. CSV Parsing Performance

**Problem:** Reading large CSV files (1M+ rows) consumes memory

**Solution:**

```php
// Stream-based parsing
public function readCsvInChunks(string $filePath, int $chunkSize)
{
    $file = fopen($filePath, 'r');
    $header = fgetcsv($file);

    while (!feof($file)) {
        $chunk = [];
        for ($i = 0; $i < $chunkSize && !feof($file); $i++) {
            $row = fgetcsv($file);
            if ($row) {
                $chunk[] = array_combine($header, $row);
            }
        }
        yield $chunk;
    }

    fclose($file);
}
```

#### 2. Bulk Database Operations

**Problem:** Individual inserts/updates are slow

**Solution:**

```php
// Batch inserts (1000x faster)
DB::table('products')->insert($batchData);

// Batch updates using CASE statement
DB::update("
    UPDATE products
    SET collection_id = CASE
        WHEN id = ? THEN ?
        WHEN id = ? THEN ?
        ...
    END
    WHERE id IN (?)
", $bindings);

// Use upsert for insert/update
DB::table('products')->upsert(
    $data,
    ['merchant_id', 'sku'], // Unique keys
    ['title', 'collection_id'] // Update fields
);
```

#### 3. Frontend Performance

**Code Splitting:**

```jsx
// React lazy loading
const CollectionDetailPage = lazy(() => import('./pages/CollectionDetailPage'));

<Suspense fallback={<Loading/>}>
    <CollectionDetailPage/>
</Suspense>
```

**Virtual Scrolling:**

```jsx
// For large lists (1000+ items)
import {FixedSizeList} from 'react-window';

<FixedSizeList
    height={600}
    itemCount={products.length}
    itemSize={50}
>
    {ProductRow}
</FixedSizeList>
```

---

## Monitoring and Observability

### Health Checks

**API Health Endpoint:**

```php
// routes/api.php
Route::get('/health', function () {
    return [
        'status' => 'ok',
        'database' => DB::connection()->getDatabaseName(),
        'queue' => Queue::size(),
        'cache' => Cache::has('health_check'),
    ];
});
```

### Performance Benchmarks

**Expected Performance (current setup):**

- Simple API requests: < 100ms
- Collection listing: < 200ms
- CSV upload (10K rows): 1-2 minutes
- Collection operation (10K products): 2-5 minutes

**Scaled Performance (Phase 5):**

- Simple API requests: < 50ms
- Collection listing: < 100ms
- CSV upload (1M rows): 10-20 minutes
- Collection operation (1M products): 15-30 minutes

---

## Conclusion

This architecture supports current requirements and provides a clear path to scale from thousands to billions of
products. Key principles:

1. **Start Simple:** Database queue, single server
2. **Scale Incrementally:** Add Redis, read replicas, caching
3. **Horizontal Scaling:** Add servers, not bigger servers
4. **Data Partitioning:** Shard by merchant for linear scaling
5. **Async Everything:** Queue all bulk operations
6. **Monitor & Optimize:** Measure before optimizing

The platform can handle 100+ concurrent merchants and 10,000+ operations/hour with Phase 3 optimizations, and scale to
1M+ products per merchant with Phase 4-5 implementations.

# Infrastructure Contract Document

This document serves as the single source of truth for the runtime infrastructure, dependency policy, and hosting compatibility for the CBT Enterprise Platform. All downstream agents must adhere strictly to these standards.

## 1. Hosting Strategy

The platform must support two primary hosting environments: **Shared Hosting** and **VPS/Dedicated Server**. The codebase must be agnostic to the environment, using abstraction layers for caching, queuing, and storage.

### 1.1 Shared Hosting Constraints
-   **No System Daemons**: Cannot rely on Supervisor, Redis, or long-running processes.
-   **Cron Jobs**: Only standard cron jobs are available for background tasks.
-   **Database Usage**: MySQL is the only persistent storage available.
-   **File System**: Local file system is available but may be ephemeral in some configurations (though typically persistent in shared hosting).
-   **Fallback Mechanisms**:
    -   **Cache**: File-based or Database-based.
    -   **Queue**: Database-based queue processed via Cron.
    -   **Session**: File-based or Database-based.

### 1.2 VPS / Dedicated Server Capabilities
-   **System Daemons**: Supervisor is available for managing worker processes.
-   **Redis**: Available for high-performance caching and queuing.
-   **Background Workers**: Long-running PHP processes for queue consumption.
-   **Docker**: Optional full-stack containerization.

## 2. Composer Dependency Contract

All dependencies must be managed via Composer.

### 2.1 Required Packages
These packages are mandatory for the core functionality and must be present in all environments.
-   `php`: `^8.2` (Strict typing enabled)
-   `firebase/php-jwt`: `^6.0` (JWT Authentication)
-   `nikic/fast-route`: `^1.3` (Routing)
-   `vlucas/phpdotenv`: `^5.5` (Environment Variable Management)
-   `ramsey/uuid`: `^4.7` (UUID Generation for Idempotency)
-   `symfony/validator`: `^6.3` (Input Validation)
-   `predis/predis`: `^2.2` (Redis Client - Required in `composer.json` but usage is conditional based on config)

### 2.2 Dev Dependencies
-   `phpunit/phpunit`: `^10.0` (Testing)
-   `phpstan/phpstan`: `^1.10` (Static Analysis)

## 3. Routing Strategy

### 3.1 Fast-Route Configuration
-   **Entry Point**: Single entry point at `public/index.php`.
-   **Dispatcher**: `FastRoute\simpleDispatcher` with caching enabled in production (`FastRoute\cachedDispatcher`).
-   **Method Support**: GET, POST, PUT, DELETE, PATCH, OPTIONS.

### 3.2 Middleware Architecture
-   **Pipeline**: Request -> [Global Middleware] -> [Route Middleware] -> Controller -> Response.
-   **Implementation**: PSR-15 compatible middleware stack (or a lightweight equivalent if not using a full framework).
-   **Core Middleware**:
    -   `CorsMiddleware`: Handles CORS headers.
    -   `AuthMiddleware`: Validates JWT (if route is protected).
    -   `RateLimitMiddleware`: Enforces rate limits based on IP/User.

## 4. Authentication Strategy

### 4.1 JWT Policy
-   **Algorithm**: HS256 (HMAC SHA-256).
-   **Structure**:
    -   `iss`: Issuer (e.g., CBT Platform)
    -   `sub`: User ID
    -   `role`: User Role (student, teacher, admin)
    -   `iat`: Issued At
    -   `exp`: Expiration Time
-   **Lifespan**:
    -   Access Token: 15 minutes (`900` seconds).
    -   Refresh Token: 24 hours (`86400` seconds).

### 4.2 OAuth Integration (Connected Mode)
-   **Provider**: SMS (School Management System).
-   **Flow**: Authorization Code Grant.
-   **Sync**: On successful login, user details (Name, Class, Role) are synced/updated from SMS.

## 5. Caching Strategy

The application must use an abstraction layer for caching to support multiple drivers.

### 5.1 Drivers
-   **File**: Default fallback for Shared Hosting. Stores serialized data in `storage/cache`.
-   **Database**: Option for Shared Hosting. Stores key-value pairs in a `cache` table.
-   **Redis**: Preferred for VPS. Uses `predis/predis`.

### 5.2 Abstraction Interface
```php
interface CacheInterface {
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
}
```

## 6. Queue Strategy

Asynchronous tasks (e.g., email sending, exam result processing) must be queued.

### 6.1 Drivers
-   **Database**: Default fallback for Shared Hosting. Jobs stored in `jobs` table.
    -   **Processing**: Processed via a Cron job running every minute (`* * * * * php /path/to/root/worker.php --stop-when-empty`).
-   **Redis**: Preferred for VPS. Uses Redis lists/sets.
    -   **Processing**: Processed via long-running worker processes managed by Supervisor.

### 6.2 Abstraction Interface
```php
interface QueueInterface {
    public function push(string $jobClass, array $data = []): string; // Returns Job ID
    public function pop(): ?JobInterface;
}
```

## 7. File Storage Strategy

### 7.1 Locations
-   **Uploads**: `storage/uploads` (Publicly accessible via symlink or serving script).
-   **Private**: `storage/app` (Not publicly accessible).

### 7.2 Drivers
-   **Local**: Standard file system storage.
-   **S3**: AWS S3 or compatible (MinIO, DigitalOcean Spaces).

### 7.3 Abstraction
-   Use a `StorageService` that delegates to the configured driver.

## 8. Mode Strategy

The system behavior is controlled by `SYSTEM_MODE` environment variable.

### 8.1 Modes
-   `standalone`: The CBT platform operates independently. Users are created and managed locally.
-   `connected`: The CBT platform relies on an external SMS for user identity and academic data.

### 8.2 Environment Variables
-   `SYSTEM_MODE=standalone|connected`
-   `SMS_API_BASE_URL` (Required if connected)
-   `SMS_CLIENT_ID` (Required if connected)
-   `SMS_CLIENT_SECRET` (Required if connected)

## 9. Rate Limiting Strategy

Rate limiting protects the system from abuse and ensures fair usage.

### 9.1 Rules
-   **Login**: 5 attempts per minute per IP.
-   **Exam Start**: 1 attempt per minute per User.
-   **Submission**: 1 request per 5 seconds per User.
-   **Result Push**: 10 requests per minute (System level).

### 9.2 Implementation
-   **Storage**: Redis (VPS) or Database/File (Shared Hosting).
-   **Fallback**: If Redis is unavailable, fall back to File/Database.

## 10. Performance Strategy

### 10.1 Optimization Guidelines
-   **Indexing**: Ensure all foreign keys and frequently queried columns (e.g., `email`, `exam_code`) are indexed.
-   **Preloading**: Load exam questions and options into cache at the start of an exam session to reduce DB hits.
-   **Bulk Inserts**: When submitting answers or importing questions, use bulk insert statements instead of individual inserts.
-   **Analytics**: Disable heavy analytics queries during active exam windows if system load is high.

## 11. Deployment Instructions

### 11.1 Shared Hosting
1.  Upload files via FTP/SFTP.
2.  Run `composer install --no-dev --optimize-autoloader`.
3.  Set up `.env` file from `.env.example`.
4.  Import database schema.
5.  Set up Cron job: `* * * * * php /path/to/root/scheduler.php >> /dev/null 2>&1`.

### 11.2 VPS (Ubuntu/Nginx)
1.  Clone repository.
2.  Run `composer install --no-dev --optimize-autoloader`.
3.  Set up `.env` file.
4.  Configure Nginx to serve `public/` directory.
5.  Set up Supervisor to run queue workers:
    ```ini
    [program:cbt-worker]
    process_name=%(program_name)s_%(process_num)02d
    command=php /path/to/root/worker.php
    autostart=true
    autorestart=true
    numprocs=2
    redirect_stderr=true
    stdout_logfile=/path/to/root/storage/logs/worker.log
    ```

## 12. Inter-Agent Communication Protocol

If downstream agents require clarification or cannot proceed due to infrastructure constraints, they must respond in the following format:

```
BLOCKED:
Required: [What is needed?]
Reason: [Why is it needed?]
Proposed Solution: [How can it be solved within constraints?]
```

Agents **cannot** modify this infrastructure contract. They must respect fallback and hosting constraints.

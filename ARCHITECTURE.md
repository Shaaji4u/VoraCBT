# Architecture Documentation

This document outlines the architectural patterns and standards for the CBT Enterprise Platform.

## 1. Project Structure

The project follows a modular structure under the `app/` directory:

-   `Core/`: Shared kernel components (Config, Container, Http, Exception, Service).
-   `Domain/` (Conceptual):
    -   `Question/`: Domain logic for questions.
    -   `Exam/`: Domain logic for exams.
    -   `Grading/`: Domain logic for grading.
    -   `Proctoring/`: Domain logic for proctoring.
-   `Integration/`: External system integrations (SMS, Payment).
-   `Infrastructure/`: Concrete implementations of infrastructure interfaces (Cache, Queue, Auth).
-   `Http/`:
    -   `Controllers/`: Domain-specific controllers.
    -   `Middleware/`: Application middleware.
-   `Storage/`: File storage abstraction.

## 2. Service Layer Patterns

Services encapsulate business logic and are isolated per domain.

### 2.1 Base Service
All services should extend `App\Core\Service\BaseService`.

### 2.2 Dependency Injection
Services are injected into controllers or other services using the `App\Core\Container\Container`.

```php
use App\Question\Service\QuestionService;

class QuestionController extends BaseController
{
    private QuestionService $service;

    public function __construct(QuestionService $service)
    {
        $this->service = $service;
    }
}
```

### 2.3 Domain Services
Located in `App/{Domain}/Service/`. Example: `App\Question\Service\QuestionService`.

## 3. Controller Patterns

Controllers handle HTTP requests and delegate logic to services.

### 3.1 Base Controller
All controllers must extend `App\Core\Controller\BaseController`.

### 3.2 API Response
Use `App\Core\Http\ApiResponse` for standardized JSON responses.

```php
// Success
return $this->json(['id' => 1], 200);

// Error
return $this->error('Invalid input', 400);
```

## 4. Middleware Patterns

Middleware intercepts requests for cross-cutting concerns.

-   `AuthMiddleware`: Validates JWT tokens.
-   `RoleMiddleware`: Enforces role-based access.
-   `RateLimitMiddleware`: Protects against abuse.
-   `LogMiddleware`: Logs request details.

## 5. Environment Configuration

Environment variables are managed via `App\Core\Config\Environment`.

```php
$env = Environment::getInstance();
$mode = $env->get('SYSTEM_MODE');
```

### 5.1 System Modes
-   `standalone`: Local user management.
-   `connected`: External SMS integration.

## 6. Error & Response Standards

### 6.1 Success Response
```json
{
    "data": {
        "id": 1,
        "name": "Example"
    },
    "status": 200
}
```

### 6.2 Error Response
```json
{
    "data": {
        "message": "Invalid input",
        "errors": ["Field X is required"]
    },
    "status": 400
}
```

## 7. Infrastructure Abstraction

Infrastructure components (Cache, Queue) are accessed via interfaces in `App\Infrastructure\`.

-   `CacheInterface`: `get`, `set`, `delete`, `clear`.
-   `QueueInterface`: `push`, `pop`.

Implementations are swapped based on the environment (Shared Hosting vs VPS).

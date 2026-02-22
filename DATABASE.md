# Database Documentation

## Overview

The database layer is designed to be agnostic, supporting both MySQL and PostgreSQL through Doctrine DBAL abstraction. It uses a migration-based approach for schema versioning.

## Schema Architecture

### Core Domains

1.  **Users & Access Control**
    -   `users`: Stores user credentials and profile.
    -   `roles`: Defined roles (Administrator, Teacher, Student).
    -   `permissions`: Granular permissions.
    -   `role_permissions`: Association between roles and permissions.

2.  **Questions & Content**
    -   `questions`: The main question repository. Stores the *current* version of a question.
        -   `type`: Polymorphic type (mcq, essay, etc.).
        -   `content`: JSON payload containing the question body, options, and answer key.
        -   `metadata`: JSON payload for tags, difficulty, subject, etc.
    -   `question_versions`: History of changes to questions.

3.  **Exams & Assessment**
    -   `exam_templates`: The blueprint for an exam (title, duration, settings).
    -   `exam_sections`: Logical grouping of questions within an exam.
    -   `exam_questions`: Linking table between sections and questions, with ordering and marks.

4.  **Sessions & Grading**
    -   `exam_sessions`: A specific instance of a user taking an exam. Tracks status and timing.
    -   `answers`: The user's response to a specific question in a session.
    -   `exam_results`: Final graded output for a session.
    -   `exam_analytics`: Aggregated metrics for an exam template.

5.  **Monitoring & Logs**
    -   `proctoring_sessions`: Security events and monitoring data for a session.
    -   `integration_logs`: Audit log of external system interactions (e.g., SMS sync).

## Indexes & Performance

-   **High Concurrency Paths**:
    -   `exam_sessions`: Indexed by `(user_id, exam_template_id)` for quick lookup of attempts.
    -   `answers`: Indexed by `exam_session_id` for retrieving full submission.
    -   `integration_logs`: Indexed by `idempotency_key` to prevent duplicate processing.

-   **Polymorphic Content**:
    -   Questions use `JSON` columns for `content` and `metadata` to allow flexible schemas for different question types (MCQ, Essay, etc.) without altering the table structure.

## Usage

### Prerequisites
-   PHP 8.2+
-   Composer dependencies installed (`composer install`)
-   Database configured in `.env`

### Migrations

Migrations are located in `database/migrations`.

**Run Migrations:**
```bash
php bin/migrate
```

**Rollback Migrations:**
```bash
php bin/rollback
```

### Seeding

Seeders are located in `database/seeds`.

**Run All Seeders:**
```bash
php bin/seed
```

**Run Specific Seeder:**
```bash
php bin/seed RolesAndPermissionsSeeder
```

## Adding New Tables

1.  Create a new file in `database/migrations` with the naming convention `YYYY_MM_DD_HHMMSS_ClassName.php`.
2.  Define the class extending `App\Core\Database\Migration\AbstractMigration`.
3.  Implement `up(Schema $schema)` and `down(Schema $schema)`.
4.  Run `php bin/migrate`.

## Environment Configuration

The system supports `mysql`, `pgsql`, and `sqlite` via the `DB_CONNECTION` environment variable.

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cbt_platform
DB_USERNAME=root
DB_PASSWORD=secret
```

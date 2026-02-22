📘 CBT ENTERPRISE PLATFORM — IMPLEMENTATION GUIDE

1️⃣ Project Overview

The CBT Enterprise Platform is a full-scale Computer-Based Testing system designed for schools, fully standalone but optionally integrated with the School Management System (SMS) via OAuth.

Key characteristics:

Multi-school, multi-class support

Multiple question types (MCQ, fill-in-the-blank, essay, numerical, matching, passage-based)

Sectional exams with per-section timers

Randomized question and option pools

Proctoring integration (future AI or human-based)

Detailed analytics and grading

Standalone or SMS-connected mode


Primary Goal:
Provide a secure, scalable, modular CBT system capable of handling thousands of concurrent users, ensuring exam integrity, and providing flexible integration with existing school systems.


---

2️⃣ System Architecture

2.1 High-Level Architecture

+---------------------+
                | Identity Service    |
                | (Local + OAuth)     |
                +---------------------+
                           |
        ------------------------------------------------
        |                                              |
+---------------------+                       +------------------+
|   CBT Core API      |                       |   SMS System     |
|---------------------|                       |------------------|
| Question Engine     |                       | Academic Engine  |
| Exam Engine         |                       | Student Records  |
| Grading & Analytics |                       | Term/Sessions    |
| Sync Adapter        | <---- REST ---->      | Results API      |
+---------------------+
        |
        v
+---------------------+
| CBT Database        |
+---------------------+

2.2 Domain Separation

Domain	Ownership & Responsibility

CBT	Questions, exams, grading, analytics, exam sessions
SMS (if connected)	Students, classes, subjects, terms, sessions, report cards


CBT never overrides SMS authority. It can operate standalone or connected.


---

3️⃣ Core Features

3.1 Question Engine

Supports polymorphic question types:

MCQ (single/multiple)

Fill-in-the-blank

Essay / structured answers

Numerical (Math)

Matching

Passage-based grouped questions

Image-based questions


Features:

Question CRUD

Versioning & archiving

Bulk upload

Subject/class filtering

Difficulty tagging


Storage: JSON-based payloads for flexibility



---

3.2 Exam Engine

Exam templates per class/subject

Sectional exams with per-section timers

Randomized question pools and option shuffling

Student exam session management:

Auto-save answers every 15–20 seconds

Server-authoritative timer

Attempt locking

Auto-submit on expiration


Exam configuration includes:

Duration, grading policy, total marks

Auto-publish results option

Section weight distribution




---

3.3 Grading & Analytics

Auto-grading:

MCQ, True/False, Fill-in-the-blank, Numerical


Manual grading queue:

Essay & structured answers

Teacher assignment


Analytics:

Class average scores

Question difficulty index

Performance per student/subject

Section-based analytics


Reporting:

Exam score breakdown

Exportable reports (PDF/CSV)




---

3.4 Integration with SMS (Optional)

OAuth client flow to SMS

Pull students, classes, subjects, and term/session

Push exam results to SMS

Retry and idempotency mechanism

Standalone mode disables sync operations



---

3.5 Proctoring Integration

Modes supported:

Webcam monitoring (video-based)

AI-based suspicious activity detection

Screen monitoring for VPS deployment


Configurable per exam template

Integration points:

Before exam start (identity verification)

During exam (live monitoring)

After exam (generate proctoring reports)




---

4️⃣ Infrastructure & Hosting

4.1 Hosting Compatibility

Environment	Strategy

Shared Hosting	File-based cache, DB queue, cron jobs, no daemons
VPS (Ubuntu/Nginx)	Redis cache & queue, supervisor workers, optional proctoring agent
Docker (optional)	Full-stack containerization


4.2 Stack

PHP 8.2+ (strict typing)

MySQL 8+

Bootstrap 5 + Vanilla JS (Fetch API)

JWT for authentication

Fast-route for routing

Composer-managed dependencies (required + optional)


4.3 Composer Dependencies

Required:

firebase/php-jwt → JWT auth

nikic/fast-route → Lightweight routing

vlucas/phpdotenv → Environment management

ramsey/uuid → Idempotency & unique IDs

symfony/validator → Input validation


Optional (VPS-only):

predis/predis → Redis cache/queue



---

5️⃣ Caching & Queueing

Cache abstraction: File / DB / Redis

Queue abstraction: DB queue for shared hosting, Redis queue for VPS

Cron jobs for DB queue processing in shared hosting

Async workers for VPS mode



---

6️⃣ Security & Performance

CSRF protection

JWT access + refresh tokens

Rate limiting on login, exam start, submission, result push

Server-side timers for exam sessions

Anti-cheating measures

Load optimization:

Preload exam templates and question payloads

Bulk insert answers

Disable heavy analytics during peak exam time




---

7️⃣ File Storage

/storage/uploads for images & attachments

Abstraction for future S3 or cloud storage

Sectional exam resources stored per exam template



---

8️⃣ Environment Variables (.env)

SYSTEM_MODE=standalone|connected
JWT_SECRET=xxxx
JWT_LIFETIME=900
JWT_REFRESH_LIFETIME=86400
SMS_API_BASE_URL=https://sms.example.com
CACHE_DRIVER=file|database|redis
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=root
DB_NAME=cbt_platform
UPLOAD_PATH=/storage/uploads


---

9️⃣ Project Structure

/app
  /Core
  /Domain
     /Question
     /Exam
     /Grading
     /Integration
     /Proctoring
  /Infrastructure
  /Http
  /Middleware
/config
/public
/storage

Modular design: each domain isolated

Communication between domains via services only



---

🔟 Advanced Features

Sectional exams: Per-section timers, weight distribution, auto-submit per section

Random question pools: Random selection from question bank, option shuffling

Proctoring integration: Video monitoring, AI-based activity analysis, reporting

Analytics dashboards: Real-time performance visualization

Bulk import/export: Questions and results



---

1️⃣1️⃣ Agent Responsibilities

Infrastructure Agent: Defines stack, hosting, caching, queue, routing, JWT

Architecture Agent: Folder & service structure

Database Agent: Schema, indices, versioning, archiving

Question Engine Agent: Question CRUD, polymorphic types, versioning

Exam Engine Agent: Exam session, timers, randomization, sectional exams

Grading & Analytics Agent: Auto/manual grading, statistics, dashboards

Integration Agent: SMS OAuth, result sync

Frontend Agent: Exam UI, teacher dashboards, responsive design

Security & QA Agent: CSRF, rate limiting, anti-cheating, load testing



---

1️⃣2️⃣ Key Principles

1. Domain separation – CBT manages exams, SMS manages students/classes


2. Extensible question types – JSON-based payloads


3. Secure, server-side timers – Never trust client-side time


4. Flexible deployment – Shared hosting fallback + VPS optimization


5. Performance-aware – Preload, bulk inserts, cache, async queues


6. Auditability – Versioning, archiving, and activity logging

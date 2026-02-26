# Eduvora Architecture & Folder Structure Design

This document outlines the high-level architecture, folder structure, and core design concepts of the Eduvora project. It serves as a reference for developers and a blueprint for structuring similar domain-driven, modular applications.

> **Note**: This document focuses strictly on structural organization and architectural patterns, agnostic of specific business logic.

---

## 1. High-Level Architecture Overview

The system is built on a **Modular, Plugin-Based Architecture** supported by a custom **Core Framework**. This approach ensures that domain-logic is highly cohesive, decoupled, and easily extensible.

### Core Concepts

*   **Custom Framework Core (`app/Core`)**: Provides the foundation of the application. It handles routing, dependency injection, event dispatching, logging, and middleware. It defines the contracts (Interfaces) that the rest of the application relies on.
*   **Plugin System (`app/Plugins`)**: The application is divided into distinct, independent modules called "Plugins" (e.g., HRM, Finance, Academic). Each plugin acts as a mini-application, encapsulating its own controllers, services, events, routing, and views.
*   **Service Providers**: Used extensively across both the Core and Plugins to bootstrap components, register services into the Dependency Injection (DI) container, and bind interfaces to their concrete implementations.
*   **Separation of Concerns (MVC + Services)**:
    *   **Routing**: Defines endpoints (Web and API separation).
    *   **Controllers**: Handle HTTP request/response orchestration.
    *   **Services**: Contain the core domain logic (kept out of controllers).
    *   **Views**: Presentation layer, separated into global layouts and plugin-specific templates.

---

## 2. Directory Structure

Below is the standard directory structure, explaining the purpose of each top-level and critical nested folder.

### Root Level Organization

```text
/eduvora
├── .agents/            # Automated agent configurations and workflows
├── .env / .env.example # Environment variable configurations
├── app/                # Application source code (Core framework & Plugins)
├── bootstrap/          # Application initialization and bootstrapping scripts
├── config/             # Global application configuration files
├── docs/               # Project documentation and architectural blueprints
├── logs/               # Application log files
├── packages/           # Internal/External vendor packages or custom integrations
├── public/             # Web server document root (entry point & static assets)
├── queue/              # Queue processing mechanisms and workers
├── resources/          # Global frontend assets (views, global components, web pages)
├── setup/              # Installation and database setup scripts
├── storage/            # Local file storage (uploads, caches, generated files)
├── tests/              # Automated testing suites (Unit, Integration, etc.)
└── vendor/             # Composer managed dependencies
```

### The `app/` Directory (The Heart of the System)

The `app` directory is cleanly split between the framework engine (`Core`) and the domain modules (`Plugins`).

```text
app/
├── Core/                       # The underlying framework engine
│   ├── Contracts/              # Interfaces defining core services (Router, Cache, Events, etc.)
│   ├── Database/               # Database connection and query building logic
│   ├── Events/                 # Global event dispatcher and core events
│   ├── Http/                   # HTTP layer (Middleware, Request/Response handling)
│   ├── Infrastructure/         # Infrastructure services (Cache, Storage integrations)
│   ├── Logging/                # Logging mechanisms
│   ├── Plugins/                # Plugin lifecycle management within the core
│   ├── Routing/                # Core routing engine
│   ├── Services/               # Core utility services
│   ├── *ServiceProvider.php    # Providers registering core components into the DI container
│   └── helpers.php             # Global utility functions
│
└── Plugins/                    # Domain-specific modules
    ├── Academic/
    ├── Communication/
    ├── Finance/
    ├── HRM/                    # Example Plugin Structure
    │   ├── Controllers/        # HR-specific HTTP controllers
    │   ├── Events/             # HR-specific events and listeners
    │   ├── Services/           # HR business logic and data processing
    │   ├── views/              # HR-specific view templates
    │   ├── routes.php          # Plugin specific routes (Web & API groups)
    │   ├── manifest.json       # Plugin metadata (Name, Version, Dependencies)
    │   ├── permissions.json    # RBAC definitions for the plugin
    │   └── HRMServiceProvider.php # Bootstraps the plugin into the core framework
    └── Students/
```

### The Configuration Layer (`config/`)

Centralized configuration files that govern the application's behavior.

```text
config/
├── app.php             # App name, environment, debug mode, timezone
├── cache.php           # Cache driver configurations (Redis, File, etc.)
├── database.php        # Database connection credentials
├── infrastructure.php  # External services or infrastructure settings
├── plugins.php         # Active plugins configuration and loading order
└── routes.php          # Global route definitions (if any, outside of plugins)
```

### The Presentation Layer (`public/`, `resources/`)

The frontend is organized to separate secure backend views from public-facing assets and pages.

```text
public/                 # Web server root
├── index.php           # The singular entry point for the application
├── router.php          # Fallback or lightweight routing script
└── assets/             # Static assets accessible via browser
    ├── plugins/        # CSS/JS scoped to specific plugins
    ├── css/            # Global stylesheets
    ├── js/             # Global JavaScript utilities
    └── images/         # Static imagery

resources/              # Uncompiled or server-side global assets
├── views/              # Global view templates (PHP/HTML)
│   ├── components/     # Reusable UI components (buttons, modals, tables)
│   ├── errors/         # Custom error pages (404, 500)
│   ├── layout/         # Base layout templates (app shell, sidebar, navbar)
│   └── marketing/      # Views for the public facing marketing site
└── web/                # Standalone or global web controllers/pages
    ├── login.php       # Authentication entry points
    ├── register.php
    ├── reset-password.php
    └── ...             # Other global pages unattached to a specific domain plugin
```

---

## 3. Key Conventions for New Projects

When adopting this architecture for a new project, adhere to the following conventions:

1.  **Strict Modularization**: Every new major feature or domain should be a self-contained Plugin. A Plugin should ideally be extractable without breaking the core system.
2.  **Contract-Driven Design**: The `Core` should expose `Contracts` (Interfaces) for essential services. Plugins should depend on these contracts rather than concrete implementations, allowing the core to swap implementations (e.g., swapping a File Logger for a Cloud Logger) without touching plugin code.
3.  **Route Isolation**: A Plugin must define its own routes in its `routes.php`, clearly separating Web endpoints (returning views) from API endpoints (returning JSON).
4.  **Service Layer**: Controllers must remain thin. All complex data manipulation, external API calls, and business rules must reside in the `Services/` directory of the respective plugin.
5.  **Manifest & Permissions**: Use `manifest.json` correctly to define module boundaries and dependencies. Use `permissions.json` to define granular Access Control targets that the Core can enforce.
6.  **Asset Scoping**: Frontend assets specific to a plugin should be scoped under `public/assets/plugins/{PluginName}/` to prevent global CSS/JS conflicts.

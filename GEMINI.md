# GEMINI.md

This file provides a comprehensive overview of the Truck Manager project, designed to serve as a detailed instructional context for future interactions with the Gemini CLI.

## Project Overview

Truck Manager is a web application built with the [Laravel](https://laravel.com/) framework and the [Filament](https://filamentphp.com/) admin panel. It's designed to manage a fleet of trucks, including their maintenance, fuel logs, trips, and other related data.

### Key Technologies

*   **Backend:** PHP / Laravel
*   **Frontend:** Filament (a TALL stack admin panel)
*   **Database:** SQLite (default, configurable in `.env`)
*   **PDF Generation:** `barryvdh/laravel-dompdf`

### Architecture

The application follows the standard Laravel MVC architecture. The core of the application's user interface is built with Filament, which provides a rich, interactive, and real-time experience for managing the application's data.

## Building and Running

### 1. Initial Setup

To set up the project for the first time, run the following command. This will install all dependencies, configure the environment, and run database migrations.

```bash
composer run setup
```

### 2. Development Environment

To start the development server, a queue listener, and other necessary services, use the following command:

```bash
composer run dev
```

This will start a local development server, typically at `http://localhost:8000`. The main application is accessible at `http://localhost:8000/admin`.

### 3. Running Tests

To run the project's test suite, use the following command:

```bash
composer run test
```

## Development Conventions

### Coding Style

The project follows the standard Laravel coding style. It's recommended to use a tool like [Laravel Pint](https://laravel.com/docs/pint) to automatically format your code.

### Database Migrations

Database schema changes should be made through [database migrations](https://laravel.com/docs/migrations). You can create a new migration using the `php artisan make:migration` command.

### Filament Resources

The application's data is managed through [Filament resources](https://filamentphp.com/docs/3.x/panels/resources). Each resource corresponds to a model and defines the form, table, and other UI components for managing that data.

When adding a new model to be managed by the application, you should also create a corresponding Filament resource. You can create a new resource using the `php artisan make:filament-resource` command.

### PDF Generation

The application uses the `barryvdh/laravel-dompdf` package to generate PDFs. You can create a new PDF by creating a new Blade view and then loading it with the `PDF` facade.

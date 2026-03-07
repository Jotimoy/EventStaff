# EventStaff

EventStaff is a role-based PHP + MySQL platform for event workforce management.
It connects organizers who need part-time staff with employees looking for shift-based event work.

The web experience follows the same product framing used in `index.php`: modern landing page, role-specific dashboards, and workflow-driven modules for events, applications, payments, and notifications.

## Tech Stack

- PHP (procedural + utility functions)
- MySQL (via PDO)
- Bootstrap 5 + Bootstrap Icons
- Vanilla JavaScript for polling and UI interactions
- XAMPP local environment

## Installation

### 1. Place project in htdocs

Expected location:

- `/Applications/XAMPP/xamppfiles/htdocs/EventStaff`

### 2. Start services

From XAMPP Control Panel, start:

- Apache
- MySQL

### 3. Create database schema and seed data

Option A: phpMyAdmin

1. Open `http://localhost/phpmyadmin`
2. Import `config/setup.sql`

Option B: CLI

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/EventStaff
/Applications/XAMPP/xamppfiles/bin/mysql -u root < config/setup.sql
```

### 4. Notifications table setup

Notifications are created in one of two ways:

- Auto-create in `config/database.php` (runs when pages load and DB connection is initialized)
- Manual migration file: `migrations/001_create_notifications_table.sql`

Manual run (if needed):

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/EventStaff
/Applications/XAMPP/xamppfiles/bin/mysql -u root eventstaff_db < migrations/001_create_notifications_table.sql
```

### 5. Verify DB connection

Open:

- `http://localhost/EventStaff/config/test_connection.php`

You should see connection success plus detected tables and sample users.

## Default Accounts

Seeded by `config/setup.sql`:

- Admin: `admin@eventstaff.com` / `admin123`
- Organizer: `organizer@test.com` / `password123`
- Employee: `employee@test.com` / `password123`

Note: This project uses MD5 hashing in current auth flow for academic/demo purposes.

## Role Model

### Admin

- Access platform-level dashboard and analytics
- Review users and change user roles
- View payments and notifications

Primary entry:

- `/EventStaff/admin/dashboard.php`

### Organizer

- Complete organizer profile
- Create events and event shifts
- Review employee applications
- Track payments and notifications

Primary entry:

- `/EventStaff/organizer/dashboard.php`

### Employee

- Complete employee profile
- Browse and apply for shifts
- Track application statuses
- Track payments and notifications

Primary entry:

- `/EventStaff/employee/dashboard.php`

## Module Overview

### Public and Auth

- `index.php`: landing page and conversion entry point
- `auth/login.php`: login + role-based redirect
- `auth/register.php`: registration for organizer and employee roles
- `auth/logout.php`: session logout
- `includes/session.php`: session state, guards, role checks, flash helpers

### Configuration and Data

- `config/database.php`: PDO connection and notifications table bootstrap
- `config/NotificationService.php`: notification CRUD and email helper
- `config/setup.sql`: base schema + sample seed data
- `config/test_connection.php`: visual DB verification
- `migrations/001_create_notifications_table.sql`: notifications migration

### Organizer Area

- `organizer/dashboard.php`: organizer KPIs and quick actions
- `organizer/events.php`: organizer event listing
- `organizer/create_event.php`: create new event
- `organizer/event_detail.php`: event details
- `organizer/event_shifts.php`: shift management by event
- `organizer/applications.php`: review incoming applications
- `organizer/payments.php`: organizer-side payments view
- `organizer/notifications.php`: organizer notifications
- `organizer/profile.php`: organizer profile completion/edit
- `organizer/employee_profile.php`: employee profile inspection
- `organizer/debug_*.php`: development/debug utilities

### Employee Area

- `employee/dashboard.php`: employee summary and shortcuts
- `employee/shifts.php`: browse available shifts
- `employee/my_applications.php`: track submitted applications
- `employee/payments.php`: employee earnings and payment states
- `employee/notifications.php`: employee notifications
- `employee/profile.php`: employee profile completion/edit

### Admin Area

- `admin/dashboard.php`: system stats, role management, recent activity
- `admin/payments.php`: global payment oversight
- `admin/notifications.php`: admin notifications

### Shared API and Frontend Assets

- `api/notification_count.php`: unread notification count endpoint
- `assets/css/style.css`: global styles
- `assets/js/notification-polling.js`: client polling for unread counts

## URL Map

Base URL:

- `http://localhost/EventStaff/`

### Public URLs

- `GET /EventStaff/` -> landing page (`index.php`)
- `GET /EventStaff/auth/login.php` -> login form
- `POST /EventStaff/auth/login.php` -> authenticate user
- `GET /EventStaff/auth/register.php` -> registration form
- `POST /EventStaff/auth/register.php` -> create user
- `GET /EventStaff/auth/logout.php` -> logout

### Admin URLs

- `GET /EventStaff/admin/dashboard.php`
- `GET /EventStaff/admin/payments.php`
- `GET /EventStaff/admin/notifications.php`

### Organizer URLs

- `GET /EventStaff/organizer/dashboard.php`
- `GET /EventStaff/organizer/profile.php`
- `GET /EventStaff/organizer/events.php`
- `GET /EventStaff/organizer/create_event.php`
- `GET /EventStaff/organizer/event_detail.php`
- `GET /EventStaff/organizer/event_shifts.php`
- `GET /EventStaff/organizer/applications.php`
- `GET /EventStaff/organizer/employee_profile.php`
- `GET /EventStaff/organizer/payments.php`
- `GET /EventStaff/organizer/notifications.php`

### Employee URLs

- `GET /EventStaff/employee/dashboard.php`
- `GET /EventStaff/employee/profile.php`
- `GET /EventStaff/employee/shifts.php`
- `GET /EventStaff/employee/my_applications.php`
- `GET /EventStaff/employee/payments.php`
- `GET /EventStaff/employee/notifications.php`

### API URL

- `GET /EventStaff/api/notification_count.php` (requires active session)

## Access and Security Notes

- Route protection is centralized in `includes/session.php` (`require_login`, `require_role`).
- Registration currently allows only `organizer` and `employee`; admin users are expected from seed data or manual DB admin actions.
- Password verification currently uses MD5 and should be upgraded to `password_hash` + `password_verify` for production.
- Default local DB user settings (`root` with empty password) are for local development only.

## Typical User Flows

1. Employee flow
   - Register -> complete profile -> browse shifts -> apply -> track status/payments/notifications
2. Organizer flow
   - Register -> complete profile -> create event -> create shifts -> review applications -> manage payments/notifications
3. Admin flow
   - Login with admin account -> monitor system stats -> manage user roles -> monitor payments

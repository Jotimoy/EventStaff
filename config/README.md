# EventStaff Database Setup Guide

This document explains how to set up and verify the EventStaff database in a local XAMPP environment.

## Database Configuration

- Database name: `eventstaff_db`
- Host: `localhost`
- Username: `root`
- Password: empty (XAMPP default)

These values are defined in `config/database.php`.

## Prerequisites

- XAMPP installed
- Apache and MySQL running
- Project path: `/Applications/XAMPP/xamppfiles/htdocs/EventStaff`

## Setup Methods

### Method 1: phpMyAdmin

1. Open XAMPP Control Panel.
2. Start Apache and MySQL.
3. Open `http://localhost/phpmyadmin`.
4. Go to the Import tab.
5. Choose file: `config/setup.sql`.
6. Click Go and wait for success.

### Method 2: MySQL CLI

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/EventStaff
/Applications/XAMPP/xamppfiles/bin/mysql -u root < config/setup.sql
```

## Notifications Table

The `notifications` table is not part of `config/setup.sql`, but the app handles it in two ways:

- Automatic creation in `config/database.php` with `CREATE TABLE IF NOT EXISTS`.
- Manual migration file available at `migrations/001_create_notifications_table.sql`.

If your app cannot use notifications, run the migration manually:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/EventStaff
/Applications/XAMPP/xamppfiles/bin/mysql -u root eventstaff_db < migrations/001_create_notifications_table.sql
```

## Default Test Accounts

Inserted by `config/setup.sql`:

- Admin: `admin@eventstaff.com` / `admin123`
- Organizer: `organizer@test.com` / `password123`
- Employee: `employee@test.com` / `password123`

Note: passwords are stored with MD5 in this project seed data.

## Expected Tables

After setup and first app load, you should have:

1. `users`
2. `employee_profiles`
3. `organizer_profiles`
4. `events`
5. `event_shifts`
6. `shift_applications`
7. `payments`
8. `notifications`

## Verify Connection

Open:

- `http://localhost/EventStaff/config/test_connection.php`

Expected result:

- "Connection Successful" message
- database metadata
- visible table list
- sample user rows

## Quick Troubleshooting

### Access denied for user root

- Ensure MySQL is running in XAMPP.
- Confirm `config/database.php` has the right credentials.
- If you set a MySQL root password, update `DB_PASS`.

### Unknown database eventstaff_db

- Re-import `config/setup.sql`.
- Confirm the database exists in phpMyAdmin.

### Duplicate entry or table already exists

- This can happen if setup is imported multiple times.
- For a clean reset:

```sql
DROP DATABASE IF EXISTS eventstaff_db;
```

Then re-run `config/setup.sql`.

### Notifications not appearing

- Confirm `notifications` table exists.
- Check if `config/database.php` is loaded in the failing page.
- Run migration: `migrations/001_create_notifications_table.sql`.

## Useful Files

- `config/setup.sql` - base schema and seed users
- `config/database.php` - PDO connection and notifications auto-create
- `config/test_connection.php` - browser-based connection checker
- `config/NotificationService.php` - notification CRUD and email sender
- `migrations/001_create_notifications_table.sql` - notifications migration

## Security Note (Development vs Production)

Current defaults are development-friendly only:

- root with empty password
- MD5-hashed seed passwords

For production, use:

- dedicated DB user with strong password
- `password_hash()` / `password_verify()`
- environment-based secret management

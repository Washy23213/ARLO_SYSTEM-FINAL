<<<<<<< HEAD
# CMU ARLO

CMU ARLO is a Laravel-based alumni management and engagement system for Central Mindanao University. It supports three user roles:

- `Super Admin` for full administrative access and top-level account ownership
- `Admin` for managing alumni records, jobs, announcements, feedback, analytics, and audit logs
- `Alumni` for viewing records, exploring jobs, updating profiles, and submitting feedback

The project includes a seeded sample dataset so the system is usable immediately for demos and presentations.

## Core Features

- Shared login page backed by separate admin and alumni account tables
- Login rate limiting and password reset flow
- Alumni dashboard with personal profile, tracer, application, and notification summaries
- Alumni directory and detailed record views
- Job board with alumni applications and alumni-submitted job postings
- Announcement publishing and viewing
- Exportable analytics reports and chart downloads
- Profile, privacy, password, and session management
- Feedback submission and admin-side monitoring
- Admin CRUD for alumni, jobs, and announcements
- Admin analytics and audit logging

## Tech Stack

- PHP 8.2
- Laravel 12
- PostgreSQL for local app data
- SQLite in-memory for automated tests
- Blade templates
- Vite
- Tailwind CSS
- Chart.js
- Boxicons

## Project Structure

- `app/Http/Controllers` contains admin, alumni, and auth controllers
- `admins` stores administrative accounts, while `users` stores alumni accounts
- `app/Services` contains reporting, analytics, dashboard, and audit services
- `resources/views` contains Blade templates for alumni, admin, and auth pages
- `database/migrations` contains the Laravel schema
- `database/seeders` contains the sample dataset and default seeding flow
- `tests/Feature` contains end-to-end feature coverage for key user flows

## Local Setup

1. Install dependencies:

```bash
composer install
npm install
```

2. Create your environment file:

```bash
copy .env.example .env
php artisan key:generate
```

3. Configure the database in `.env`.

For the current local setup used in this project, the database values are:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cmu_arlo_db
DB_USERNAME=cmu_user
DB_PASSWORD=1234
```

4. Run migrations and seed sample data:

```bash
php artisan migrate --force
php artisan db:seed --force
```

5. Configure mail in `.env`.

For local testing, the project is now SMTP-ready and defaults to a local SMTP catcher such as Mailpit:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS=noreply@cmu.edu.ph
MAIL_FROM_NAME="CMU ARLO"
```

To send to real inboxes, replace those values with your actual SMTP provider credentials.

6. Start the app:

```bash
composer run dev
```

If you prefer separate terminals:

```bash
php artisan serve
npm run dev
```

## Demo Accounts

All seeded sample accounts use the password:

```text
password
```

Super admin account:

- `admin@cmu.edu.ph`

Admin account:

- `oarl.admin@cmu.edu.ph`

Sample alumni accounts:

- `john.mabuhay@cmu.edu.ph`
- `maria.caballero@cmu.edu.ph`
- `rico.salvador@cmu.edu.ph`
- `anne.dizon@cmu.edu.ph`
- `kevin.alonzo@cmu.edu.ph`
- `jessa.neri@cmu.edu.ph`
- `paolo.ventura@cmu.edu.ph`

## Useful Commands

Install and build everything:

```bash
composer run setup
```

Run the test suite:

```bash
php artisan test
```

Clear caches after data or config changes:

```bash
php artisan optimize:clear
```

Seed sample data into the current database without wiping tables:

```bash
php artisan db:seed --force
```

Promote an existing account to super admin:

```bash
php artisan user:make-super-admin your-email@example.com
```

Reset the database and recreate the sample dataset:

```bash
php artisan migrate:fresh --seed --force
```

## Important Data Warning

`php artisan migrate:fresh --seed --force` is destructive.

It will:

- drop all existing tables
- delete current records
- recreate the schema
- load only the seeded sample dataset

Use `php artisan db:seed --force` if you want to add the sample dataset without wiping the whole database.

## Database Notes

- The live app is configured for PostgreSQL.
- The automated tests use SQLite in memory through `phpunit.xml`.
- Some analytics queries are written with PostgreSQL behavior in mind, so PostgreSQL is recommended for full local feature parity.

## Seeded Sample Data

The current sample seeder creates a working demo dataset that includes:

- 1 admin account
- 7 alumni accounts
- 3 job postings
- 3 announcements
- multi-month tracer records from October 2025 to March 2026
- 3 feedback entries
- linked job applications and system logs

Seeder files:

- `database/seeders/DatabaseSeeder.php`
- `database/seeders/SampleDataSeeder.php`

## Testing Status

The current feature coverage includes:

- authentication and password reset
- admin CRUD flows
- admin audit logs
- admin feedback monitoring
- alumni job applications
- alumni feedback
- alumni dashboard privacy-safe rendering
- announcement visibility rules
- account deletion cascade behavior

Run:

```bash
php artisan test
```

## Presentation Notes

For presentation/demo use, the easiest reset flow is:

```bash
php artisan migrate:fresh --seed --force
php artisan optimize:clear
```

That restores the sample admin account, alumni accounts, jobs, announcements, and tracer data to a known state.

## Current Limitations

- External email delivery still depends on valid SMTP credentials. The app is configured for SMTP transport, but real inbox delivery requires a working mail server or provider account.
- Detailed tracer and employment analytics remain available in the admin workspace, not the alumni dashboard.
- More `FormRequest` extraction can still be done in deeper admin and settings flows.
=======
# ARLO_SYSTEM-FINAL
>>>>>>> 889d8ab7f1fa28d0b3d702218ff06279cd88b14b

# Laravel + SQL + Docker + Vercel Deployment Guide

## Complete Production Deployment Setup

This guide explains how to deploy a **Laravel backend with a SQL database using Docker on Vercel**.

The final architecture will be:

```text
Frontend
   |
   | HTTPS API Requests
   v
Vercel
   |
   | Docker Container
   v
Laravel + PHP
   |
   | MySQL Connection
   v
Hosted MySQL Database
```

> **Important:** Vercel hosts the Laravel application/container. Your MySQL database should be hosted separately on a persistent database service.

---

# 1. Requirements

Before starting, make sure you have:

* Laravel project
* PHP installed locally
* Composer installed
* MySQL/SQL database
* Git installed
* GitHub account
* Vercel account
* Docker installed locally
* A production MySQL database
* Laravel `APP_KEY`

Recommended tools:

```text
PHP
Composer
Laravel
MySQL
Git
GitHub
Docker
Vercel
```

---

# 2. Check Your Laravel Version

Open your Laravel project.

Run:

```bash
php artisan --version
```

Example:

```text
Laravel Framework 12.x
```

Also check PHP:

```bash
php -v
```

And Composer:

```bash
composer --version
```

Check your dependencies:

```bash
composer show
```

---

# 3. Check the Laravel Project Structure

Your project should look approximately like this:

```text
laravel-backend/
│
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
│
├── artisan
├── composer.json
├── composer.lock
├── package.json
├── phpunit.xml
│
├── .env
├── .env.example
│
└── Dockerfile.vercel
```

Depending on your project, some files/folders may be different.

---

# 4. Verify the Application Works Locally

Before deploying anything, make sure Laravel works locally.

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies if your project uses them:

```bash
npm install
```

Generate the Laravel application key if one does not already exist:

```bash
php artisan key:generate
```

Run migrations:

```bash
php artisan migrate
```

Start Laravel:

```bash
php artisan serve
```

You should see something similar to:

```text
Server running on [http://127.0.0.1:8000]
```

Open:

```text
http://127.0.0.1:8000
```

Test your API routes.

For example:

```text
GET /api/users
POST /api/login
GET /api/products
```

Use Postman or another API testing tool.

---

# 5. Configure Your Local SQL Database

Your local `.env` may look like:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:YOUR_LOCAL_KEY
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=root
DB_PASSWORD=
```

The exact values depend on your local MySQL installation.

If you use XAMPP, for example:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_database
DB_USERNAME=root
DB_PASSWORD=
```

---

# 6. IMPORTANT: Do Not Upload `.env`

Your `.env` contains sensitive information.

Never commit it to GitHub.

Make sure `.gitignore` contains:

```gitignore
.env
.env.backup
.env.production
.phpunit.result.cache
/vendor
/node_modules
```

Check Git status:

```bash
git status
```

If `.env` appears in the files that will be committed, stop and fix `.gitignore`.

---

# 7. Prepare `.env.example`

Your repository should contain:

```text
.env.example
```

It should contain variable names but not production passwords.

Example:

```env
APP_NAME=Laravel
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

Never put the actual production password here.

---

# 8. Production Database

Your local database:

```text
localhost
```

cannot be accessed by Vercel.

This will NOT work:

```env
DB_HOST=127.0.0.1
```

inside production.

Why?

Because:

```text
127.0.0.1
```

inside the Vercel container means:

```text
the Vercel container itself
```

It does NOT mean your personal computer.

You need a publicly accessible managed SQL database.

The production architecture should be:

```text
Laravel on Vercel
       |
       | Internet
       |
       v
Production MySQL
```

---

# 9. Create Your Production MySQL Database

Create a production database using a managed MySQL provider.

You will receive credentials similar to:

```text
Host:
Port:
Database:
Username:
Password:
```

For example:

```text
Host: mysql.example.com
Port: 3306
Database: production_db
Username: production_user
Password: ********
```

Do not use these example values literally.

---

# 10. Import Your Existing Database

If you already have data locally, export your database.

Using MySQL:

```bash
mysqldump -u root -p your_database > database.sql
```

You can then import the SQL dump into your production MySQL database.

Example:

```bash
mysql -h YOUR_HOST \
-u YOUR_USERNAME \
-p YOUR_DATABASE < database.sql
```

Alternatively, use your database provider's import tool.

---

# 11. Test Production Database Connectivity

Before deploying Laravel, make sure the production database accepts connections.

Your Laravel production environment will eventually contain:

```env
DB_CONNECTION=mysql
DB_HOST=YOUR_PRODUCTION_HOST
DB_PORT=3306
DB_DATABASE=YOUR_PRODUCTION_DATABASE
DB_USERNAME=YOUR_PRODUCTION_USERNAME
DB_PASSWORD=YOUR_PRODUCTION_PASSWORD
```

Do not commit this file.

These values will be added to Vercel Environment Variables.

---

# 12. Create the Dockerfile

Create this file in the root of the Laravel project:

```text
Dockerfile.vercel
```

Example structure:

```text
laravel-backend/
│
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
│
├── artisan
├── composer.json
├── composer.lock
│
└── Dockerfile.vercel
```

---

# 13. Dockerfile

Use a PHP/FrankenPHP-based container.

Example:

```dockerfile
FROM dunglas/frankenphp:php8.3

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

RUN php artisan optimize

RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

ENV SERVER_NAME=:80

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
```

> **Important:** Adjust the PHP version to the version required by your Laravel version and `composer.json`.

Check:

```bash
cat composer.json
```

Look for:

```json
"require": {
    "php": "^8.3"
}
```

The Docker PHP version must satisfy this requirement.

---

# 14. Why FrankenPHP?

FrankenPHP provides an HTTP server for PHP applications and works well with Laravel.

The container contains:

```text
FrankenPHP
      |
      v
PHP
      |
      v
Laravel
```

This means the Docker container can directly serve the Laravel application.

---

# 15. Laravel Storage Permissions

Laravel needs write access to:

```text
storage/
bootstrap/cache/
```

The Dockerfile therefore contains:

```dockerfile
RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache
```

This prevents many common production permission errors.

---

# 16. Laravel Cache Configuration

For production, run:

```bash
php artisan optimize
```

This caches:

* configuration
* routes
* views
* events

Do not run this blindly during local development if you are frequently changing `.env` values.

When using cached configuration, Laravel reads environment variables through its configuration files.

---

# 17. Check `config/database.php`

Laravel's MySQL configuration normally uses environment variables.

A typical configuration looks like:

```php
'mysql' => [
    'driver' => 'mysql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
],
```

Do not hard-code your production credentials here.

---

# 18. CORS Configuration

If your frontend is hosted separately, you need to configure CORS.

For example:

```text
Frontend:
https://myfrontend.vercel.app

Backend:
https://mybackend.vercel.app
```

Your frontend needs permission to call your backend.

Check:

```text
config/cors.php
```

Depending on your Laravel version, configure the allowed origins appropriately.

Example:

```php
'allowed_origins' => [
    'https://myfrontend.vercel.app',
],
```

During testing, you may temporarily use:

```php
'allowed_origins' => ['*'],
```

But do not leave an unnecessarily permissive CORS configuration in production.

---

# 19. Authentication

If your Laravel application uses:

* Laravel Sanctum
* session authentication
* API tokens
* JWT
* custom authentication

make sure the production configuration is correct.

For an API-only backend, token-based authentication is often simpler than relying on browser sessions.

If using Sanctum with a separate frontend, configure:

```env
SANCTUM_STATEFUL_DOMAINS=
SESSION_DOMAIN=
```

according to your actual frontend/backend domains.

Do not copy random values from tutorials. Domains are surprisingly good at ruining an otherwise functional deployment.

---

# 20. Local Docker Test

Before sending the container to Vercel, test it locally.

Build:

```bash
docker build \
  -f Dockerfile.vercel \
  -t laravel-backend .
```

Run:

```bash
docker run \
  -p 8080:80 \
  laravel-backend
```

Open:

```text
http://localhost:8080
```

Test your Laravel API.

For example:

```text
http://localhost:8080/api/products
```

If the API works locally inside Docker, you have eliminated an entire category of deployment problems.

---

# 21. Git Initialization

If your project is not already a Git repository:

```bash
git init
```

Add your files:

```bash
git add .
```

Check what will be committed:

```bash
git status
```

Make sure:

```text
.env
```

is NOT included.

Commit:

```bash
git commit -m "Prepare Laravel backend for deployment"
```

---

# 22. Create GitHub Repository

Create a new repository on GitHub.

Example:

```text
laravel-backend
```

Then connect your local project:

```bash
git remote add origin https://github.com/YOUR_USERNAME/laravel-backend.git
```

Push:

```bash
git branch -M main
git push -u origin main
```

Your repository should contain:

```text
app/
bootstrap/
config/
database/
public/
resources/
routes/
storage/

artisan
composer.json
composer.lock
Dockerfile.vercel
.env.example
.gitignore
```

It should NOT contain:

```text
.env
```

---

# 23. Create Vercel Project

Go to Vercel.

Select:

```text
Add New Project
```

Then:

```text
Import Git Repository
```

Select your Laravel GitHub repository.

---

# 24. Vercel Build Configuration

Because this deployment uses Docker, Vercel should build your container using:

```text
Dockerfile.vercel
```

Do not add a random Node.js build command just because Vercel's interface offers one.

Your Laravel application is the backend.

---

# 25. Environment Variables

Open:

```text
Vercel
→ Project
→ Settings
→ Environment Variables
```

Add your Laravel production variables.

Example:

```env
APP_NAME=My Laravel API
APP_ENV=production
APP_KEY=base64:YOUR_KEY
APP_DEBUG=false
APP_URL=https://your-backend.vercel.app
```

Database:

```env
DB_CONNECTION=mysql
DB_HOST=YOUR_DATABASE_HOST
DB_PORT=3306
DB_DATABASE=YOUR_DATABASE
DB_USERNAME=YOUR_USERNAME
DB_PASSWORD=YOUR_PASSWORD
```

---

# 26. Generate APP_KEY

If you already have a Laravel application key, keep it.

If you need to generate one locally:

```bash
php artisan key:generate --show
```

You will get something similar to:

```text
base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Put that value into Vercel:

```env
APP_KEY=base64:xxxxxxxxxxxxxxxx
```

Do not regenerate the key unnecessarily after your application is already using encrypted data.

Changing `APP_KEY` can make previously encrypted data impossible to decrypt.

---

# 27. Important Production Variables

Typical variables may include:

```env
APP_NAME=
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

Your project may also require:

```env
MAIL_MAILER=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=

FILESYSTEM_DISK=
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=

SANCTUM_STATEFUL_DOMAINS=
SESSION_DOMAIN=
```

Only add variables your application actually uses.

---

# 28. Never Enable APP_DEBUG in Production

Production:

```env
APP_DEBUG=false
```

Do NOT use:

```env
APP_DEBUG=true
```

Production errors can expose application details, paths, SQL information, and other sensitive information.

---

# 29. Deploy

After configuring environment variables:

```text
Vercel
→ Deploy
```

Vercel will:

```text
GitHub
   ↓
Clone repository
   ↓
Build Docker image
   ↓
Install Composer dependencies
   ↓
Create container
   ↓
Start Laravel
   ↓
Expose your API
```

Your backend will receive a URL similar to:

```text
https://your-backend.vercel.app
```

---

# 30. Test the API

Suppose your route is:

```php
Route::get('/products', [ProductController::class, 'index']);
```

Your production endpoint might be:

```text
https://your-backend.vercel.app/api/products
```

Test using:

```bash
curl https://your-backend.vercel.app/api/products
```

Or use Postman.

---

# 31. Check Laravel Logs

If deployment fails, check:

```text
Vercel
→ Project
→ Deployments
→ Failed Deployment
→ Build Logs
```

Also check runtime logs.

Common errors include:

```text
Class not found
```

```text
Database connection refused
```

```text
SQLSTATE
```

```text
Permission denied
```

```text
APP_KEY missing
```

```text
PHP extension missing
```

```text
Application failed to start
```

The exact error is important. Do not randomly change five configuration files at once. That is how debugging turns into archaeology.

---

# 32. Common Database Error

If you see:

```text
SQLSTATE[HY000] [2002] Connection refused
```

check:

```env
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

Do not use:

```env
DB_HOST=127.0.0.1
```

unless the database is actually running inside the same container, which is not the architecture recommended here.

Use your hosted database host.

---

# 33. SSL Database Connections

Some managed database providers require SSL/TLS.

If your provider requires SSL, follow its Laravel/MySQL connection instructions.

Do not disable SSL just to make an error disappear.

For example, a provider may give you:

```text
mysql://username:password@host:3306/database
```

or specific SSL certificate requirements.

Use the provider's production connection requirements.

---

# 34. Laravel Migrations in Production

If your production database is empty and your project has migrations:

```bash
php artisan migrate --force
```

The `--force` option is necessary for production environments because Laravel protects production databases from accidental migration commands.

Do not run:

```bash
php artisan migrate:fresh
```

against your production database unless you deliberately want to destroy the existing schema/data.

`migrate:fresh` drops all tables.

Humanity has invented backups for a reason.

---

# 35. Database Seeders

If you need initial data:

```bash
php artisan db:seed --force
```

Or:

```bash
php artisan migrate --seed --force
```

Only run seeders that are safe for production.

Do not use development seeders that create fake users or delete production records.

---

# 36. File Uploads

This is important.

Do not assume files stored in:

```text
storage/app/public
```

will behave like a permanent traditional server filesystem.

Vercel containers are not a normal persistent VPS.

For production file uploads, use object storage such as:

```text
Amazon S3
Cloudinary
ImageKit
```

Then configure Laravel's filesystem appropriately.

For example:

```env
FILESYSTEM_DISK=s3
```

or the configuration required by your selected provider.

---

# 37. Laravel Sessions

If your application uses sessions, do not rely on local container storage for long-term persistence.

For API-only applications, consider token-based authentication.

For session-based applications, configure a persistent session store appropriate for your architecture.

Depending on your application, Redis or a database-backed session store may be appropriate.

---

# 38. Laravel Cache

Similarly, do not assume local container storage is persistent.

For production workloads requiring shared/persistent cache, use an appropriate external cache such as Redis.

Your application should not depend on files inside the container surviving indefinitely.

---

# 39. Laravel Queue Workers

This is one of the most important limitations to understand.

If your application uses:

```text
php artisan queue:work
```

do not assume a normal long-running queue worker can simply run permanently inside the Vercel container.

For queue-heavy applications, use an architecture designed for background jobs.

Possible approaches include:

```text
External queue service
+
Worker platform
```

or a platform specifically designed for persistent workers.

If your Laravel backend does not use queues, you can ignore this section.

---

# 40. Scheduled Tasks

If your application uses Laravel Scheduler:

```text
php artisan schedule:run
```

you need a mechanism to trigger it periodically.

Do not assume:

```text
php artisan schedule:work
```

can run indefinitely on a serverless/container platform.

Use an external scheduler/cron mechanism supported by your deployment architecture.

---

# 41. Storage Architecture

Recommended:

```text
Laravel
   |
   ├── MySQL → Database
   |
   ├── S3/Cloudinary/ImageKit → Files
   |
   └── Redis → Cache/Queues if required
```

Do not try to make the Vercel container behave like a traditional VPS.

---

# 42. Frontend Configuration

If your frontend is React or Next.js, change your API URL.

Development:

```env
VITE_API_URL=http://localhost:8000/api
```

Production:

```env
VITE_API_URL=https://your-backend.vercel.app/api
```

Then your frontend can call:

```javascript
fetch(`${import.meta.env.VITE_API_URL}/products`);
```

For Next.js:

```env
NEXT_PUBLIC_API_URL=https://your-backend.vercel.app/api
```

---

# 43. CORS Example

Suppose:

```text
Frontend:
https://my-frontend.vercel.app

Backend:
https://my-backend.vercel.app
```

Your Laravel CORS configuration should allow:

```text
https://my-frontend.vercel.app
```

Do not allow every origin unless you have a specific reason.

---

# 44. Production Checklist

Before deployment:

```text
[ ] Laravel works locally
[ ] API routes work
[ ] Database works locally
[ ] composer install works
[ ] PHP version is compatible
[ ] Docker installed
[ ] Docker image builds
[ ] Docker container runs locally
[ ] API works inside Docker
[ ] .env is ignored
[ ] .env is NOT on GitHub
[ ] .env.example exists
[ ] Production database exists
[ ] Production database is accessible
[ ] Production database credentials are ready
[ ] APP_KEY is configured
[ ] APP_DEBUG=false
[ ] CORS is configured
[ ] File storage is configured
[ ] Authentication is configured
[ ] GitHub repository is ready
[ ] Vercel project created
[ ] Vercel environment variables configured
```

---

# 45. Recommended Final Architecture

Your production system should look like:

```text
                    ┌───────────────────────┐
                    │       FRONTEND        │
                    │ React / Next.js       │
                    └───────────┬───────────┘
                                │
                                │ HTTPS
                                ▼
                    ┌───────────────────────┐
                    │        VERCEL         │
                    │                       │
                    │   Docker Container    │
                    │                       │
                    │   FrankenPHP          │
                    │        ↓              │
                    │       PHP              │
                    │        ↓              │
                    │      Laravel           │
                    └───────┬───────┬───────┘
                            │       │
                    MySQL   │       │ Storage
                            │       │
                            ▼       ▼
                    ┌──────────┐ ┌──────────┐
                    │  MySQL   │ │   S3 /   │
                    │ Database │ │Cloudinary │
                    └──────────┘ └──────────┘
```

---

# 46. Recommended Project Structure

Final repository:

```text
laravel-backend/
│
├── app/
│   ├── Http/
│   ├── Models/
│   └── ...
│
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
│   ├── api.php
│   └── web.php
│
├── storage/
├── tests/
│
├── artisan
├── composer.json
├── composer.lock
│
├── Dockerfile.vercel
├── .env.example
├── .gitignore
└── README.md
```

---

# 47. Deployment Flow

Every future deployment can follow:

```text
1. Make code changes
        ↓
2. Test locally
        ↓
3. Test API
        ↓
4. Build Docker image
        ↓
5. Test Docker container
        ↓
6. Commit changes
        ↓
7. Push to GitHub
        ↓
8. Vercel automatically deploys
        ↓
9. Test production API
```

Commands:

```bash
git add .
git commit -m "Update backend"
git push
```

Vercel can then deploy the new commit automatically.

---

# 48. Emergency Rollback

If a deployment breaks your production API:

```text
Vercel
→ Deployments
→ Previous successful deployment
→ Promote to Production
```

Do not immediately start deleting things.

First identify:

```text
What changed?
```

Then inspect:

```text
Build logs
Runtime logs
Database errors
Environment variables
Docker build
```

---

# 49. Security Checklist

Never commit:

```text
.env
database passwords
API keys
AWS secret keys
SMTP passwords
JWT secrets
private certificates
```

Use Vercel Environment Variables.

Production:

```env
APP_ENV=production
APP_DEBUG=false
```

Use HTTPS.

Use restricted database users.

Do not expose your database publicly more than necessary.

Validate API input.

Use authentication for protected endpoints.

Use authorization for admin endpoints.

Validate file uploads.

Limit upload sizes.

Keep Laravel and Composer dependencies updated.

---

# 50. Final Deployment Checklist

The complete process is:

```text
LOCAL LARAVEL
      │
      ▼
Test Laravel
      │
      ▼
Test MySQL
      │
      ▼
Create Dockerfile.vercel
      │
      ▼
Build Docker locally
      │
      ▼
Run Docker locally
      │
      ▼
Test API
      │
      ▼
Create production MySQL
      │
      ▼
Create GitHub repository
      │
      ▼
Push Laravel project
      │
      ▼
Create Vercel project
      │
      ▼
Configure environment variables
      │
      ▼
Vercel builds Docker image
      │
      ▼
Laravel starts
      │
      ▼
Laravel connects to MySQL
      │
      ▼
Test API
      │
      ▼
Connect frontend
```

---

# 51. Important: What Vercel Does and Does Not Host

### Vercel hosts

```text
Laravel application
PHP runtime
Docker container
HTTP API
```

### Your database provider hosts

```text
MySQL
Database storage
Database backups
Database persistence
```

### Your storage provider hosts

```text
Images
Documents
Uploaded files
```

### Your frontend can be hosted

```text
Vercel
```

or another hosting provider.

---

# 52. Final Architecture for a Typical Project

For a normal Laravel API project:

```text
                 GitHub
                    │
                    ▼
              ┌───────────┐
              │  Vercel   │
              │  Docker   │
              └─────┬─────┘
                    │
                    ▼
                Laravel
                    │
          ┌─────────┴─────────┐
          │                   │
          ▼                   ▼
       MySQL              Cloud Storage
     Database             Images/Files
```

This is the architecture to aim for.

---

# 53. Troubleshooting Order

If something fails, troubleshoot in this order:

### Step 1

Check Vercel build logs.

### Step 2

Check Docker build locally:

```bash
docker build -f Dockerfile.vercel -t laravel-backend .
```

### Step 3

Run Docker locally:

```bash
docker run -p 8080:80 laravel-backend
```

### Step 4

Check Laravel environment variables.

### Step 5

Check database connectivity.

### Step 6

Check CORS.

### Step 7

Check authentication.

### Step 8

Check storage/file uploads.

Do not change everything simultaneously.

---

# 54. Most Important Rules

Remember these five rules:

### Rule 1

Do not put `.env` on GitHub.

### Rule 2

Do not run MySQL inside your Vercel Laravel container.

### Rule 3

Use a persistent hosted MySQL database.

### Rule 4

Test the Docker container locally before deploying.

### Rule 5

Use Vercel Environment Variables for production secrets.

---

# 55. Expected Final Result

After successful deployment:

```text
Laravel API:

https://your-backend.vercel.app
```

Example:

```text
GET
https://your-backend.vercel.app/api/products
```

Your frontend:

```text
https://your-frontend.vercel.app
```

Your database:

```text
Hosted MySQL
```

Your production architecture:

```text
Frontend
   ↓
Laravel API on Vercel
   ↓
Hosted MySQL
```

That is the complete deployment model.

---

## Before Going Live

Do not deploy directly to production without testing:

```text
Authentication
Registration
Login
Logout
CRUD operations
Database queries
File uploads
API validation
CORS
Error handling
Admin routes
Password reset
Email functionality
```

A deployment being "green" on Vercel only means the server started. It does not mean your application suddenly achieved enlightenment.

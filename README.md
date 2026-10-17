# De Guzman Drugmart — Pharmacy Inventory & POS (Laravel 13)

Front end (Blade + Tailwind 4 + Alpine) + backend (Laravel 13) + database (migrations/seeders),
base sa Figma design. May login/register/forgot-reset password, Dashboard, Products (CRUD + photo + stock +/-),
POS (Senior/PWD 20%, change computation), Receipt (print/PDF), Sales History, Reports (+CSV export), Settings.

## Requirements
PHP 8.3+, Composer, Node 20+ (XAMPP/Laragon/Herd ok)

## Install (PowerShell / Terminal)
```bash
composer create-project laravel/laravel:^13.0 dg-drugmart
cd dg-drugmart

# 1) Kopyahin ang LAHAT ng laman ng zip na ito papunta sa project (i-overwrite: routes/web.php, resources/css/app.css, resources/js/app.js, app/Models/User.php, database/seeders/DatabaseSeeder.php)

# 2) Frontend
npm install alpinejs
npm install
npm run build        # o `npm run dev` habang nagde-develop

# 3) Database (default SQLite, gagana agad). Para sa MySQL/XAMPP, i-edit ang .env:
#    DB_CONNECTION=mysql  DB_DATABASE=dg_drugmart  DB_USERNAME=root  DB_PASSWORD=
php artisan migrate --seed
php artisan storage:link

php artisan serve      # http://127.0.0.1:8000
```
Default admin: `admin@deguzmandrugmart.ph` / `Admin#Drugmart2026` — **palitan agad sa Settings.**
Para patayin ang public registration pagkatapos: `REGISTRATION_ENABLED=false` sa `.env`.

## Logo & product photos
I-export ang logo mula sa Figma at ilagay sa `public/images/logo.png` (may placeholder `logo.svg` kung wala).
Product photos: i-upload sa Add/Edit Product (PNG/JPG/WEBP, max 5MB).

## Mail (Forgot Password)
Default `MAIL_MAILER=log` — lalabas ang reset link sa `storage/logs/laravel.log`. Mag-set ng SMTP sa `.env` para sa totoong email.

## Tests
`php artisan test`

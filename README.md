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


## Forgot Password via Gmail (6-digit code)
Ang "Forgot Password" ay nagpapadala ng **6-digit code** sa email ng user (valid 10 minuto, 5 tries lang).
Para gumana sa Gmail, kailangan ng **App Password** (hindi ang normal na Gmail password):

1. Buksan ang Google Account → **Security** → i-on ang **2-Step Verification**.
2. Hanapin ang **App passwords** → gumawa ng bago (pangalan: DeGuzmanDrugmart) → kopyahin ang 16-character code (tanggalin ang spaces).
3. Sa `.env` ng project:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=youremail@gmail.com
MAIL_PASSWORD=abcdefghijklmnop
MAIL_FROM_ADDRESS=youremail@gmail.com
MAIL_FROM_NAME="De Guzman Drugmart"
```
4. `php artisan config:clear`
5. Test: `php artisan mail:test youremail@gmail.com` (dapat dumating sa Inbox/Spam).

## Update steps (kung na-install mo na ang dating bersyon)
```
php artisan migrate
php artisan view:clear
npm run build
```
Hindi na kailangan ang `php artisan storage:link` — ang mga larawan ay inihahatid na ng `/media/...` route.


## Pag-update sa bagong bersyon (bug fixes #1-#19)
```
npm install @fontsource-variable/manrope @fontsource-variable/plus-jakarta-sans @fontsource-variable/jetbrains-mono
php artisan migrate
php artisan view:clear
npm run build
```
- Naka-bundle na ang mga font (walang Google Fonts) kaya pareho ang itsura kahit offline.
- Si **Admin** lang ang puwedeng mag-Add/Edit/Delete ng product, mag +/- ng stock, at gumawa ng accounts (menu: **Users**).
- Naka-OFF na ang public Register (bukas lang kung wala pang user). Para buksan: `REGISTRATION_ENABLED=true` sa `.env`.
- Ilagay ang totoong address, phone at TIN sa **Settings** para lumabas sa resibo.


## Oras ng Pilipinas (timezone)
Sa `.env`, ilagay ang `APP_TIMEZONE=Asia/Manila`, tapos `php artisan config:clear` at `php artisan migrate`
(ang migration ay nag-aayos ng oras ng mga lumang benta nang isang beses lang).

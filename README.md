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


## Recheck fixes (#1-#6)
```
php artisan migrate
php artisan config:clear
php artisan view:clear
npm run build
php artisan test
```
- **Timezone:** naka-force na sa `Asia/Manila` (sa `AppServiceProvider`, dahil naka-hardcode ang 'UTC' sa config/app.php). Itatama ng bagong migration nang isang beses ang oras ng mga lumang benta.
- **npm run build** ay hindi na nangangailangan ng internet (tinanggal ang Bunny/Instrument Sans sa vite.config.js).


## Retest fixes (#1-#5)
```
php artisan migrate
php artisan view:clear
php artisan test
```
- POS: ang gamot na mag-e-expire ngayong araw ay mabebenta pa; expired lang kapag lumampas na ang petsa.
- Dashboard: ang "Critical" = Low Stock + Expiring Soon + Expired (lahat ng alerts, scrollable ang listahan).
- +/- stock ay atomic (database ang nagbibilang).
- Reports: may **Amount** (bago discount), **Discount** at **Net**; ang total ng Net ay tugma sa Revenue.
- Seeder: hindi na ire-reset ang password ng admin kapag nag-`migrate --seed` ulit.


## Expiry rule sa POS
Hindi na mabebenta ang gamot na **30 araw (o mas malapit pa)** na lang bago mag-expire (ang bilang ay galing sa
Settings → "Warn me before a product expires"). Nakatago rin ito sa POS list at hinaharang din sa server.


## Paglipat sa MySQL (phpMyAdmin / XAMPP)
1. XAMPP Control Panel: i-Start ang **MySQL** (at Apache).
2. `http://localhost/phpmyadmin` -> **New** -> Database name: `dg_drugmart`, Collation: `utf8mb4_unicode_ci` -> **Create**.
3. `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dg_drugmart
DB_USERNAME=root
DB_PASSWORD=
```
4. `php artisan config:clear` -> `php artisan migrate`
5. Kung gusto mong dalhin ang lumang data mula SQLite: `php artisan dg:import-sqlite`
   (o kung bagong simula: `php artisan db:seed`)


## Ilipat sa MySQL / phpMyAdmin (XAMPP)
1. XAMPP Control Panel: i-Start ang **Apache** at **MySQL**.
2. `http://localhost/phpmyadmin` → **New** → Database name: `dg_drugmart`, Collation: `utf8mb4_unicode_ci` → **Create**.
3. `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dg_drugmart
DB_USERNAME=root
DB_PASSWORD=
```
4. `php artisan config:clear` then `php artisan migrate`
5. Bagong simula: `php artisan db:seed` (admin + sample products).
   Ililipat ang lumang data: `php artisan db:import-sqlite` (huwag mag-seed bago ito).


## Bagong design (Figma: De Guzman Drugmart – System Screens)
```
php artisan migrate
php artisan view:clear
npm run build
php artisan test
```
- **Product Type:** Branded / Generic / Grocery (Add/Edit Product, Products filter chips, Product Details, POS, Reports). Ang "Prescription (Rx)" ay hiwalay na checkbox.
- **Reports → Sales & Stock:** *Daily Sales & Stock* (3 columns, may prev/next na petsa), *Sales Report (Detailed)* at *Sales by Product*, may Category filter.
- **Reports → Analytics:** Today / This Week / This Month / This Year, chart per type, Share of sales, Top 5 best sellers.
- **403 Access Denied** page para sa cashier.


## Cashier access link (Gmail)
- Walang password ang **Cashier**. Pag gumawa ang Admin ng account sa **Users** (Full Name, Email, Role), awtomatikong may Gmail na ipapadala na may link: pindutin = diretso sa Dashboard (isang gamit lang, 72 oras).
- Hindi makaka-login ang Cashier sa login form. Kung kailangan ng bagong link: "Cashier? Get an access link" sa login page, o **Send link** ng Admin sa Users page.
- Ang bagong **Admin** ay nakakatanggap ng link para itakda ang sariling password.
- Kailangang gumagana ang Gmail settings sa `.env` (App Password). Subukan: `php artisan mail:test youremail@gmail.com`.
- Ang link ay gumagamit ng address kung saan naka-bukas ang Admin. Kung ang cashier ay sa ibang device (phone), patakbuhin ang `php artisan serve --host=0.0.0.0` at buksan ang system gamit ang IP ng laptop (hal. `http://192.168.1.5:8000`) bago gumawa ng account.
- Update: `php artisan migrate` (bagong table na `access_tokens`).


## Daily Report, Dashboard at POS (update)
```
php artisan migrate
php artisan view:clear
npm run build
php artisan test
```
- **Reports → Daily Report** (dating Sales & Stock): bawat produkto kada araw: *Last stock*, *Sold*, *Added / Removed*, *Stock left*. Ang bawat pagbabago ng stock (benta, +/−, edit, bagong produkto) ay naka-save sa table na `stock_movements`.
- **Dashboard:** may **Today's Sales by Type**; nandoon pa rin ang Recent Transactions.
- **POS:** "Available Products" (kasama na ang Grocery).


## Product form at Dashboard (update)
```
php artisan migrate
php artisan view:clear
npm run build
php artisan test
```
- **Add/Edit Product:** *Unit* ay dropdown (Per piece, Per tablet, Per bottle...). *Needs a prescription (Rx)* ay para sa gamot lang. Ang **No expiry** ay para sa **Grocery** lang (hal. napkin, sabon); ang gamot ay laging may expiration date.
- Ang produktong walang expiration ay hindi kailanman lalabas na expired/expiring at laging mabebenta sa POS.
- **Dashboard:** buong card na ang clickable: Total Products → Products (All), Low Stock → Low Stock filter, Expiring Soon → Expiring filter, Today's Sales → Daily Report.
- **Settings (Cashier):** walang password na bahagi.

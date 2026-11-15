# AlifHost (cPanel) Deployment Guide — Alishe Nails Laravel 12

Yeh guide AlifHost ya kisi bhi standard cPanel shared hosting par **Alishe Nails** deploy karne ke liye step-by-step tayar ki gayi hai.

---

## 📋 Pre-Deployment Checklist (Jo Already Done Hai)

- [x] **No /public in URLs:** Root `.htaccess` aur `public/index.php` configure ho chuke hain.
- [x] **Vite Assets Compiled:** `npm run build` run ho chuka hai (`public/build` mein production assets moujood hain). `node_modules` server par upload karne ki bilkul zaroorat nahi hai.
- [x] **Database Export:** Purana demo dump (`database/alishe_nails_backup.sql`) repository se
  **hata diya gaya hai** — us mein customer orders, emails aur admin password
  hashes thay. Schema `php artisan migrate --force` se banta hai; real data ke
  liye apna cPanel backup import karein.
- [x] **Automated Tests:** 113 tests pass in the local SQLite test environment; this does not guarantee zero defects or validate the production server.
- [ ] **Production Config:** Create the server's `.env` from `.env.example`; production secrets are not included in this repository.

---

## Step 1: AlifHost cPanel Mein PHP Version Set Karein

1. AlifHost cPanel login karein.
2. Search bar mein **"Select PHP Version"** ya **"MultiPHP Manager"** open karein.
3. Apni domain ke liye **PHP 8.2** ya **PHP 8.3** select karein.
4. **PHP Extensions** check karein ke yeh enabled hon:
   - `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `curl`, `gd`, `bcmath`, `zip`, `xml`, `ctype`, `tokenizer`.

---

## Step 2: Database Setup (cPanel MySQL Wizard)

1. cPanel mein **MySQL® Database Wizard** open karein:
   - **Step 1: Create A Database:** Naam likhein (e.g. `alishenails` -> Full name banega `username_alishenails`).
   - **Step 2: Create Database Users:** Username aur strong password enter karein (e.g. `username_admin`).
   - **Step 3: Add User to Database:** **"ALL PRIVILEGES"** par tick karein aur save karein.
2. cPanel Home par ja kar **phpMyAdmin** open karein.
3. Left side se apna naya database select karein.
4. Top menu mein **Import** par click karein.
5. **Choose File** par click karke apna SQL dump select karein (cPanel →
   **Backup → Download a Home Directory Backup** se milne wala dump, ya jo
   backup aap ne khud export kiya ho). Repository mein koi dump ship nahi hota.
   Agar aap pehli baar fresh install kar rahe hain to import skip karein aur
   terminal mein `php artisan migrate --force --seed` chala dein.
6. Niche **Import / Go** button daba dein. (Saare 25 tables aur sample products import ho jayenge).

---

## Step 3: Project Files Upload Karein (2 Asaan Tareeqay)

### Tareeqa A (Sub se Safe aur Professional — Recommended):

1. Apne computer par `alishe-nails` folder ka ZIP banayein (⚠️ **Note:** `node_modules` ko ZIP mein mat daalna, baki sab vendor samait zip karein).
2. cPanel **File Manager** open karein.
3. **`public_html` ke bahar** (Root directory `/home/username/`) mein `alishe-nails` naam ka folder banayein aur ZIP upload karke extract karein.
4. Ab `/home/username/alishe-nails/public` ke andar jitni bhi files/folders hain (`build`, `css`, `js`, `images`, `favicon`, `.htaccess`, `index.php`), un sab ko move karke **`public_html`** ke andar daal dein.
5. `public_html/index.php` ko edit karein aur line 14 & 18 ko aise update karein:
   ```php
   require __DIR__.'/../alishe-nails/vendor/autoload.php';
   $app = require_once __DIR__.'/../alishe-nails/bootstrap/app.php';
   ```

---

### Tareeqa B (Direct `public_html` Deployment):

Agar aap files bahar move nahi karna chahte aur saara project seedha `public_html` mein rakhna chahte hain:
1. Saara project seedha `public_html` mein upload & extract karein.
2. Jo root `.htaccess` humne project mein set ki hai, woh automatically bina `/public/` show kiye website ko live kar degi!

---

## Step 4: `.env` Configure Karein

1. Server par `alishe-nails` folder ke andar `.env` file banayein from `.env.example`; never commit or publish this file.
2. Yeh values update karein:
   ```ini
   APP_NAME="Alishe Nails"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=username_alishenails
   DB_USERNAME=username_admin
   DB_PASSWORD=Aapka_Database_Password

   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   ```
3. `yourdomain.com` ki jagah apni actual domain likhein.
4. `APP_KEY` ko document ya source control se copy na karein. Server par `php artisan key:generate --force` chala kar nayi key banayein. Is guide ke pehle se published key ko compromised samjhein; agar woh kisi live environment mein use hui hai, rotation plan karein, kyunke purani encrypted values decrypt nahi hongi aur active sessions invalidate honge.
5. Production `ADMIN_EMAIL` aur unique `ADMIN_SEED_PASSWORD` ko server ke protected environment/secrets manager mein set karein. Repository ka sample password kabhi use na karein; production seeder known default ko reject karta hai.

---

## Step 5: Product Images (Public Images Display Fix)

Catalogue photos `public/images/products/` mein rehti hain, is liye storage
symlink ki zaroorat nahi hai — `storage_link.php` helper hata diya gaya hai
(public symlink-banaane wala tool production par security risk tha).

Agar product images show na hon:
1. Terminal mein `php artisan images:doctor` chalayein — ye batata hai ke
   problem database reference mein hai ya file disk par nahi hai.
2. `php artisan images:doctor --repair` sirf un files ko link karta hai jinka
   match bilkul unambiguous ho.
3. Jo photos naam se match na hon, unhein admin → **Image Doctor**
   (`/admin/images`) par thumbnail dekh kar attach karein.
4. `php artisan optimize:clear` chala kar page reload karein.

---

## Step 6: AlifHost Cron Job (Background Tasks & Instagram Sync)

1. cPanel mein **Cron Jobs** open karein.
2. **Common Settings** mein **Once Per Minute (`* * * * *`)** select karein.
3. Command box mein likhein:
   ```bash
   /usr/local/bin/php /home/username/alishe-nails/artisan schedule:run >> /dev/null 2>&1
   ```
   *(Note: `username` ki jagah apna actual cPanel username likhein).*

---

## ✅ Deployment Complete!

Website open karein:
👉 `https://yourdomain.com`  
Admin Login:
👉 `https://yourdomain.com/admin/login`  
- **Email:** Set `ADMIN_EMAIL` in the protected server environment.
- **Password:** Set a unique `ADMIN_SEED_PASSWORD` in the protected server environment; no production credential is published in this guide.

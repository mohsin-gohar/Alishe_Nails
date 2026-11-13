# Deploying Alishe Nails to Hostinger (Shared Hosting)

This guide takes the project from your machine to `https://alishenails.com` on
Hostinger shared hosting.

---

## 1. Requirements on the server

| Requirement | Why | How to check |
|---|---|---|
| PHP **8.2+** | Laravel 11 | `php -v` in hPanel → Advanced → PHP |
| **GD extension with WebP support** | Image pipeline (auto WebP) | `php -r "print_r(gd_info());"` — look for `WebP Support => yes` |
| `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`, `tokenizer`, `curl`, `zip` | Laravel core | hPanel → PHP → Extensions |
| MySQL database + user | Data | hPanel → Databases |
| `.htaccess` support with `mod_rewrite` | Clean URLs + HTTPS | hPanel → Advanced → Apache |

> **If `WebP Support` is `no`:** in hPanel go to *Advanced → PHP → Extensions*,
> tick **gd**, then check *Extensions → Edit* and make sure `extension=gd` is
> not commented out. Without it, uploads are still stored — they simply stay
> JPEG instead of converting to WebP.

---

## 2. Upload the code

**Option A — Git (recommended, repeatable)**

```bash
git add -A
git commit -m "Enterprise performance pass: WebP pipeline, SEO, accessibility"
git branch -M main
git remote add hostinger git@<your-git-remote>:alishe-nails.git
git push -u hostinger main
```

Then in hPanel → *Advanced → Git*, add the repository and pull.

**Option B — File Manager / FTP**

Upload the whole project **except**:

- `vendor/`
- `node_modules/`
- `.env`
- `storage/framework/views/*`
- `storage/logs/*`

**IMPORTANT:** the `public/` folder must be the one Apache serves. In hPanel
set the domain's document root to `<project-root>/public`. The root
`.htaccess` then forwards internal requests into `public/`, and
`public/.htaccess` handles the front controller.

---

## 3. Environment file

Create `.env` in the project root from `.env.example` and set at minimum:

```dotenv
APP_NAME="Alishe Nails"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://alishenails.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=alishe_nails_db
DB_USERNAME=alishe_nails_user
DB_PASSWORD=your_real_password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@alishenails.com"
```

> `CACHE_STORE=array` is **local-development only**. In production it is
> per-process, so every request starts cold and the caching does nothing.
> Use `file` (fine for a boutique shop) or `redis` if Hostinger offers it.

---

## 4. Install & prepare

Run these in hPanel → *Advanced → Terminal* (or SSH):

```bash
cd ~/public_html/alishe-nails

composer install --no-dev --optimize-autoloader

php artisan key:generate          # only if .env has no APP_KEY yet
php artisan migrate --force
php artisan db:seed --force       # optional: demo catalogue + admin user
php artisan storage:link
php artisan optimize:clear
```

### Convert the existing images to WebP

The catalogue images that were uploaded before the WebP pipeline existed are
still JPEG. Convert them in one command:

```bash
php artisan images:optimize --prune
```

Output looks like:

```
Before   14.79 MB
After     2.78 MB
Saved    12.01 MB (81.2%)
```

New uploads convert automatically — nothing else to do.

### Regenerate the brand assets (optional)

```bash
php tools/generate-og-image.php   # 1200x630 social sharing card
php tools/generate-favicon.php    # square favicon
```

---

## 5. HTTPS

hPanel → *Security → SSL* → install the free Let's Encrypt certificate and
enable *Force HTTPS*. The root `.htaccess` also issues a 301 redirect to
HTTPS, so both paths end up on TLS.

---

## 6. Background workers

**Queue** (order emails, notifications):

```
php artisan queue:work --tries=3 --max-time=3600
```

Run it through hPanel → *Advanced → Supervisor* (or a cron fallback):

```
* * * * * cd /home/user/public_html/alishe-nails && php artisan schedule:run >> /dev/null 2>&1
```

---

## 7. Post-deploy verification

```bash
php artisan about                 # environment summary
php artisan images:doctor         # <-- image health check
php artisan images:optimize --dry-run
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`images:doctor` is the first thing to run when an image looks wrong. It
reports:

- whether GD and the WebP encoder are available on the server
- how many files are on disk, and how many are WebP
- how many products have an image record, and how many are missing
- database records that point at files which are not on disk
- files on disk that no product points at (often images whose database
  record was lost)

Add `--repair` to attempt to re-link orphaned files to products that have no
image:

```bash
php artisan images:doctor --repair
```

If the image files are present but the products have **no image records at all**
(this is what the live site had), point the importer at the folder that holds
the files and it will match each file to its product by slug/name, write the
WebP copy and rebuild the `media` records:

```bash
# 1. Make sure the new binary assets actually reached the server
git add -A && git commit -m "Deploy WebP catalogue" && git push
# then pull/deploy on Hostinger

# 2. Confirm where the problem is
php artisan images:doctor

# 3. Rebuild the image records from the files on disk
php artisan images:doctor --from=public/images/products

# 4. Clear caches so the new records render
php artisan optimize:clear
```

If a product name does not match a file name, rename the file to the product
slug (for example `classic-pearl-mauve.webp`) and re-run step 3. Anything the
importer cannot match is listed at the end of its output.

Then open the site and check:

- [ ] Home page loads, hero video plays
- [ ] A product page shows **WebP** images (right-click → Inspect → the
      `<img>` has a `<source type="image/webp">` above it)
- [ ] `/robots.txt` lists `Sitemap: https://alishenails.com/sitemap.xml`
- [ ] `/sitemap.xml` returns XML with product + category URLs
- [ ] Admin login at `/admin` works
- [ ] Uploading a new product image stores a `.webp` file in
      `public/images/products`

---

## 8. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| **Images show "Image unavailable"** | The products have no image record in the database | Run `php artisan images:doctor` — it says exactly which of the three causes applies. Then re-attach the image in the admin, or run `php artisan images:doctor --repair` if the file is already on disk |
| Images 404 after deploying | The new `.webp` files were never uploaded (they are new, untracked files) | Deploy the whole `public/` folder, and make sure you `git add -A` before pushing so the new binary assets are included |
| Images 404 but the DB points at `.jpg` | The files are not on the server at all | Upload `public/images/` as part of the deploy |
| Site returns 500 on every page | `.htaccess` has an unmatched `<IfModule>` tag | The root `.htaccess` in this project is balanced — re-upload it |
| 500 on every page after `.env` edit | `APP_KEY` empty or `APP_DEBUG=false` hides the real error | `php artisan config:clear`, then `php artisan about` |
| "Access denied for user" | DB credentials wrong | Check hPanel → Databases |
| Login loops / session drops | `SESSION_SECURE_COOKIE=true` but no HTTPS yet | Set it to `false` until the SSL certificate is active |
| Changes not visible | Stale caches | `php artisan optimize:clear` |
| Uploads stay JPEG instead of WebP | GD/WebP missing on the server | Enable `gd` in hPanel → PHP → Extensions, then restart PHP via hPanel |

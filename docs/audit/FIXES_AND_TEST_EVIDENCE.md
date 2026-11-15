# Fixes and test evidence — 2026-11-15

All commands were run in `F:\Xampp\htdocs\alishe-nails` on `main` after
`git pull origin main` (`2a5667c` → `0506ca5`).

## 1. Fixes made in this run

### FIX-01 — `public/css/app.css` no longer breaks the production build

Two selector rules were missing their declaration block, which left the file with
two extra closing braces and made Tailwind's parser abort:

```css
/* before */
.luxe-value-card h3,
  font-size: 0.95rem;
  margin-bottom: 6px;
  color: var(--espresso);
}

/* after */
.luxe-value-card h3 {
  font-size: 0.95rem;
  margin-bottom: 6px;
  color: var(--espresso);
}
```

Same shape of repair for `.shape-card h3` (line ~1788).

Evidence — before:

```
error during build:
[@tailwindcss/vite:generate:build] Missing opening {
file: F:/Xampp/htdocs/alishe-nails/public/css/app.css
```

Evidence — after:

```
✓ 2 modules transformed.
public/build/manifest.json           0.32 kB │ gzip: 0.17 kB
public/build/assets/app-cKIv6Lz5.css 48.65 kB │ gzip: 9.77 kB
public/build/assets/app-CBovk__f.js  12.56 kB │ gzip: 3.93 kB
✓ built in 506ms
```

### FIX-02 — one Content-Security-Policy instead of two

`.htaccess` (which Apache applies to CSS, JS and images without booting Laravel)
declared a second, weaker policy: `'unsafe-eval'`, `default-src 'self' data:
https:`, `style-src 'self' 'unsafe-inline' https:`. Browsers enforce the
intersection of multiple CSP headers, so the two files could silently disagree.
`.htaccess` now repeats the exact policy from
`app/Http/Middleware/SecurityHeaders.php`, and a test asserts they stay identical.

### FIX-03 — dead code removed

`Product::mediaSearchDirectories()` contained an empty `if (is_dir($pubSub)) { }`
block with an unused variable. Removed.

### FIX-04 — 34 tracked product photos restored

`git status` showed the whole `public/images/products/` folder as deleted in the
working tree while `HEAD` still contained the files. Committing from that state
would have deleted the original catalogue photos from the repository. Restored
with `git restore public/images/products`; `git status --short -- public/images`
is now empty and the folder holds 34 files.

## 2. Regression tests added

`tests/Feature/SecurityHeadersTest.php`

| Test | Proves |
| --- | --- |
| `test_htaccess_content_security_policy_matches_the_middleware` | `.htaccess` declares exactly one CSP and it is byte-identical to the header Laravel sends. |
| `test_htaccess_content_security_policy_does_not_allow_eval_or_wildcard_defaults` | The static-asset policy contains no `'unsafe-eval'` and no `data:`/wildcard `default-src`. |

## 3. Automated verification

| Command | Result |
| --- | --- |
| `php artisan test` | **150 passed (526 assertions)**, 0 failures |
| `php -l app/Models/Product.php` | No syntax errors detected |
| `php -l tests/Feature/SecurityHeadersTest.php` | No syntax errors detected |
| `php vendor/bin/pint --test` | clean after formatting the new test file |
| `npm run build` | `✓ built in 506ms` |
| `php artisan config:clear` / `route:clear` / `view:clear` | run (a stale `bootstrap/cache/config.php` was forcing MySQL in the test env and masking `phpunit.xml`'s sqlite settings) |

Note on the first test run: it failed with
`SQLSTATE[HY000] [2002] … Connection: mysql, Host: 127.0.0.1, Port: 3306`
because `bootstrap/cache/config.php` was stale. Clearing the config cache made
`phpunit.xml` (`DB_CONNECTION=sqlite`, `:memory:`) take effect. Anyone
reproducing the suite must clear the config cache first.

## 4. What is deliberately NOT claimed

* No photograph was generated, downloaded or invented.
* No database row was modified on any server.
* No production deployment was performed.
* No browser was driven; page evidence in `LIVE_BROWSER_RESULTS.md` is HTTP-level
  only, and the remaining interactive paths are listed as untested.
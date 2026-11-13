/**
 * Real-browser crawl of the storefront.
 *
 *   node tools/audit/crawl.mjs --base=http://127.0.0.1:8080 --max=60
 *
 * Visits every internal link it can find, in a real Chrome page, and records:
 * console errors, failed requests, broken images, missing alt text, empty
 * links, duplicate ids, horizontal overflow, missing meta description, and
 * form problems. Everything is written to tools/audit/report.json.
 */

import { launchChrome, newPage } from './cdp.mjs';
import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';

const args = Object.fromEntries(
  process.argv.slice(2).map((argument) => {
    const [key, value = 'true'] = argument.replace(/^--/, '').split('=');
    return [key, value];
  })
);

const BASE = (args.base ?? 'http://127.0.0.1:8080').replace(/\/$/, '');
const MAX_PAGES = Number(args.max ?? 60);
const OUT = args.out ?? 'tools/audit/report.json';
const MOBILE = args.mobile === 'true';

const SKIP_PATTERNS = [
  /\/logout/,
  /\/admin\/logout/,
  /\/newsletter\/unsubscribe/,
  /\/reviews\/.+\/destroy/,
  /\/cart\/remove/,
  /\/admin\/products\/.+\/delete/,
  /\/admin\/categories\/.+\/delete/,
  /\/admin\/coupons\/.+\/delete/,
  /mailto:/,
  /tel:/,
  /whatsapp\.com/,
  /instagram\.com/,
  /facebook\.com/,
  /tiktok\.com/,
];

const DIAGNOSTIC = `(() => {
  const absolute = (href) => { try { return new URL(href, location.href).href; } catch { return null; } };

  const images = [...document.querySelectorAll('img')];
  const anchorTags = [...document.querySelectorAll('a')];

  const overflow = (() => {
    const documentWidth = document.documentElement.scrollWidth;
    const viewportWidth = document.documentElement.clientWidth;
    const offenders = [];

    // Nested horizontal scrollers (comparison tables, code blocks) are a
    // deliberate design choice; only flag it when the whole page scrolls.
    if (documentWidth <= viewportWidth + 2) {
      return { scrollWidth: documentWidth, clientWidth: viewportWidth, offenders: [] };
    }

    for (const element of document.querySelectorAll('body *')) {
      const rect = element.getBoundingClientRect();
      if (rect.width === 0) continue;
      if (rect.right > viewportWidth + 2 && rect.left < viewportWidth) {
        const style = getComputedStyle(element);
        if (style.position === 'fixed' || style.visibility === 'hidden') continue;
        offenders.push({
          tag: element.tagName.toLowerCase(),
          className: (element.className || '').toString().slice(0, 80),
          right: Math.round(rect.right),
        });
      }
    }
    return { scrollWidth: documentWidth, clientWidth: viewportWidth, offenders: offenders.slice(0, 6) };
  })();

  const ids = [...document.querySelectorAll('[id]')].map((element) => element.id);
  const duplicateIds = ids.filter((id, index) => id && ids.indexOf(id) !== index);

  const labels = [...document.querySelectorAll('label')].map((label) => label.getAttribute('for')).filter(Boolean);
  const unlabeledInputs = [...document.querySelectorAll('input:not([type=hidden]):not([type=submit]):not([type=button]), select, textarea')]
    .filter((field) => {
      if (field.getAttribute('aria-label') || field.getAttribute('aria-labelledby') || field.getAttribute('title')) return false;
      if (field.closest('label')) return false;
      if (field.id && labels.includes(field.id)) return false;
      // Honeypots and hidden file pickers are intentionally not announced.
      if (field.hidden || field.getAttribute('aria-hidden') === 'true' || field.tabIndex === -1) return false;
      if (field.closest('[hidden], [aria-hidden="true"]')) return false;
      return true;
    })
    .map((field) => field.name || field.id || field.type);

  return {
    title: document.title,
    description: document.querySelector('meta[name="description"]')?.content ?? null,
    canonical: document.querySelector('link[rel="canonical"]')?.href ?? null,
    h1: [...document.querySelectorAll('h1')].map((element) => element.textContent.trim().slice(0, 120)),
    imageCount: images.length,
    brokenImages: images
      .filter((image) => image.complete && image.naturalWidth === 0)
      .map((image) => image.getAttribute('src') || image.currentSrc || '(no src)'),
    imagesMissingAlt: images.filter((image) => !image.hasAttribute('alt')).length,
    emptyAltDecorative: images.filter((image) => image.getAttribute('alt') === '').length,
    lazyUnloaded: images.filter((image) => image.loading === 'lazy' && !image.complete).length,
    unavailablePlaceholders: (document.body.innerText.match(/Image unavailable/gi) || []).length,
    emptyLinks: anchorTags
      .filter((a) => ['#', '', 'javascript:void(0)', 'javascript:;'].includes((a.getAttribute('href') || '').trim()))
      .map((a) => (a.textContent || a.getAttribute('aria-label') || '').trim().slice(0, 60))
      .filter(Boolean),
    externalNewTabNoRel: anchorTags
      .filter((a) => a.target === '_blank' && !/noopener/.test(a.rel))
      .map((a) => a.href)
      .slice(0, 10),
    submitButtonsWithoutType: [...document.querySelectorAll('form button:not([type])')].map((b) => (b.textContent || '').trim().slice(0, 40)),
    formsWithoutCsrf: [...document.querySelectorAll('form[method=post], form[method=POST]')]
      .filter((form) => !form.querySelector('input[name="_token"]'))
      .map((form) => form.action),
    duplicateIds: [...new Set(duplicateIds)].slice(0, 10),
    unlabeledInputs: [...new Set(unlabeledInputs)].slice(0, 10),
    overflow,
    links: anchorTags.map((a) => a.getAttribute('href')).filter(Boolean).map(absolute).filter(Boolean),
    textLength: document.body.innerText.trim().length,
  };
})()`;

const results = [];
const visited = new Set();
const queued = [];
const failures = [];

function normalise(url) {
  const parsed = new URL(url);
  parsed.hash = '';

  return parsed.href.replace(/\/$/, '') || parsed.href;
}

function shouldVisit(url) {
  if (!url.startsWith(BASE)) return false;
  if (SKIP_PATTERNS.some((pattern) => pattern.test(url))) return false;
  if (/\.(jpg|jpeg|png|webp|gif|svg|pdf|zip|mp4|webm|css|js|ico|woff2?|ttf)$/i.test(new URL(url).pathname)) return false;
  if (new URL(url).searchParams.has('page') && !args.paginate) return false;

  return true;
}

const chrome = await launchChrome();
const page = await newPage(chrome);

if (MOBILE) {
  await page.setViewport(390, 844);
}

const consoleErrors = [];
const networkErrors = [];

page.on((event) => {
  if (event.method === 'Runtime.consoleAPICalled' && event.params.type === 'error') {
    consoleErrors.push(event.params.args.map((arg) => arg.value ?? arg.description ?? arg.type).join(' '));
  }

  if (event.method === 'Runtime.exceptionThrown') {
    consoleErrors.push('Uncaught: ' + (event.params.exceptionDetails?.exception?.description ?? event.params.exceptionDetails?.text));
  }

  if (event.method === 'Log.entryAdded' && ['error', 'warning'].includes(event.params.entry.level)) {
    const entry = event.params.entry;
    if (entry.source === 'network' || entry.level === 'error') {
      networkErrors.push(`${entry.source}: ${entry.text} ${entry.url ?? ''}`.trim());
    }
  }

  if (event.method === 'Network.responseReceived') {
    const { response } = event.params;
    if (response.status >= 400 && response.url.startsWith(BASE)) {
      networkErrors.push(`HTTP ${response.status} ${response.url}`);
    }
  }

  if (event.method === 'Network.loadingFailed' && !event.params.canceled) {
    networkErrors.push(`FAILED ${event.params.errorText} ${event.params.type}`);
  }
});

async function visit(url) {
  const normalised = normalise(url);

  if (visited.has(normalised) || visited.size >= MAX_PAGES) return;

  visited.add(normalised);
  consoleErrors.length = 0;
  networkErrors.length = 0;

  let record = { url: normalised };

  try {
    const started = Date.now();
    await page.navigate(normalised, { timeout: 45_000 });

    // Scroll through the page so lazy images and reveal animations run.
    await page.evaluate(`(async () => {
      const step = Math.max(300, Math.floor(innerHeight * 0.8));
      for (let y = 0; y < document.body.scrollHeight; y += step) {
        scrollTo(0, y);
        await new Promise((resolve) => setTimeout(resolve, 40));
      }
      scrollTo(0, document.body.scrollHeight);
      await new Promise((resolve) => setTimeout(resolve, 250));
      scrollTo(0, 0);
    })()`);

    await page.waitForFunction("[...document.images].every((image) => image.complete)", 6_000);

    const diagnostics = await page.evaluate(DIAGNOSTIC);

    record = {
      ...record,
      status: 'ok',
      ms: Date.now() - started,
      ...diagnostics,
      consoleErrors: [...consoleErrors],
      networkErrors: [...networkErrors],
    };

    // Only follow links on HTML pages we successfully loaded.
    for (const link of diagnostics.links) {
      if (shouldVisit(link) && !visited.has(normalise(link))) {
        queued.push(link);
      }
    }
  } catch (error) {
    record = { ...record, status: 'error', error: error.message, consoleErrors: [...consoleErrors], networkErrors: [...networkErrors] };
    failures.push(record);
  }

  results.push(record);
  process.stdout.write(`${results.length}. ${record.status === 'ok' ? 'OK ' : 'ERR'} ${normalised}  ${record.ms ?? ''}ms\n`);
}

if (args.login) {
  const [email, password] = args.login.split(':');
  await page.navigate(BASE + '/admin/login');
  await page.evaluate(`(() => {
    const email = document.querySelector('input[name=email]');
    const password = document.querySelector('input[name=password]');
    if (!email || !password) return false;
    email.value = ${JSON.stringify(email)};
    password.value = ${JSON.stringify(password)};
    email.form.requestSubmit ? email.form.requestSubmit() : email.form.submit();
    return true;
  })()`);
  await page.waitForFunction('!location.pathname.includes("/login")', 20_000);
  console.log('Logged in as', email, '->', await page.evaluate('location.pathname'));
}

for (const start of (args.start ?? '/').split(',')) {
  queued.push(BASE + start);
}

while (queued.length && visited.size < MAX_PAGES) {
  const next = queued.shift();
  await visit(next);
}

await page.close();
await chrome.close();

mkdirSync(dirname(OUT), { recursive: true });
writeFileSync(OUT, JSON.stringify(results, null, 2));

// ---------------- Summary ----------------
const pageErrors = results.filter((page) => page.status === 'error');
const withConsole = results.filter((page) => page.consoleErrors?.length);
const withNetwork = results.filter((page) => page.networkErrors?.length);
const withBrokenImages = results.filter((page) => page.brokenImages?.length);
const withPlaceholders = results.filter((page) => page.unavailablePlaceholders > 0);
const withOverflow = results.filter((page) => page.overflow?.offenders?.length);
const missingDescription = results.filter((page) => !page.description);
const noH1 = results.filter((page) => !page.h1?.length);
const emptyLinks = results.filter((page) => page.emptyLinks?.length);
const duplicateIds = results.filter((page) => page.duplicateIds?.length);
const unlabeled = results.filter((page) => page.unlabeledInputs?.length);
const formsNoCsrf = results.filter((page) => page.formsWithoutCsrf?.length);
const emptyPages = results.filter((page) => page.status === 'ok' && page.textLength < 20);

console.log('\n================ SUMMARY ================');
console.log('Pages visited           :', results.length);
console.log('Page load failures      :', pageErrors.length);
console.log('Pages w/ console errors :', withConsole.length);
console.log('Pages w/ network errors :', withNetwork.length);
console.log('Pages w/ broken images  :', withBrokenImages.length);
console.log('Pages w/ placeholders   :', withPlaceholders.length);
console.log('Pages w/ overflow       :', withOverflow.length);
console.log('Pages w/o description   :', missingDescription.length);
console.log('Pages w/o h1            :', noH1.length);
console.log('Pages w/ empty links    :', emptyLinks.length);
console.log('Pages w/ duplicate ids  :', duplicateIds.length);
console.log('Pages w/ unlabeled form :', unlabeled.length);
console.log('Forms w/o CSRF          :', formsNoCsrf.length);
console.log('Suspiciously empty      :', emptyPages.length);

const show = (label, rows, field) => {
  if (!rows.length) return;
  console.log(`\n--- ${label} ---`);
  for (const row of rows.slice(0, 25)) {
    console.log(' ', row.url);
    console.log('   ', JSON.stringify(field(row)).slice(0, 500));
  }
};

show('PAGE ERRORS', pageErrors, (row) => row.error);
show('CONSOLE ERRORS', withConsole, (row) => row.consoleErrors);
show('NETWORK ERRORS', withNetwork, (row) => row.networkErrors);
show('BROKEN IMAGES', withBrokenImages, (row) => row.brokenImages);
show('IMAGE PLACEHOLDERS', withPlaceholders, (row) => row.unavailablePlaceholders);
show('OVERFLOW', withOverflow, (row) => row.overflow);
show('MISSING DESCRIPTION', missingDescription, (row) => row.title);
show('NO H1', noH1, (row) => row.title);
show('EMPTY LINKS', emptyLinks, (row) => row.emptyLinks);
show('DUPLICATE IDS', duplicateIds, (row) => row.duplicateIds);
show('UNLABELED FIELDS', unlabeled, (row) => row.unlabeledInputs);
show('FORMS WITHOUT CSRF', formsNoCsrf, (row) => row.formsWithoutCsrf);

console.log('\nReport written to', OUT);

/**
 * End-to-end admin flows, driven through a real Chrome page:
 * login, product CRUD (with a real file upload), category CRUD, shipping
 * CRUD, order status update, review moderation, homepage studio and the
 * password form's validation.
 *
 *   node tools/audit/flows-admin.mjs
 *
 * Creates and then deletes its own records. Restore database/database.sqlite
 * from the backup if you want a pristine local database afterwards.
 */

import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { launchChrome, newPage } from './cdp.mjs';

const BASE = (process.env.BASE ?? 'http://127.0.0.1:8080').replace(/\/$/, '');
const EMAIL = process.env.ADMIN_EMAIL ?? 'admin@alishenails.com';
const PASSWORD = process.env.ADMIN_PASSWORD ?? 'ChangeMe123!';
const results = [];

function record(name, ok, detail = '') {
  results.push({ name, ok, detail });
  console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? '  — ' + detail : ''}`);
}

async function check(name, fn) {
  try {
    const detail = await fn();
    record(name, true, detail ?? '');
  } catch (error) {
    record(name, false, error.message);
  }
}

function assert(condition, message) {
  if (!condition) throw new Error(message);
}

const chrome = await launchChrome();
const page = await newPage(chrome);

const consoleErrors = [];
page.on((event) => {
  if (event.method === 'Runtime.consoleAPICalled' && event.params.type === 'error') {
    consoleErrors.push(event.params.args.map((a) => a.value ?? a.description).join(' '));
  }
  if (event.method === 'Runtime.exceptionThrown') {
    consoleErrors.push('Uncaught: ' + (event.params.exceptionDetails?.exception?.description ?? event.params.exceptionDetails?.text));
  }
  if (event.method === 'Page.javascriptDialogOpening') {
    // The delete buttons use confirm(); accept so the flow can continue.
    page.send('Page.handleJavaScriptDialog', { accept: true }).catch(() => {});
  }
});

const text = (selector) => page.evaluate(`(document.querySelector(${JSON.stringify(selector)})?.innerText ?? '').trim()`);
const exists = (selector) => page.evaluate(`!!document.querySelector(${JSON.stringify(selector)})`);
const value = (selector) => page.evaluate(`document.querySelector(${JSON.stringify(selector)})?.value ?? null`);
const count = (selector) => page.evaluate(`document.querySelectorAll(${JSON.stringify(selector)}).length`);
const bodyText = () => page.evaluate('document.body.innerText');
const path = () => page.evaluate('location.pathname');

async function goto(target) {
  const url = target.startsWith('http') ? target : BASE + target;
  await page.navigate(url, { timeout: 45_000 });
  await page.waitForFunction('document.readyState === "complete"', 20_000);
}

async function afterNavigation() {
  await page.waitForFunction('document.readyState === "complete"', 20_000);
  await new Promise((resolve) => setTimeout(resolve, 250));
}

async function click(selector) {
  const navigation = page.waitForEvent('Page.loadEventFired', 20_000).catch(() => null);
  const clicked = await page.evaluate(`(() => {
    const el = document.querySelector(${JSON.stringify(selector)});
    if (!el) return false;
    el.click();
    return true;
  })()`);
  assert(clicked, `Element not found: ${selector}`);
  await navigation;
  await afterNavigation();
}

async function submit(selector) {
  const navigation = page.waitForEvent('Page.loadEventFired', 20_000).catch(() => null);
  const submitted = await page.evaluate(`(() => {
    const form = document.querySelector(${JSON.stringify(selector)});
    if (!form) return false;
    form.requestSubmit ? form.requestSubmit() : form.submit();
    return true;
  })()`);
  assert(submitted, `Form not found: ${selector}`);
  await navigation;
  await afterNavigation();
}

async function fill(selector, valueToSet) {
  const filled = await page.evaluate(`(() => {
    const el = document.querySelector(${JSON.stringify(selector)});
    if (!el) return false;
    const proto = el instanceof HTMLTextAreaElement ? HTMLTextAreaElement.prototype : HTMLInputElement.prototype;
    Object.getOwnPropertyDescriptor(proto, 'value').set.call(el, ${JSON.stringify(valueToSet)});
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    return true;
  })()`);
  assert(filled, `Field not found: ${selector}`);
}

async function select(selector, optionValue) {
  const ok = await page.evaluate(`(() => {
    const el = document.querySelector(${JSON.stringify(selector)});
    if (!el) return false;
    el.value = ${JSON.stringify(optionValue)};
    el.dispatchEvent(new Event('change', { bubbles: true }));
    return true;
  })()`);
  assert(ok, `Select not found: ${selector}`);
}

/** Sets a real file on a file input through the DevTools protocol. */
async function setFile(selector, filePath) {
  const { root } = await page.send('DOM.getDocument', { depth: -1, pierce: true });
  const { nodeId } = await page.send('DOM.querySelector', { nodeId: root.nodeId, selector });
  assert(nodeId, `File input not found: ${selector}`);
  await page.send('DOM.setFileInputFiles', { nodeId, files: [filePath] });
}

/** Submits the form inside the table row that contains the given text. */
async function submitRowForm(rowText, formSelector, method = null) {
  const navigation = page.waitForEvent('Page.loadEventFired', 20_000).catch(() => null);
  const submitted = await page.evaluate(`(() => {
    const row = [...document.querySelectorAll('tbody tr')].find((r) => r.innerText.includes(${JSON.stringify(rowText)}));
    if (!row) return false;
    let form = row.querySelector(${JSON.stringify(formSelector)});
    ${method ? `
    if (form) {
      const declared = form.querySelector('input[name="_method"]');
      if (!declared || declared.value.toUpperCase() !== ${JSON.stringify(method)}) {
        form = [...row.querySelectorAll(${JSON.stringify(formSelector)})]
          .find((candidate) => (candidate.querySelector('input[name="_method"]')?.value ?? '').toUpperCase() === ${JSON.stringify(method)});
      }
    }
    ` : ''}
    if (!form) return false;
    form.requestSubmit ? form.requestSubmit() : form.submit();
    return true;
  })()`);
  assert(submitted, `No ${formSelector} in the row for "${rowText}"`);
  await navigation;
  await afterNavigation();
}

async function openRowEdit(rowText) {
  const navigation = page.waitForEvent('Page.loadEventFired', 20_000).catch(() => null);
  const clicked = await page.evaluate(`(() => {
    const row = [...document.querySelectorAll('tbody tr')].find((r) => r.innerText.includes(${JSON.stringify(rowText)}));
    if (!row) return false;
    const link = row.querySelector('a[href*="/edit"]');
    if (!link) return false;
    link.click();
    return true;
  })()`);
  assert(clicked, `No edit link in the row for "${rowText}"`);
  await navigation;
  await afterNavigation();
}

const imagePath = resolve('public/images/products/rosy-quartz-ombre.webp');
assert(existsSync(imagePath), 'Missing test image: ' + imagePath);

const stamp = Date.now();
const productName = `Audit Product ${stamp}`;
const productEdited = `${productName} Updated`;
const categoryName = `Audit Category ${stamp}`;
const categoryEdited = `${categoryName} Renamed`;
const areaName = `Audit Zone ${stamp}`;

// ------------------------------------------------------------------- login
await goto('/admin/login');

await check('Admin login rejects a wrong password', async () => {
  await fill('input[name=email]', EMAIL);
  await fill('input[name=password]', 'definitely-wrong');
  await submit('form[action$="/admin/login"]');
  const alert = await page.evaluate(`document.querySelector('.alert, .error')?.innerText ?? ''`);
  assert(/invalid|credentials|incorrect/i.test(alert), 'No error shown for a bad password: ' + alert);
  return alert.replace(/\s+/g, ' ').slice(0, 70);
});

await check('Admin can sign in', async () => {
  await fill('input[name=email]', EMAIL);
  await fill('input[name=password]', PASSWORD);
  await submit('form[action$="/admin/login"]');
  assert((await path()) === '/admin', 'Landed on ' + (await path()));
  assert(await exists('.admin-sidebar'), 'Admin layout missing');
  return 'dashboard';
});

await check('Dashboard shows the KPI cards', async () => {
  await goto('/admin');
  const cards = await count('.admin-card');
  assert(cards >= 3, 'Only ' + cards + ' dashboard cards');
  const heading = await text('h1');
  return `${cards} cards · ${heading}`;
});

await check('Removed sections are gone from the admin sidebar', async () => {
  const sidebar = await text('.admin-sidebar');
  const removed = ['Coupons', 'Sellers', 'Subscriptions', 'Campaign Pitches'];
  const present = removed.filter((label) => sidebar.includes(label));
  assert(present.length === 0, 'Still linked: ' + present.join(', '));
  return 'coupons, sellers, subscriptions, pitches absent';
});

// ----------------------------------------------------------------- product
await check('Product search filters the table', async () => {
  await goto('/admin/products?q=ombre');
  const rows = await page.evaluate('[...document.querySelectorAll("tbody tr")].filter((r) => r.querySelector("form")).length');
  assert(rows >= 1, 'No rows matched ombre');
  const all = await bodyText();
  assert(/ombre/i.test(all), 'Search term not reflected');
  return rows + ' row(s)';
});

await check('Admin can create a product with an uploaded image', async () => {
  await goto('/admin/products/create');
  await fill('#name', productName);
  await select('#category_id', await page.evaluate('document.querySelector("#category_id").options[1]?.value ?? ""'));
  await fill('#price', '4321');
  await fill('#stock', '7');
  await fill('#short_description', 'Automated audit product.');
  await fill('#description', 'Created by tools/audit/flows-admin.mjs to verify the admin product form end to end.');
  await fill('#tags', 'audit, test');

  await setFile('input[data-media-picker="image"]', imagePath);
  const added = await page.waitForFunction('document.querySelectorAll(".media-manager__item").length > 0', 10_000);
  assert(added, 'Media manager did not register the uploaded image');

  await submit('form[action$="/admin/products"]');

  const body = await bodyText();
  assert(body.includes(productName), 'Product not listed after save');
  const alert = await page.evaluate(`document.querySelector('.alert')?.innerText ?? ''`);
  assert(/created|added|saved/i.test(alert), 'No success message: ' + alert);
  return alert.replace(/\s+/g, ' ').slice(0, 60);
});

await check('Admin can edit the product', async () => {
  await goto('/admin/products?q=' + encodeURIComponent(productName));
  await openRowEdit(productName);
  assert(await exists('#name'), 'Edit form did not open');
  await fill('#name', productEdited);
  await fill('#price', '4999');
  await submit('form[action*="/admin/products/"]');

  const body = await bodyText();
  assert(body.includes(productEdited), 'Edited name not shown');
  assert(/4,999/.test(body), 'Edited price not shown');
  return productEdited;
});

await check('Admin can delete the product', async () => {
  await goto('/admin/products?q=' + encodeURIComponent(productEdited));
  await submitRowForm(productEdited, 'form[action*="/admin/products/"]');
  const body = await bodyText();
  assert(!body.includes(productEdited), 'Product still listed after delete');
  return 'deleted';
});

// ---------------------------------------------------------------- category
await check('Admin can create a category', async () => {
  await goto('/admin/categories/create');
  await fill('#name', categoryName);
  await fill('#description', 'Created by the automated admin flow test.');
  await fill('#sort_order', '99');
  await submit('form[action$="/admin/categories"]');
  const body = await bodyText();
  assert(body.includes(categoryName), 'Category not listed');
  return categoryName;
});

await check('Admin can edit the category', async () => {
  await goto('/admin/categories');
  await openRowEdit(categoryName);
  await fill('#name', categoryEdited);
  await submit('form[action*="/admin/categories/"]');
  const body = await bodyText();
  assert(body.includes(categoryEdited), 'Renamed category not shown');
  return categoryEdited;
});

await check('Admin can delete the category', async () => {
  await goto('/admin/categories');
  await submitRowForm(categoryEdited, 'form[action*="/admin/categories/"]');
  const body = await bodyText();
  assert(!body.includes(categoryEdited), 'Category still listed');
  return 'deleted';
});

// ---------------------------------------------------------------- shipping
await check('Admin can create a shipping rate', async () => {
  await goto('/admin/shipping/create');
  await fill('#city', 'Karachi');
  await fill('#area', areaName);
  await fill('#delivery_fee', '350');
  await fill('#free_shipping_threshold', '5000');
  await submit('form[action$="/admin/shipping"]');
  const row = await page.evaluate(`(() => {
    const row = [...document.querySelectorAll('tbody tr')].find((r) => r.innerText.includes(${JSON.stringify(areaName)}));
    return row ? row.innerText.replace(/\\s+/g, ' ').trim() : '';
  })()`);
  assert(row, 'New shipping rate not listed');
  assert(/350/.test(row), 'Fee not shown: ' + row);
  return row.slice(0, 70);
});

await check('Admin can edit a shipping rate', async () => {
  await goto('/admin/shipping');
  await openRowEdit(areaName);
  await fill('#delivery_fee', '400');
  await submit('form[action*="/admin/shipping/"]');
  const row = await page.evaluate(`(() => {
    const row = [...document.querySelectorAll('tbody tr')].find((r) => r.innerText.includes(${JSON.stringify(areaName)}));
    return row ? row.innerText.replace(/\\s+/g, ' ').trim() : '';
  })()`);
  assert(/400/.test(row), 'Updated fee not shown: ' + row);
  return row.slice(0, 70);
});

await check('Admin can delete a shipping rate', async () => {
  await goto('/admin/shipping');
  await submitRowForm(areaName, 'form[action*="/admin/shipping/"]');
  const body = await bodyText();
  assert(!body.includes(areaName), 'Shipping rate still listed');
  return 'deleted';
});

// ------------------------------------------------------------------- order
await check('Admin can change an order status and restore it', async () => {
  await goto('/admin/orders');
  const orderHref = await page.evaluate(`(() => {
    const link = document.querySelector('a[href*="/admin/orders/"]');
    return link ? new URL(link.href).pathname : null;
  })()`);
  assert(orderHref, 'No order link found');

  await goto(orderHref);
  const original = await value('select[name=status]');
  const target = original === 'confirmed' ? 'processing' : 'confirmed';

  await select('select[name=status]', target);
  await submit('form[action$="/status"]');
  const alert = await page.evaluate(`document.querySelector('.alert')?.innerText ?? ''`);
  assert(/marked as/i.test(alert), 'No status confirmation: ' + alert);
  assert((await value('select[name=status]')) === target, 'Status did not change');

  await select('select[name=status]', original);
  await submit('form[action$="/status"]');
  assert((await value('select[name=status]')) === original, 'Status did not revert');

  return `${original} → ${target} → ${original}`;
});

// ------------------------------------------------------------------ review
await check('Admin can approve, hide and delete a review', async () => {
  const id = execFileSync('php', ['tools/seed-review.php'], { encoding: 'utf8' }).trim();
  assert(/^\d+$/.test(id), 'Could not seed a review: ' + id);

  await goto('/admin/reviews');
  const marker = 'AUDIT-PENDING-REVIEW';

  const sealed = await page.evaluate(`(() => {
    const row = [...document.querySelectorAll('tbody tr')].find((r) => r.innerText.includes(${JSON.stringify(marker)}));
    if (!row) return null;
    const form = row.querySelector('form[action*="/approve"]');
    if (!form) return null;
    form.requestSubmit();
    return true;
  })()`);
  assert(sealed, 'Pending review row not found (or no approve form)');
  await afterNavigation();

  const approved = await page.evaluate(`(() => {
    const row = [...document.querySelectorAll('tbody tr')].find((r) => r.innerText.includes(${JSON.stringify(marker)}));
    return row ? row.innerText.includes('Approved') : false;
  })()`);
  assert(approved, 'Review was not approved');

  const hidden = await page.evaluate(`(() => {
    const row = [...document.querySelectorAll('tbody tr')].find((r) => r.innerText.includes(${JSON.stringify(marker)}));
    if (!row) return null;
    const form = row.querySelector('form[action*="/reject"]');
    if (!form) return null;
    form.requestSubmit();
    return true;
  })()`);
  assert(hidden, 'No hide form after approval');
  await afterNavigation();

  const pendingAgain = await page.evaluate(`(() => {
    const row = [...document.querySelectorAll('tbody tr')].find((r) => r.innerText.includes(${JSON.stringify(marker)}));
    return row ? row.innerText.includes('Pending') : false;
  })()`);
  assert(pendingAgain, 'Review was not hidden again');

  await submitRowForm(marker, 'form[action*="/admin/reviews/"]', 'DELETE');
  const body = await bodyText();
  assert(!body.includes(marker), 'Review still listed after delete');

  return 'approved → hidden → deleted';
});

// ---------------------------------------------------------------- homepage
await check('Homepage studio adds and restores a product', async () => {
  await goto('/admin/homepage');

  const before = await count('[data-hp-selected]');
  const candidate = await page.evaluate(`(() => {
    const selected = new Set([...document.querySelectorAll('[data-hp-selected]')].map((el) => el.dataset.id));
    const item = [...document.querySelectorAll('[data-hp-item]')].find((el) => !selected.has(el.dataset.id));
    return item ? item.dataset.id : null;
  })()`);
  assert(candidate, 'No unselected product available');

  await page.evaluate(`document.querySelector('[data-hp-item][data-id="${candidate}"] [data-add]').click()`);
  assert((await count('[data-hp-selected]')) === before + 1, 'Product was not added in the UI');

  await submit('#homepage-form');
  assert((await count('[data-hp-selected]')) === before + 1, 'Add did not persist');

  await page.evaluate(`document.querySelector('[data-hp-selected][data-id="${candidate}"] [data-remove]').click()`);
  await submit('#homepage-form');
  assert((await count('[data-hp-selected]')) === before, 'Remove did not persist');

  return `${before} → ${before + 1} → ${before}`;
});

// ---------------------------------------------------------------- settings
await check('Password form validates before changing anything', async () => {
  await goto('/admin/settings');
  await fill('#current_password', PASSWORD);
  await fill('#new_password', 'SomeNewPass123!');
  await fill('#new_password_confirmation', 'DoesNotMatch123!');
  await submit('form[action$="/settings/password"]');
  const alert = await page.evaluate(`document.querySelector('.alert')?.innerText ?? ''`);
  assert(/fix the following|match|confirmation/i.test(alert), 'No validation message: ' + alert);
  return alert.replace(/\s+/g, ' ').slice(0, 70);
});

// ------------------------------------------------------------------ logout
await check('Admin logout returns to the login screen', async () => {
  await page.evaluate(`document.querySelector('form[action$="/admin/logout"]')?.requestSubmit()`);
  await afterNavigation();
  assert((await path()) === '/admin/login', 'Landed on ' + (await path()));
  return '/admin/login';
});

await check('Admin pages are protected when signed out', async () => {
  await goto('/admin/products');
  assert((await path()).startsWith('/admin/login'), 'Protected page opened: ' + (await path()));
  return 'redirected';
});

await page.close();
await chrome.close();

// ------------------------------------------------------------------ summary
const failed = results.filter((row) => !row.ok);

console.log('\n================ ADMIN FLOW SUMMARY ================');
console.log(`Passed: ${results.length - failed.length}/${results.length}`);
if (failed.length) {
  console.log('\nFAILURES:');
  for (const row of failed) console.log('  ✗', row.name, '—', row.detail);
}
if (consoleErrors.length) {
  console.log('\nBROWSER CONSOLE ERRORS:');
  for (const error of [...new Set(consoleErrors)].slice(0, 20)) console.log('  ', error);
}

process.exit(failed.length ? 1 : 0);

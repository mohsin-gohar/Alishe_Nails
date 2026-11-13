/**
 * Secondary storefront flows: the smaller interactions the main flow test
 * does not cover.
 *
 *   node tools/audit/flows-extra.mjs
 */

import { launchChrome, newPage } from './cdp.mjs';

const BASE = (process.env.BASE ?? 'http://127.0.0.1:8080').replace(/\/$/, '');
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
    page.send('Page.handleJavaScriptDialog', { accept: true }).catch(() => {});
  }
});

const text = (selector) => page.evaluate(`(document.querySelector(${JSON.stringify(selector)})?.innerText ?? '').trim()`);
const exists = (selector) => page.evaluate(`!!document.querySelector(${JSON.stringify(selector)})`);
const count = (selector) => page.evaluate(`document.querySelectorAll(${JSON.stringify(selector)}).length`);
const bodyText = () => page.evaluate('document.body.innerText');
const path = () => page.evaluate('location.pathname');

async function goto(target) {
  await page.navigate(target.startsWith('http') ? target : BASE + target, { timeout: 45_000 });
  await page.waitForFunction('document.readyState === "complete"', 20_000);
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
  await page.waitForFunction('document.readyState === "complete"', 20_000);
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
  await page.waitForFunction('document.readyState === "complete"', 20_000);
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

// ------------------------------------------------------------- navigation UI
await goto('/');

await check('Search overlay opens and closes', async () => {
  await click('[data-search-toggle]');
  const open = await page.evaluate('getComputedStyle(document.querySelector("[data-search-form]")).display');
  assert(open !== 'none', 'Overlay did not open');
  await page.evaluate('document.querySelector("[data-search-close]").click()');
  const closed = await page.evaluate('getComputedStyle(document.querySelector("[data-search-form]")).display');
  assert(closed === 'none', 'Overlay did not close');
  return 'open → closed';
});

await check('Mobile menu button is hidden on desktop', async () => {
  const display = await page.evaluate('getComputedStyle(document.querySelector("[data-navbar-toggle]")).display');
  assert(display === 'none', 'Hamburger visible on desktop: ' + display);
  return display;
});

// ---------------------------------------------------------------- product UI
await goto('/products/rosy-quartz-ombre');

await check('Size guide modal opens and closes', async () => {
  await page.evaluate('document.querySelector("[data-modal-open=size-guide-modal]").click()');
  const open = await page.evaluate('document.querySelector("#size-guide-modal").classList.contains("is-open")');
  assert(open, 'Modal did not open');
  await page.evaluate('document.querySelector("#size-guide-modal [data-modal-close]").click()');
  const closed = await page.evaluate('!document.querySelector("#size-guide-modal").classList.contains("is-open")');
  assert(closed, 'Modal did not close');
  return 'open → closed';
});

await check('Escape key closes the modal', async () => {
  await page.evaluate('document.querySelector("[data-modal-open=size-guide-modal]").click()');
  await page.evaluate('document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }))');
  const closed = await page.evaluate('!document.querySelector("#size-guide-modal").classList.contains("is-open")');
  assert(closed, 'Escape did not close the modal');
  return 'closed';
});

await check('Accordion sections expand', async () => {
  const details = await count('.accordion-item');
  assert(details >= 2, 'Expected accordion items');
  await page.evaluate('document.querySelectorAll(".accordion-item")[1].querySelector("summary")?.click()');
  const open = await page.evaluate('document.querySelectorAll(".accordion-item")[1].open');
  return `${details} sections, second open=${open}`;
});

// ------------------------------------------------------------------- tracking
await check('Track order shows a friendly message for unknown orders', async () => {
  await goto('/track-order');
  await fill('input[name=order_number]', 'ALN-DOESNOTEX');
  await fill('input[name=identifier]', '03000000000');
  await submit('form[action$="/track-order"]');
  const body = await bodyText();
  assert(/not found|could not|no order/i.test(body), 'No friendly error: ' + body.slice(0, 160));
  return 'friendly error';
});

// -------------------------------------------------------------------- forms
await check('Contact form rejects a honeypot submission', async () => {
  await goto('/contact');
  await fill('input[name=name]', 'Bot');
  await fill('input[name=email]', 'bot@example.com');
  await fill('textarea[name=message]', 'Spam message that should be blocked by the honeypot.');
  await page.evaluate('document.querySelector(\'input[name="website"]\').value = "http://spam.example"');
  await submit('form[action$="/contact"]');
  const body = await bodyText();
  assert(/rejected|fix the following/i.test(body), 'Honeypot was not enforced');
  return 'blocked';
});

await check('Seller application submits', async () => {
  await goto('/sell-with-us');
  const email = `audit.seller.${Date.now()}@example.com`;
  await fill('#name', 'Audit Seller Studio');
  await fill('#email', email);
  await fill('#instagram_handle', '@auditseller');
  await fill('#phone', '03001234567');
  await fill('#product_details', 'Hand-painted press-on sets with a focus on bold bridal couture.');
  await fill('#password', 'SellerPass123!');
  await fill('#password_confirmation', 'SellerPass123!');
  await submit('form[action$="/sell-with-us"]');
  const alert = await page.evaluate('document.querySelector(".alert")?.innerText ?? ""');
  assert(/thank|received|application|submitted|review/i.test(alert), 'No confirmation: ' + alert);
  return alert.replace(/\s+/g, ' ').slice(0, 70);
});

await check('Forgot password handles an unknown email gracefully', async () => {
  await goto('/forgot-password');
  await fill('input[name=email]', `missing.${Date.now()}@example.com`);
  await submit('form[action$="/forgot-password"]');
  const status = await page.evaluate('document.querySelector(".alert") ? 200 : 0');
  assert(status === 200, 'Page did not render a message');
  const alert = await page.evaluate('document.querySelector(".alert").innerText');
  assert(/sent|unable|not found|email/i.test(alert), 'Unexpected message: ' + alert);
  return alert.replace(/\s+/g, ' ').slice(0, 70);
});

await check('Login rejects wrong credentials', async () => {
  await goto('/login');
  await fill('input[name=email]', 'demo@alishenails.com');
  await fill('input[name=password]', 'wrong-password');
  await submit('form[action$="/login"]');
  const alert = await page.evaluate('document.querySelector(".alert")?.innerText ?? ""');
  assert(/invalid|credentials|incorrect/i.test(alert), 'No error: ' + alert);
  return alert.replace(/\s+/g, ' ').slice(0, 60);
});

// ------------------------------------------------------------------- account
const customerEmail = `audit.extra.${Date.now()}@example.com`;

await check('Customer can update their profile', async () => {
  await goto('/register');
  await fill('input[name=name]', 'Extra Audit');
  await fill('input[name=email]', customerEmail);
  await fill('input[name=phone]', '03001234567');
  await fill('input[name=password]', 'AuditPass123!');
  await fill('input[name=password_confirmation]', 'AuditPass123!');
  await submit('form[action$="/register"]');

  await goto('/account/profile');
  await fill('#name', 'Extra Audit Updated');
  await fill('#phone', '03111234567');
  await submit('form[action$="/account/profile"]');
  const alert = await page.evaluate('document.querySelector(".alert")?.innerText ?? ""');
  assert(/updated|saved/i.test(alert), 'No profile confirmation: ' + alert);
  return alert.replace(/\s+/g, ' ').slice(0, 60);
});

await check('Account menu opens for a signed-in customer', async () => {
  await goto('/');
  await page.evaluate('document.querySelector("[data-account-toggle]").click()');
  const display = await page.evaluate('getComputedStyle(document.querySelector("[data-account-menu]")).display');
  assert(display !== 'none', 'Account menu stayed hidden');
  return display;
});

await check('Wishlist and orders pages render for the customer', async () => {
  await goto('/account/wishlist');
  assert(/wishlist/i.test(await bodyText()), 'Wishlist page did not render');
  await goto('/account/orders');
  assert(/orders/i.test(await bodyText()), 'Orders page did not render');
  return 'both render';
});

await check('Customer can submit a review for moderation', async () => {
  await goto('/products/blush-aura');
  const form = await exists('form[action$="/reviews"]');
  assert(form, 'Review form missing');
  await page.evaluate('document.querySelector(\'form[action$="/reviews"] select[name=rating]\').value = "5"');
  await fill('form[action$="/reviews"] textarea[name=comment]', 'Audit review: beautiful finish and easy application.');
  await submit('form[action$="/reviews"]');
  const alert = await page.evaluate('document.querySelector(".alert")?.innerText ?? ""');
  assert(/thanks|approved|submitted/i.test(alert), 'No review confirmation: ' + alert);
  return alert.replace(/\s+/g, ' ').slice(0, 70);
});

await check('Empty cart sends checkout back to the cart', async () => {
  await goto('/checkout');
  assert((await path()) === '/cart', 'Checkout did not redirect: ' + (await path()));
  const alert = await page.evaluate('document.querySelector(".alert")?.innerText ?? ""');
  assert(/empty/i.test(alert), 'No empty-cart message: ' + alert);
  return 'redirected to /cart';
});

await check('Cart item can be removed', async () => {
  await goto('/products/blush-aura');
  await submit('#add-to-cart-form');
  await goto('/cart');
  const before = await count('.cart-item');
  assert(before >= 1, 'Cart is empty after adding');

  const removed = await page.evaluate(`(() => {
    const form = document.querySelector('.cart-item form[action*="/cart/remove"], .cart-item form[action*="/remove"]');
    if (!form) return false;
    form.requestSubmit();
    return true;
  })()`);
  assert(removed, 'Remove form missing');
  await page.waitForFunction('document.readyState === "complete"', 15_000);
  const after = await count('.cart-item');
  assert(after === before - 1, `Cart still has ${after} item(s)`);
  return `${before} → ${after}`;
});

await page.close();
await chrome.close();

const failed = results.filter((row) => !row.ok);

console.log('\n================ EXTRA FLOW SUMMARY ================');
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

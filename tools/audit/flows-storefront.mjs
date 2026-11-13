/**
 * End-to-end storefront flows, driven through a real Chrome page.
 *
 *   node tools/audit/flows-storefront.mjs
 *
 * Creates an order, a customer, a newsletter subscriber and a contact
 * message in the local database. Restore database/database.sqlite afterwards
 * if you want a clean slate.
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
});

const text = (selector) => page.evaluate(`(document.querySelector(${JSON.stringify(selector)})?.innerText ?? '').trim()`);
const exists = (selector) => page.evaluate(`!!document.querySelector(${JSON.stringify(selector)})`);
const value = (selector) => page.evaluate(`document.querySelector(${JSON.stringify(selector)})?.value ?? null`);
const attr = (selector, name) => page.evaluate(`document.querySelector(${JSON.stringify(selector)})?.getAttribute(${JSON.stringify(name)}) ?? null`);
const count = (selector) => page.evaluate(`document.querySelectorAll(${JSON.stringify(selector)}).length`);
const href = (selector) => page.evaluate(`document.querySelector(${JSON.stringify(selector)})?.href ?? null`);

async function goto(path) {
  await page.navigate(BASE + path, { timeout: 40_000 });
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

async function pause(ms = 600) {
  await new Promise((resolve) => setTimeout(resolve, ms));
}

// ---------------------------------------------------------------- homepage
await goto('/');

await check('Homepage hero video is present and loaded', async () => {
  const video = await page.evaluate(`(() => {
    const v = document.querySelector('video');
    if (!v) return null;
    return { src: v.currentSrc || v.getAttribute('src'), readyState: v.readyState, paused: v.paused, poster: v.getAttribute('poster') };
  })()`);
  assert(video, 'No <video> element');
  assert(video.src, 'Video has no source');
  assert(video.readyState >= 1, 'Video metadata did not load (readyState ' + video.readyState + ')');
  return video.src;
});

await check('Only one H1 on the homepage', async () => {
  const count = await page.evaluate('document.querySelectorAll("h1").length');
  assert(count === 1, count + ' H1 elements');
  return await text('h1');
});

await check('Search toggle opens the search overlay', async () => {
  await click('[data-search-toggle]');
  assert(await exists('[data-search-form]'), 'Search form missing');
  const display = await page.evaluate('getComputedStyle(document.querySelector("[data-search-form]")).display');
  assert(display !== 'none', 'Search form stayed hidden');
  return display;
});

await check('Search finds a product', async () => {
  await fill('[data-search-form] input[name=q]', 'ombre');
  await submit('[data-search-form] form');
  assert(page.wsUrl !== null, 'no page');
  const url = await page.evaluate('location.href');
  assert(url.includes('q=ombre'), 'Search did not reach the shop: ' + url);
  const cards = await count('.product-card');
  assert(cards > 0, 'No results for "ombre"');
  return cards + ' result(s)';
});

// ------------------------------------------------------------------- shop
await goto('/shop');

await check('Shop lists products with prices', async () => {
  const cards = await count('.product-card');
  assert(cards >= 8, 'Only ' + cards + ' product cards');
  const price = await text('.product-card .product-card__price');
  assert(/PKR/.test(price), 'No price rendered: ' + price);
  return cards + ' cards, first price ' + price;
});

await check('Sorting by price changes the order', async () => {
  await select('select[name=sort]', 'price_low');
  await page.waitForEvent('Page.loadEventFired', 20_000).catch(() => null);
  await page.waitForFunction('location.href.includes("sort=price_low")', 10_000);
  const prices = await page.evaluate(`[...document.querySelectorAll('.product-card__price')].map((el) => Number(el.innerText.replace(/[^0-9]/g, ''))).filter(Boolean)`);
  const sorted = [...prices].sort((a, b) => a - b);
  assert(JSON.stringify(prices) === JSON.stringify(sorted), 'Prices are not ascending: ' + prices.join(','));
  return prices.join(' < ');
});

await check('Category filter narrows the results', async () => {
  await goto('/shop?category%5B0%5D=luxury-edition');
  const cards = await count('.product-card');
  assert(cards > 0 && cards < 12, 'Expected a subset, got ' + cards);
  const chips = await text('.active-filters');
  assert(chips.toLowerCase().includes('luxury-edition'), 'Filter chip missing');
  return cards + ' products';
});

await check('Removing a filter chip clears it', async () => {
  const remove = await href('.filter-chip__remove');
  assert(remove, 'No remove link');
  await page.navigate(remove);
  const chips = await count('.filter-chip');
  assert(chips === 0, 'Chip still present');
  return 'cleared';
});

// -------------------------------------------------------------------- PDP
await goto('/products/rosy-quartz-ombre');

await check('Product gallery thumbnails switch the main image', async () => {
  const mainImage = '.product-gallery__slide.is-active img';

  const before = await page.evaluate(`document.querySelector(${JSON.stringify(mainImage)})?.currentSrc ?? ''`);
  await page.evaluate('document.querySelectorAll("[data-media-thumb]")[1]?.click()');
  await pause(400);
  const after = await page.evaluate(`document.querySelector(${JSON.stringify(mainImage)})?.currentSrc ?? ''`);
  assert(after, 'No main image');
  assert(before !== after, 'Thumbnail did not change the main image');
  return after.split('/').pop();
});

await check('Size selector updates the hidden input', async () => {
  await page.evaluate(`[...document.querySelectorAll('#size-option-group .option-pill')].find((el) => el.dataset.value === 'M').click()`);
  const size = await value('#product-size-input');
  assert(size === 'M', 'Size is ' + size);
  return size;
});

await check('Quantity stepper increments and decrements', async () => {
  await page.evaluate('document.querySelector("[data-action=increase]").click()');
  const plus = await value('#product-qty-input');
  assert(plus === '2', 'Increase gave ' + plus);
  await page.evaluate('document.querySelector("[data-action=decrease]").click()');
  const minus = await value('#product-qty-input');
  assert(minus === '1', 'Decrease gave ' + minus);
  return '2 then 1';
});

await check('Live subtotal updates with quantity', async () => {
  await page.evaluate('document.querySelector("[data-action=increase]").click()');
  await pause(200);
  const subtotal = await text('#product-live-subtotal');
  assert(/5,?000|5000/.test(subtotal.replace(/[^0-9]/g, '')), 'Subtotal not updated: ' + subtotal);
  return subtotal;
});

await check('Add to cart shows confirmation and updates the badge', async () => {
  await page.evaluate('document.querySelector("#product-qty-input").value = "1"');
  await submit('#add-to-cart-form');
  const badge = (await text('.navbar__cart-count')) || '0';
  assert(Number(badge) >= 1, 'Cart badge is ' + badge);
  const alert = await page.evaluate(`document.querySelector('.alert')?.innerText ?? ''`);
  assert(/added to your cart/i.test(alert), 'No add-to-cart confirmation: ' + alert);
  return 'badge ' + badge;
});

await check('Buy Now jumps straight to checkout', async () => {
  await goto('/products/rosy-quartz-ombre');
  await page.evaluate('document.querySelector("button[name=buy_now]").click()');
  await page.waitForFunction('location.pathname === "/checkout"', 15_000);
  return await page.evaluate('location.pathname');
});

// ------------------------------------------------------------------- cart
await goto('/cart');

await check('Cart shows the item with a working quantity stepper', async () => {
  const items = await count('.cart-item');
  assert(items >= 1, 'Cart is empty');
  await page.evaluate('document.querySelector(".cart-item [data-action=increase]").click()');
  await page.waitForEvent('Page.loadEventFired', 20_000).catch(() => null);
  await page.waitForFunction('document.readyState === "complete"', 15_000);
  const qty = await page.evaluate('document.querySelector(".cart-item input[name=qty]")?.value');
  assert(Number(qty) === 2, 'Quantity is ' + qty);
  return 'qty ' + qty;
});

await check('Cart summary shows a delivery charge', async () => {
  const summary = await text('.summary-card');
  assert(/PKR\s?300|PKR\s?[1-9]/.test(summary), 'No delivery amount in the summary: ' + summary.slice(0, 200));
  return summary.replace(/\s+/g, ' ').slice(0, 160);
});

// --------------------------------------------------------------- checkout
await goto('/checkout');

await check('Checkout page renders the order form', async () => {
  assert(await exists('#checkout-form'), 'Checkout form missing');
  const items = await count('.order-summary-item');
  assert(items >= 1, 'No order summary items');
  return items + ' item(s)';
});

await check('Selecting an area fetches the delivery fee without reload', async () => {
  await select('#area', 'DHA (Defence)');
  await pause(1200);
  const fee = await text('#checkout-shipping-amount');
  assert(/300/.test(fee), 'Fee did not update: ' + fee);
  const totalText = await text('#checkout-total-amount');
  const buttonTotal = await text('#checkout-btn-total');
  assert(totalText === buttonTotal, 'Header total and button total differ: ' + totalText + ' / ' + buttonTotal);
  return fee + ' · ' + totalText;
});

await check('Invalid coupon shows a friendly error', async () => {
  await fill('#coupon-code', 'NOPE123');
  await submit('.checkout-coupon__form');
  const alert = await page.evaluate(`document.querySelector('.alert')?.innerText ?? ''`);
  assert(/invalid|expired/i.test(alert), 'No invalid coupon message: ' + alert);
  return alert.replace(/\s+/g, ' ').slice(0, 80);
});

await check('Required-field validation blocks an empty order', async () => {
  await goto('/checkout');
  const before = await page.evaluate('location.pathname');
  await page.evaluate('document.querySelector("#btn-place-order").click()');
  await pause(800);
  const after = await page.evaluate('location.pathname');
  const invalid = await page.evaluate('document.querySelectorAll("#checkout-form :invalid").length');
  assert(invalid > 0, 'Browser did not flag any required fields');
  return invalid + ' invalid field(s)';
});

await check('A complete order is placed and the success page opens', async () => {
  await fill('#email', 'audit.customer@example.com');
  await fill('#first_name', 'Audit');
  await fill('#last_name', 'Customer');
  await fill('#address', 'House 1, Street 2, Phase 6');
  await select('#area', 'DHA (Defence)');
  await fill('#phone', '03001234567');
  await pause(900);

  const totals = await text('#checkout-total-amount');

  await page.evaluate('document.querySelector("#btn-place-order").click()');
  await page.waitForFunction('location.pathname.startsWith("/checkout/success/")', 25_000);

  const heading = await text('h1');
  const orderNumber = await attr('[data-order-number]', 'data-order-number');
  assert(heading.length > 0, 'No confirmation heading');
  assert(orderNumber, 'No order number on the confirmation page');
  globalThis.__auditOrderNumber = orderNumber;

  return orderNumber + ' · ' + totals;
});

// -------------------------------------------------------------- track order
await check('Track order finds the order that was just placed', async () => {
  const orderNumber = globalThis.__auditOrderNumber;
  assert(orderNumber, 'Could not read the order number from the success page');

  await goto('/track-order');
  await fill('input[name=order_number]', orderNumber.trim());
  await fill('input[name=identifier]', '03001234567');
  await submit('form[action$="/track-order"]');

  const body = await page.evaluate('document.body.innerText');
  assert(!/not found/i.test(body), 'Order was not found');
  assert(body.includes(orderNumber.trim()), 'Order number not echoed back');
  return orderNumber.trim();
});

// ----------------------------------------------------------------- contact
await check('Contact form submits successfully', async () => {
  await goto('/contact');
  await fill('input[name=name]', 'Audit Customer');
  await fill('input[name=email]', 'audit.contact@example.com');
  await fill('input[name=phone]', '03001234567');
  await fill('textarea[name=message]', 'This is an automated audit message to verify the contact form.');
  await submit('form[action$="/contact"]');
  const alert = await page.evaluate(`document.querySelector('.alert')?.innerText ?? ''`);
  assert(/thank|received|sent|message/i.test(alert), 'No contact confirmation: ' + alert);
  return alert.replace(/\s+/g, ' ').slice(0, 80);
});

// -------------------------------------------------------------- newsletter
await check('Newsletter subscribe works', async () => {
  await goto('/');
  await page.evaluate(`(() => {
    const form = [...document.querySelectorAll('form')].find((f) => f.action.includes('newsletter/subscribe'));
    const input = form.querySelector('input[type=email]');
    input.value = 'audit.newsletter@example.com';
    form.requestSubmit();
  })()`);
  await page.waitForFunction('document.readyState === "complete"', 15_000);
  const alert = await page.evaluate(`document.querySelector('.alert')?.innerText ?? ''`);
  assert(/subscribed|thank|already/i.test(alert), 'No newsletter confirmation: ' + alert);
  return alert.replace(/\s+/g, ' ').slice(0, 80);
});

// -------------------------------------------------------------------- auth
await check('Customer registration works', async () => {
  await goto('/register');
  const email = `audit.${Date.now()}@example.com`;
  await fill('input[name=name]', 'Audit Customer');
  await fill('input[name=email]', email);
  await fill('input[name=phone]', '03001234567');
  await fill('input[name=password]', 'AuditPass123!');
  await fill('input[name=password_confirmation]', 'AuditPass123!');
  await submit('form[action$="/register"]');

  const path = await page.evaluate('location.pathname');
  assert(path !== '/register', 'Registration redirected back to the form');
  const signedIn = await exists('form[action$="/logout"]');
  assert(signedIn, 'Not signed in after registration');
  return path + ' as ' + email;
});

await check('Wishlist toggle works for a signed-in customer', async () => {
  await goto('/products/blush-aura');
  const form = await exists('form[action*="wishlist/"]');
  assert(form, 'Wishlist form missing');
  await page.evaluate('document.querySelector(\'form[action*="wishlist/"]\').requestSubmit()');
  await page.waitForFunction('document.readyState === "complete"', 15_000);
  const saved = await page.evaluate('document.body.innerText.includes("Saved to Wishlist")');
  assert(saved, 'Wishlist did not save');
  return 'saved';
});

await check('Logout returns to the storefront', async () => {
  await page.evaluate('document.querySelector(\'form[action$="/logout"]\')?.requestSubmit()');
  await page.waitForFunction('document.readyState === "complete"', 15_000);
  await pause(400);
  const stillSignedIn = await exists('form[action$="/logout"]');
  assert(!stillSignedIn, 'Still signed in');
  return await page.evaluate('location.pathname');
});

// ------------------------------------------------------------------ errors
await check('Custom 404 page renders', async () => {
  const response = await fetch(BASE + '/this-page-does-not-exist', { redirect: 'manual' });
  assert(response.status === 404, 'Status ' + response.status);
  const body = await response.text();
  assert(/404|not found/i.test(body), 'No custom 404 content');
  return '404';
});

await page.close();
await chrome.close();

// ------------------------------------------------------------------ summary
const failed = results.filter((row) => !row.ok);

console.log('\n================ FLOW SUMMARY ================');
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

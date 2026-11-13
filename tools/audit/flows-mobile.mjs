/**
 * Mobile (390x844) interaction flows — the parts of the site that only exist
 * on a phone: the hamburger menu, the search overlay, and form usability.
 *
 *   node tools/audit/flows-mobile.mjs
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

await page.setViewport(390, 844);

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
const count = (selector) => page.evaluate(`document.querySelectorAll(${JSON.stringify(selector)}).length`);
const bodyText = () => page.evaluate('document.body.innerText');
const path = () => page.evaluate('location.pathname');
const display = (selector) => page.evaluate(`getComputedStyle(document.querySelector(${JSON.stringify(selector)})).display`);
const viewportWidth = () => page.evaluate('document.documentElement.clientWidth');
const scrollWidth = () => page.evaluate('document.documentElement.scrollWidth');

async function goto(target) {
  await page.navigate(target.startsWith('http') ? target : BASE + target, { timeout: 45_000 });
  await page.waitForFunction('document.readyState === "complete"', 20_000);
  await new Promise((resolve) => setTimeout(resolve, 250));
}

async function click(selector) {
  const clicked = await page.evaluate(`(() => {
    const el = document.querySelector(${JSON.stringify(selector)});
    if (!el) return false;
    el.click();
    return true;
  })()`);
  assert(clicked, `Element not found: ${selector}`);
  await new Promise((resolve) => setTimeout(resolve, 200));
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

async function submit(selector) {
  const navigation = page.waitForEvent('Page.loadEventFired', 25_000).catch(() => null);
  const submitted = await page.evaluate(`(() => {
    const form = document.querySelector(${JSON.stringify(selector)});
    if (!form) return false;
    form.requestSubmit ? form.requestSubmit() : form.submit();
    return true;
  })()`);
  assert(submitted, `Form not found: ${selector}`);
  await navigation;
  await page.waitForFunction('document.readyState === "complete"', 20_000);
  await new Promise((resolve) => setTimeout(resolve, 250));
}

/** Every element matching the selector must fit inside the phone viewport. */
async function fits(selector) {
  return page.evaluate(`(() => {
    const width = document.documentElement.clientWidth;
    const offenders = [...document.querySelectorAll(${JSON.stringify(selector)})]
      .filter((el) => {
        const style = getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden') return false;
        const rect = el.getBoundingClientRect();
        if (rect.width === 0 && rect.height === 0) return false;
        return rect.left < -1 || rect.right > width + 1;
      })
      .map((el) => el.className || el.tagName);
    return { width, offenders: offenders.slice(0, 5) };
  })()`);
}

// --------------------------------------------------------------- navigation
await goto('/');

await check('Phone shows the hamburger and hides the desktop links', async () => {
  const toggle = await display('[data-navbar-toggle]');
  const links = await display('.navbar__links');
  assert(toggle !== 'none', 'Hamburger hidden at 390px');
  assert(links === 'none', 'Desktop links visible at 390px: ' + links);
  return `hamburger ${toggle}, desktop links ${links}`;
});

await check('Hamburger opens and closes the mobile menu', async () => {
  await click('[data-navbar-toggle]');
  const open = await display('.navbar__mobile-links');
  assert(open !== 'none', 'Menu did not open');
  const expanded = await page.evaluate('document.querySelector("[data-navbar-toggle]").getAttribute("aria-expanded")');
  assert(expanded === 'true', 'aria-expanded was ' + expanded);

  await click('[data-navbar-toggle]');
  const closed = await display('.navbar__mobile-links');
  assert(closed === 'none', 'Menu did not close');
  return 'open → close';
});

await check('Escape closes the mobile menu', async () => {
  await click('[data-navbar-toggle]');
  await page.evaluate('document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }))');
  await new Promise((resolve) => setTimeout(resolve, 150));
  const closed = await display('.navbar__mobile-links');
  assert(closed === 'none', 'Escape did not close the menu');
  return 'closed';
});

await check('A mobile menu link navigates to the shop', async () => {
  await click('[data-navbar-toggle]');
  const navigation = page.waitForEvent('Page.loadEventFired', 25_000).catch(() => null);
  await page.evaluate(`(() => {
    const link = [...document.querySelectorAll('.navbar__mobile-links a')].find((a) => a.innerText.trim() === 'Shop');
    link?.click();
  })()`);
  await navigation;
  await page.waitForFunction('document.readyState === "complete"', 20_000);
  assert((await path()) === '/shop', 'Landed on ' + (await path()));
  return '/shop';
});

// ------------------------------------------------------------------- search
await goto('/');

await check('Search overlay opens and returns mobile results', async () => {
  await click('[data-search-toggle]');
  const open = await display('[data-search-form]');
  assert(open !== 'none', 'Search overlay did not open');

  await fill('[data-search-form] input[name=q]', 'ombre');
  await submit('[data-search-form] form');

  assert((await path()) === '/shop', 'Search did not land on /shop');
  const cards = await count('.product-card');
  assert(cards >= 1, 'Search returned no products');
  return `${cards} result(s)`;
});

// --------------------------------------------------------------------- shop
await goto('/shop');

await check('Shop controls and product cards fit the phone screen', async () => {
  const overflow = await scrollWidth();
  const width = await viewportWidth();
  assert(overflow <= width + 2, `Page scrolls sideways: ${overflow}px > ${width}px`);

  const controls = await fits('.shop-toolbar, .shop-sidebar, .product-card, .pagination');
  assert(controls.offenders.length === 0, 'Off-screen: ' + controls.offenders.join(', '));

  const sort = await display('.shop-toolbar select');
  assert(sort !== 'none', 'Sort control hidden on mobile');
  const cards = await count('.product-card');
  return `${cards} cards, no sideways scroll`;
});

// ------------------------------------------------------------------ product
await goto('/products/blush-aura');

await check('Product page is usable on a phone', async () => {
  const width = await viewportWidth();
  const overflow = await scrollWidth();
  assert(overflow <= width + 2, `Page scrolls sideways: ${overflow}px > ${width}px`);

  const sizes = await count('#size-option-group .option-pill');
  assert(sizes >= 1, 'No size options on mobile');

  const before = await page.evaluate('document.querySelector("#product-size-input").value');
  await page.evaluate(`(() => {
    const pill = [...document.querySelectorAll('#size-option-group .option-pill')].find((p) => p.dataset.value === 'M');
    pill?.click();
  })()`);
  await new Promise((resolve) => setTimeout(resolve, 150));
  const after = await page.evaluate('document.querySelector("#product-size-input").value');
  assert(after === 'M', 'Tapping a size did not update the hidden input: ' + after);
  assert(before === 'S', 'Unexpected default size: ' + before);

  const button = await page.evaluate(`(() => {
    const btn = document.querySelector('#add-to-cart-form button[type=submit]');
    if (!btn) return null;
    const rect = btn.getBoundingClientRect();
    const style = getComputedStyle(btn);
    return {
      visible: style.display !== 'none' && rect.width > 0,
      fits: rect.left >= -1 && rect.right <= document.documentElement.clientWidth + 1,
      height: Math.round(rect.height),
      label: btn.innerText.trim(),
    };
  })()`);
  assert(button, 'Add to cart button missing');
  assert(button.visible && button.fits, 'Add to cart button is off-screen');
  assert(button.height >= 40, 'Add to cart tap target is only ' + button.height + 'px tall');
  return `${sizes} sizes · "${button.label}" ${button.height}px tall`;
});

await check('Product can be added to the cart from the phone', async () => {
  await submit('#add-to-cart-form');
  const badge = await text('.navbar__cart-count');
  assert(Number(badge) >= 1, 'Cart badge did not update: ' + badge);
  return 'badge ' + badge;
});

// --------------------------------------------------------------------- cart
await goto('/cart');

await check('Cart works at phone width with the flat delivery fee', async () => {
  const width = await viewportWidth();
  const overflow = await scrollWidth();
  assert(overflow <= width + 2, `Cart scrolls sideways: ${overflow}px > ${width}px`);

  const body = await bodyText();
  assert(/PKR 300/.test(body), 'Delivery fee missing from the mobile cart');
  assert((await count('.cart-item')) >= 1, 'No cart items rendered');

  const steppers = await page.evaluate(`(() => {
    const buttons = [...document.querySelectorAll('.cart-item .qty-selector button')];
    if (!buttons.length) return null;
    return buttons.map((btn) => Math.round(btn.getBoundingClientRect().height));
  })()`);
  assert(steppers, 'Quantity stepper missing');
  assert(steppers.every((height) => height >= 32), 'Stepper tap targets too small: ' + steppers.join(', '));
  return `delivery PKR 300 · steppers ${steppers.join('/')}px`;
});

// ----------------------------------------------------------------- checkout
await goto('/checkout');

await check('Checkout form fits the phone and shows the delivery fee', async () => {
  const width = await viewportWidth();
  const overflow = await scrollWidth();
  assert(overflow <= width + 2, `Checkout scrolls sideways: ${overflow}px > ${width}px`);

  const fields = await fits('input:not([type=hidden]):not(.hp-field), select:not(.hp-field), textarea:not(.hp-field), .checkout-submit, button[type=submit]');
  assert(fields.offenders.length === 0, 'Off-screen fields: ' + fields.offenders.join(', '));

  const body = await bodyText();
  assert(/PKR 300/.test(body), 'Delivery fee missing at checkout');

  const placeOrder = await page.evaluate(`(() => {
    const btn = [...document.querySelectorAll('button[type=submit]')].find((b) => /place order|confirm order/i.test(b.innerText));
    if (!btn) return null;
    const rect = btn.getBoundingClientRect();
    return { height: Math.round(rect.height), label: btn.innerText.trim() };
  })()`);
  assert(placeOrder, 'Place order button missing');
  assert(placeOrder.height >= 40, 'Place order button only ' + placeOrder.height + 'px tall');
  return `delivery PKR 300 · "${placeOrder.label}" ${placeOrder.height}px tall`;
});

await page.close();
await chrome.close();

const failed = results.filter((row) => !row.ok);

console.log('\n================ MOBILE FLOW SUMMARY ================');
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

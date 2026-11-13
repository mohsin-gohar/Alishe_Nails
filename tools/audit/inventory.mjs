/**
 * Dumps every interactive element on the key pages, so nothing is left
 * untested by hand.
 *
 *   node tools/audit/inventory.mjs
 */

import { launchChrome, newPage } from './cdp.mjs';

const BASE = (process.env.BASE ?? 'http://127.0.0.1:8080').replace(/\/$/, '');

const PAGES = [
  '/',
  '/shop',
  '/products/rosy-quartz-ombre',
  '/cart',
  '/checkout',
  '/contact',
  '/track-order',
  '/login',
  '/register',
  '/about',
  '/how-to-apply',
  '/policies',
  '/sell-with-us',
  '/seller/login',
  '/account/wishlist',
  '/admin/login',
];

const EXPRESSION = `(() => {
  const pick = (el) => ({
    tag: el.tagName.toLowerCase(),
    text: (el.innerText || el.value || el.getAttribute('aria-label') || '').trim().replace(/\\s+/g, ' ').slice(0, 70),
    href: el.getAttribute('href') || undefined,
    type: el.getAttribute('type') || undefined,
    name: el.getAttribute('name') || undefined,
    action: el.getAttribute('action') || undefined,
    method: el.getAttribute('method') || undefined,
    classes: (el.className || '').toString().slice(0, 70) || undefined,
    disabled: el.disabled || undefined,
    onclick: el.getAttribute('onclick') || undefined,
    data: [...el.attributes].filter((a) => a.name.startsWith('data-')).map((a) => a.name + '=' + a.value.slice(0, 30)).join(' ').slice(0, 120) || undefined,
    aria: el.getAttribute('aria-expanded') || el.getAttribute('aria-controls') || undefined,
  });

  return {
    forms: [...document.querySelectorAll('form')].map((form) => ({
      action: form.getAttribute('action'),
      method: (form.getAttribute('method') || 'get').toLowerCase(),
      fields: [...form.querySelectorAll('input,select,textarea')].map((f) => f.name || f.type).filter(Boolean),
      buttons: [...form.querySelectorAll('button, input[type=submit]')].map((b) => (b.innerText || b.value || '').trim()).filter(Boolean),
    })),
    buttons: [...document.querySelectorAll('button')].map(pick),
    links: [...document.querySelectorAll('a')].map(pick).filter((l) => l.href && l.href !== '#'),
    inputs: [...document.querySelectorAll('input, select, textarea')]
      .filter((el) => !['hidden'].includes(el.type))
      .map((el) => ({ ...pick(el), required: el.required || undefined, options: el.tagName === 'SELECT' ? [...el.options].map((o) => o.text.trim()).slice(0, 20) : undefined })),
  };
})()`;

const chrome = await launchChrome();
const page = await newPage(chrome);
page.on((event) => {
  if (event.method === 'Runtime.consoleAPICalled' && event.params.type === 'error') {
    console.log('  CONSOLE ERROR:', event.params.args.map((a) => a.value ?? a.description).join(' '));
  }
});

for (const path of PAGES) {
  console.log('\n============================================================');
  console.log(path);
  console.log('============================================================');

  try {
    await page.navigate(BASE + path, { timeout: 30_000 });

    if (path === '/checkout') {
      console.log('  (empty cart — will redirect)');
    }

    const data = await page.evaluate(EXPRESSION);
    console.log('FORMS:', JSON.stringify(data.forms, null, 1));
    console.log('BUTTONS:');
    for (const button of data.buttons) console.log('  ', JSON.stringify(button));
    console.log('INPUTS:');
    for (const input of data.inputs) console.log('  ', JSON.stringify(input));
    console.log('LINKS:', data.links.length);
    for (const link of data.links.slice(0, 40)) console.log('  ', link.text, '->', link.href);
  } catch (error) {
    console.log('  ERROR:', error.message);
  }
}

await page.close();
await chrome.close();

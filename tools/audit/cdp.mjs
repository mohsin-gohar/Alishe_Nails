/**
 * Tiny Chrome DevTools Protocol client (zero dependencies).
 *
 * Node 22+ ships a global WebSocket, so we can drive a real Chrome instance
 * without pulling Puppeteer/Playwright into the project.
 */

import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const CHROME_CANDIDATES = [
  'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
  '/usr/bin/google-chrome',
  '/usr/bin/chromium',
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
];

export async function launchChrome({ port = 9333, headless = true } = {}) {
  const { existsSync } = await import('node:fs');
  const binary = CHROME_CANDIDATES.find((candidate) => existsSync(candidate));

  if (!binary) {
    throw new Error('No Chrome/Edge binary found.');
  }

  const profile = mkdtempSync(join(tmpdir(), 'alishe-audit-'));

  const args = [
    `--remote-debugging-port=${port}`,
    `--user-data-dir=${profile}`,
    '--no-first-run',
    '--no-default-browser-check',
    '--disable-extensions',
    '--disable-background-networking',
    '--disable-sync',
    '--disable-features=Translate,MediaRouter',
    '--window-size=1440,900',
    '--hide-scrollbars',
    'about:blank',
  ];

  if (headless) {
    args.unshift('--headless=new', '--disable-gpu');
  }

  const child = spawn(binary, args, { stdio: 'ignore', detached: false });

  // Wait for the debugging endpoint to come up.
  let version = null;
  for (let attempt = 0; attempt < 100; attempt++) {
    try {
      const response = await fetch(`http://127.0.0.1:${port}/json/version`);
      if (response.ok) {
        version = await response.json();
        break;
      }
    } catch {
      // not ready yet
    }
    await new Promise((resolve) => setTimeout(resolve, 100));
  }

  if (!version) {
    child.kill();
    throw new Error('Chrome debugging endpoint did not start.');
  }

  return {
    port,
    version,
    async close() {
      try {
        await fetch(`http://127.0.0.1:${port}/json/close/` + (await currentTargetId(port)));
      } catch {
        // ignore
      }
      child.kill();
      try {
        rmSync(profile, { recursive: true, force: true });
      } catch {
        // ignore
      }
    },
  };
}

async function currentTargetId(port) {
  try {
    const list = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
    return list[0]?.id ?? '';
  } catch {
    return '';
  }
}

export class Page {
  constructor(wsUrl) {
    this.wsUrl = wsUrl;
    this.ws = null;
    this.nextId = 1;
    this.pending = new Map();
    this.listeners = new Set();
    this.events = [];
  }

  async open() {
    this.ws = new WebSocket(this.wsUrl);

    await new Promise((resolve, reject) => {
      this.ws.addEventListener('open', resolve, { once: true });
      this.ws.addEventListener('error', reject, { once: true });
    });

    this.ws.addEventListener('message', (message) => {
      const payload = JSON.parse(message.data);

      if (payload.id && this.pending.has(payload.id)) {
        const { resolve, reject } = this.pending.get(payload.id);
        this.pending.delete(payload.id);

        if (payload.error) {
          reject(new Error(payload.error.message));
        } else {
          resolve(payload.result);
        }

        return;
      }

      if (payload.method) {
        this.events.push(payload);
        for (const listener of this.listeners) {
          listener(payload);
        }
      }
    });

    await Promise.all([
      this.send('Page.enable'),
      this.send('Runtime.enable'),
      this.send('Network.enable'),
      this.send('Log.enable'),
    ]);

    return this;
  }

  send(method, params = {}, timeout = 60_000) {
    const id = this.nextId++;

    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        this.pending.delete(id);
        reject(new Error(`CDP ${method} timed out after ${timeout}ms`));
      }, timeout);

      this.pending.set(id, {
        resolve: (value) => {
          clearTimeout(timer);
          resolve(value);
        },
        reject: (error) => {
          clearTimeout(timer);
          reject(error);
        },
      });

      this.ws.send(JSON.stringify({ id, method, params }));
    });
  }

  on(listener) {
    this.listeners.add(listener);
    return () => this.listeners.delete(listener);
  }

  waitForEvent(method, timeout = 30_000) {
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        off();
        reject(new Error(`Timed out waiting for ${method}`));
      }, timeout);

      const off = this.on((event) => {
        if (event.method === method) {
          clearTimeout(timer);
          off();
          resolve(event.params);
        }
      });
    });
  }

  async navigate(url, { waitUntil = 'load', timeout = 30_000 } = {}) {
    this.events = [];

    const loaded = this.waitForEvent('Page.loadEventFired', timeout).catch(() => null);

    await this.send('Page.navigate', { url });

    if (waitUntil === 'load') {
      await loaded;
    }

    await this.evaluate('document.readyState');
  }

  async evaluate(expression, { awaitPromise = true, timeout = 30_000 } = {}) {
    const result = await this.send('Runtime.evaluate', {
      expression,
      returnByValue: true,
      awaitPromise,
      userGesture: true,
    });

    if (result.exceptionDetails) {
      throw new Error(result.exceptionDetails.text + ' ' + (result.exceptionDetails.exception?.description ?? ''));
    }

    return result.result.value;
  }

  async waitForFunction(expression, timeout = 15_000) {
    const started = Date.now();

    while (Date.now() - started < timeout) {
      try {
        if (await this.evaluate(expression)) {
          return true;
        }
      } catch {
        // keep waiting
      }

      await new Promise((resolve) => setTimeout(resolve, 100));
    }

    return false;
  }

  async screenshot(path, { fullPage = false } = {}) {
    const { writeFileSync } = await import('node:fs');

    let clip;
    if (fullPage) {
      const metrics = await this.send('Page.getLayoutMetrics');
      const size = metrics.cssContentSize ?? metrics.contentSize;
      clip = { x: 0, y: 0, width: size.width, height: size.height, scale: 1 };
    }

    const { data } = await this.send('Page.captureScreenshot', {
      format: 'png',
      captureBeyondViewport: fullPage,
      ...(clip ? { clip } : {}),
    });

    writeFileSync(path, Buffer.from(data, 'base64'));
  }

  async setViewport(width, height) {
    await this.send('Emulation.setDeviceMetricsOverride', {
      width,
      height,
      deviceScaleFactor: 1,
      mobile: width < 700,
    });
  }

  async close() {
    try {
      this.ws?.close();
    } catch {
      // ignore
    }
  }
}

export async function newPage(chrome) {
  const response = await fetch(`http://127.0.0.1:${chrome.port}/json/new?about:blank`, {
    method: 'PUT',
  });

  const target = await response.json();
  const page = new Page(target.webSocketDebuggerUrl);
  await page.open();

  return page;
}

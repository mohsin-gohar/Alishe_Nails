/**
 * Web search through Exa, for the storefront and content tooling.
 *
 *   node tools/exa-search.mjs "handmade press-on nail trends 2026" --num=5
 *   node tools/exa-search.mjs "gel nail aftercare" --json
 *
 * Reads EXA_API_KEY from the environment, falling back to the project's .env
 * (where Laravel keeps it in local development). Requests highlights, so each
 * result carries the excerpts that actually answer the query instead of the
 * whole page.
 */

import { existsSync } from 'node:fs';
import { join } from 'node:path';
import Exa from 'exa-js';

const ROOT = join(import.meta.dirname, '..');

/**
 * Load .env once when the key was not exported by the shell. A broken .env is
 * not fatal here: the key may still be present in the environment.
 */
function loadLocalEnv() {
  if (process.env.EXA_API_KEY?.trim()) {
    return;
  }

  const envFile = join(ROOT, '.env');

  if (!existsSync(envFile)) {
    return;
  }

  try {
    process.loadEnvFile(envFile);
  } catch {
    // Ignore: createExaClient() below reports the missing key.
  }
}

export function createExaClient() {
  loadLocalEnv();

  const apiKey = process.env.EXA_API_KEY?.trim();

  if (!apiKey) {
    throw new Error(
      'EXA_API_KEY is not set. Add it to .env (see .env.example) or export it before running this script.'
    );
  }

  return new Exa(apiKey);
}

/**
 * Search the web and return Exa's results, each with relevance highlights.
 *
 * @returns {Promise<Array<{title?: string, url: string, publishedDate?: string, highlights?: string[]}>>}
 */
export async function searchWeb(query, { numResults = 5, type = 'auto', client } = {}) {
  const trimmed = query?.trim();

  if (!trimmed) {
    throw new Error('A search query is required.');
  }

  const exa = client ?? createExaClient();

  const response = await exa.search(trimmed, {
    type,
    numResults,
    contents: { highlights: true },
  });

  return response.results ?? [];
}

function printResults(query, results) {
  if (results.length === 0) {
    console.log(`No results for "${query}".`);
    return;
  }

  console.log(`${results.length} result(s) for "${query}"\n`);

  results.forEach((result, index) => {
    console.log(`${index + 1}. ${result.title ?? '(untitled)'}`);
    console.log(`   ${result.url}`);

    if (result.publishedDate) {
      console.log(`   published ${result.publishedDate}`);
    }

    for (const highlight of result.highlights ?? []) {
      console.log(`   > ${highlight.replace(/\s+/g, ' ').trim()}`);
    }

    console.log('');
  });
}

async function main() {
  const argv = process.argv.slice(2);
  const flags = Object.fromEntries(
    argv
      .filter((argument) => argument.startsWith('--'))
      .map((argument) => {
        const [key, value = 'true'] = argument.replace(/^--/, '').split('=');
        return [key, value];
      })
  );
  const query = argv.filter((argument) => !argument.startsWith('--')).join(' ');

  if (!query) {
    console.error('Usage: node tools/exa-search.mjs "your query" [--num=5] [--json]');
    process.exitCode = 1;
    return;
  }

  const results = await searchWeb(query, {
    numResults: Number(flags.num ?? 5),
    type: flags.type ?? 'auto',
  });

  if (flags.json === 'true') {
    console.log(JSON.stringify(results, null, 2));
    return;
  }

  printResults(query, results);
}

if (process.argv[1] && join(process.argv[1]) === join(import.meta.filename)) {
  try {
    await main();
  } catch (error) {
    console.error(`Exa search failed: ${error.message}`);
    process.exitCode = 1;
  }
}

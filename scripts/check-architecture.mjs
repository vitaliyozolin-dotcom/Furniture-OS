import { readdir, readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
const errors = [];
async function visit(directory) {
  for (const entry of await readdir(directory, { withFileTypes: true })) {
    const path = resolve(directory, entry.name);
    if (entry.isDirectory()) { await visit(path); continue; }
    if (!/\.(ts|tsx)$/.test(path) || /\.(test|fixture)\./.test(path)) continue;
    const source = await readFile(path, 'utf8');
    if (!path.includes('/test/') && !path.includes('/compatibility/prototype-v0/') && !path.endsWith('/migrationCalculationGateway.ts') && /calculateCabinet|localPrototypeCalculationGateway/.test(source)) errors.push(`${path}: production imports compatibility calculation`);
    if (/\/(entities|shared)\//.test(path) && /from ['"][^'"]*(features|pages|app)\//.test(source)) errors.push(`${path}: UI layer depends outward`);
  }
}
await visit('apps/web/src');
if (errors.length) { console.error(errors.join('\n')); process.exitCode = 1; }
else console.log('Frontend architecture checks passed.');
// Cross-context imports must target an explicit public application contract.
async function backend(directory) {
  for (const entry of await readdir(directory, { withFileTypes: true })) {
    const path = resolve(directory, entry.name);
    if (entry.isDirectory()) { await backend(path); continue; }
    if (!path.endsWith('.php')) continue;
    const source = await readFile(path, 'utf8');
    const context = source.match(/namespace App\\([^\\;]+)\\/u)?.[1];
    for (const match of source.matchAll(/\bApp\\([^\\]+)\\([^;\s'"]+)/gu)) {
      if (context && context !== match[1] && !match[2].startsWith('Application\\Contract\\')) errors.push(`${path}: cross-context import bypasses public contract`);
    }
    if (path.includes('/Domain/') && /\b(file_get_contents|file_put_contents|fopen|mkdir|curl_init|shell_exec)\s*\(/u.test(source)) errors.push(`${path}: domain performs I/O`);
  }
}
await backend('apps/api/src');
if (errors.length) { console.error(errors.join('\n')); process.exitCode = 1; }
else console.log('Backend context and domain I/O checks passed.');

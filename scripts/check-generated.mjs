import { mkdtemp, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { execFileSync } from 'node:child_process';
const directory = await mkdtemp(join(tmpdir(), 'furniture-openapi-'));
try {
  const generated = join(directory, 'schema.ts');
  execFileSync('node_modules/.bin/openapi-typescript', ['contracts/openapi.yaml', '-o', generated], { stdio: 'inherit' });
  if (await readFile(generated, 'utf8') !== await readFile('apps/web/src/shared/api/generated/schema.ts', 'utf8')) throw new Error('Generated client is stale. Run npm run api:generate.');
} finally { await rm(directory, { recursive: true, force: true }); }

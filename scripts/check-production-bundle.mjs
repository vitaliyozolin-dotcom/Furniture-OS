import { readdir, readFile } from 'node:fs/promises';
const directory = 'apps/web/dist/assets';
for (const name of await readdir(directory)) {
  if (!name.endsWith('.js')) continue;
  const source = await readFile(`${directory}/${name}`, 'utf8');
  if (source.includes('Глубины недостаточно для стандартной продольной штанги.')) throw new Error('Compatibility engine leaked into the default production bundle.');
}
console.log('Default production bundle contains no compatibility engine.');

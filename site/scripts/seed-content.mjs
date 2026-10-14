#!/usr/bin/env node
/**
 * Copia i contenuti di esempio (*.example.json) nei file reali (*.json)
 * per lo sviluppo locale.
 *
 * I file reali sono gitignorati: in produzione li scrive la GitHub Action
 * leggendoli da admin/api/export.php. Il loader usa comunque l'example come
 * fallback, quindi questo script serve solo se vuoi modificare i contenuti
 * in locale senza toccare gli esempi versionati.
 *
 *   node scripts/seed-content.mjs           salta i file che esistono già
 *   node scripts/seed-content.mjs --force   sovrascrive
 */

import { readdir, copyFile, access } from 'node:fs/promises';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const CONTENT_DIR = join(dirname(fileURLToPath(import.meta.url)), '..', 'src', 'content');
const force = process.argv.includes('--force');

const exists = async (p) => access(p).then(() => true, () => false);

const examples = (await readdir(CONTENT_DIR)).filter((f) => f.endsWith('.example.json'));

if (examples.length === 0) {
  console.error(`Nessun *.example.json in ${CONTENT_DIR}`);
  process.exit(1);
}

let copied = 0;
let skipped = 0;

for (const file of examples) {
  const target = file.replace('.example.json', '.json');
  const targetPath = join(CONTENT_DIR, target);

  if (!force && (await exists(targetPath))) {
    skipped++;
    continue;
  }

  await copyFile(join(CONTENT_DIR, file), targetPath);
  copied++;
  console.log(`  ${target}`);
}

console.log(
  `\nContenuti di esempio: ${copied} copiati, ${skipped} già presenti` +
    (skipped && !force ? ' (usa --force per sovrascrivere)' : ''),
);

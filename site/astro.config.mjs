import { defineConfig } from 'astro/config';
import react from '@astrojs/react';
import tailwindcss from '@tailwindcss/vite';

import { SITE_URL, LANGS, DEFAULT_LANG } from './src/config/site.ts';

// Hosting condiviso: solo PHP + MySQL, niente Node. Quindi output statico
// e nessun adapter. Il build produce site/dist/, caricato via SFTP.
export default defineConfig({
  site: SITE_URL,
  output: 'static',
  // Coerente con build.format 'directory' e con i percorsi di i18n/routes.ts:
  // l'URL canonico è quello che Apache serve, senza redirect.
  trailingSlash: 'always',

  build: {
    // Tutto il CSS inline: i fogli sono piccoli (pochi KB a pagina) e tre
    // <link> separati bloccavano il primo paint per ~300 ms su mobile.
    inlineStylesheets: 'always',
    format: 'directory',
  },

  // Le immagini stanno in /uploads/ sull'host e non passano mai dal build:
  // le varianti WebP/AVIF le genera il back office PHP.
  image: {
    service: { entrypoint: 'astro/assets/services/noop' },
  },

  i18n: {
    locales: [...LANGS],
    defaultLocale: DEFAULT_LANG,
    routing: {
      prefixDefaultLocale: false, // IT in radice, EN sotto /en/
    },
  },

  // La sitemap è un endpoint nostro (src/pages/sitemap.xml.ts) invece di
  // @astrojs/sitemap: l'integrazione accoppia le lingue solo a slug identico
  // e qui gli slug sono tradotti (/servizi ↔ /en/services).
  integrations: [react()],

  vite: {
    plugins: [tailwindcss()],
  },
});

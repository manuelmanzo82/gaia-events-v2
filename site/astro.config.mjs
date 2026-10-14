import { defineConfig } from 'astro/config';
import react from '@astrojs/react';
import sitemap from '@astrojs/sitemap';
import tailwindcss from '@tailwindcss/vite';

import { SITE_URL, LANGS, DEFAULT_LANG } from './src/config/site.ts';

// Hosting condiviso: solo PHP + MySQL, niente Node. Quindi output statico
// e nessun adapter. Il build produce site/dist/, caricato via SFTP.
export default defineConfig({
  site: SITE_URL,
  output: 'static',
  trailingSlash: 'ignore',

  build: {
    inlineStylesheets: 'auto',
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

  integrations: [
    react(),
    sitemap({
      i18n: {
        defaultLocale: DEFAULT_LANG,
        locales: { it: 'it-IT', en: 'en' },
      },
      // Le pagine EN esistono solo se la traduzione c'è: Astro genera
      // soltanto quelle, quindi la sitemap è già coerente.
    }),
  ],

  vite: {
    plugins: [tailwindcss()],
  },
});

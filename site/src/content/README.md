# Contenuti

Astro legge i contenuti da questi JSON, **mai dal database**.

- `*.example.json` — dati di esempio, versionati, usati come fallback dal
  loader (`src/lib/content.ts`) così il build locale funziona sempre.
- `*.json` — file reali, **gitignorati**: li scrive la GitHub Action leggendo
  `admin/api/export.php` prima del build. In locale puoi crearli con
  `npm run content:seed`.

Ogni campo di testo è bilingue: `{ "it": "…", "en": "…" }`.
**Se la versione EN è vuota, la pagina EN non viene generata** e non compare
né in sitemap né negli hreflang. I tipi sono in `src/lib/types.ts`.

Le immagini sono riferimenti a file in `/uploads/` sull'host: il back office
genera le varianti WebP (e AVIF se il server lo supporta) e scrive qui
`width`, `height` e i formati disponibili.

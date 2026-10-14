# Contenuti

Astro legge i contenuti da questi JSON, **mai dal database**.

- `*.example.json` — versionati, usati come fallback dal loader
  (`src/lib/content.ts`) così il build locale funziona sempre.
  `posts`, `faq` e le policy dentro `pages` contengono i **contenuti reali**
  importati dalla v1, in italiano; il resto è ancora segnaposto.
  La fonte di verità resta il database: l'import sta in
  `admin/sql/seed-contenuti-v1.sql` e questi file ne sono la copia.
- `*.json` — file reali, **gitignorati**: li scrive la GitHub Action leggendo
  `admin/api/export.php` prima del build. In locale puoi crearli con
  `npm run content:seed`.

Ogni campo di testo è bilingue: `{ "it": "…", "en": "…" }`.
**Se la versione EN è vuota, la pagina EN non viene generata** e non compare
né in sitemap né negli hreflang. I tipi sono in `src/lib/types.ts`.

Le immagini sono riferimenti a file in `/uploads/` sull'host: il back office
genera le varianti WebP (e AVIF se il server lo supporta) e scrive qui
`width`, `height` e i formati disponibili.

> **In locale le immagini di esempio danno 404**, ed è normale: `/uploads/`
> esiste solo sul server e i file di `esempi/` non sono mai stati caricati.
> I riferimenti restano negli esempi apposta, per poter verificare che
> `lib/images.ts` costruisca `srcset` e `<picture>` come deve.

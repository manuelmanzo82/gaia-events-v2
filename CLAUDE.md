# Gaia Events v2 — contesto per Claude Code

## Il progetto

Rifacimento da zero del sito di **Gaia Crognale**, wedding & event planner a **Roma**
(non in Toscana: la v1 aveva questo errore nella meta description di default).

Il sito v1 (`manuelmanzo82/gaia-events-astro`, Astro + Sanity + Vercel) resta **solo
come riferimento**: niente copia-incolla acritico. La recon completa è in
[docs/RECON.md](docs/RECON.md) — leggila prima di lavorare sui contenuti.

**Dati ufficiali** (fonte unica: `site/src/config/site.ts` e `admin/config.php`):

| | |
|---|---|
| Dominio | `https://gaiaevents.it` |
| Staging | `https://gaia.incognito.work` (password + noindex) |
| Email | `gaiacrognale@gmail.com` |
| WhatsApp | `+39 327 385 5362` |
| Instagram | `@gaiacrognale_events` |

## Decisioni prese (non rimetterle in discussione senza chiedere)

**Stack**
- Astro 5 con `output: 'static'`. **Nessun adapter** (niente Vercel): l'hosting di
  produzione è condiviso, solo PHP + MySQL, **niente Node**.
- React 19 **solo come islands**, dove serve interattività. Tutto il resto è `.astro`.
- Tailwind per lo stile. Font **self-hosted** in `site/public/fonts/`.

**Contenuti**
- Back office su misura in `admin/` (PHP 8.2+, MySQL), sostituisce Sanity.
  Gaia gestisce: impostazioni e testi di ogni sezione, eventi/matrimoni con gallery,
  servizi, recensioni, blog, FAQ, richieste di preventivo.
- Astro legge **file JSON** in `site/src/content/`, mai il database direttamente.
- In repo stanno solo i `*.example.json`. I file reali (`settings.json`, …) sono
  gitignorati: li scrive la Action durante il build. Il loader usa l'example come
  fallback, così il build locale funziona sempre.

**Pubblicazione**
1. Gaia preme **Pubblica** nel back office.
2. `admin/` chiama la GitHub API con un `repository_dispatch` (evento `publish`).
3. La Action fa `GET admin/api/export.php` con un bearer token, riceve i JSON e li
   scrive in `site/src/content/`.
4. Build di Astro, poi upload SFTP di `site/dist/`.
5. Il deploy **non cancella e non sovrascrive** `admin/` e `uploads/`.

Le credenziali stanno **solo** nei GitHub Secrets e in `admin/config.php` sul server.
Mai nel codice, mai nei commit.

**Immagini**
- Upload dal back office con crop; varianti generate da `ImageService`:
  **WebP sempre**, **AVIF solo se il server lo supporta** (`function_exists('imageavif')`).
  Il `<picture>` include l'AVIF soltanto se il file esiste.
- Le immagini **non passano mai dal build**: restano in `uploads/` sull'host e il sito
  statico le linka come `/uploads/...`. Ogni `<img>` ha sempre `width` e `height`
  (la v1 aveva 14 immagini senza dimensioni → CLS).

**Bilingue IT/EN su tutto il sito**
- `/` italiano, `/en/` inglese. **Slug tradotti**: `/servizi` ↔ `/en/services`.
  La mappa sta in `site/src/i18n/routes.ts` — unica fonte, usala sempre.
- Ogni campo di testo ha versione IT ed EN. **Se l'EN è vuoto, la pagina EN non viene
  generata** e non compare né in sitemap né negli hreflang.
- `hreflang` su ogni pagina (it, en, x-default) + sitemap bilingue.

**SEO / AEO**
- HTML statico pulito, JSON-LD: LocalBusiness, Service, Event, Article, FAQPage,
  BreadcrumbList. Helper in `site/src/lib/jsonld.ts`.
- `llms.txt` e `llms-full.txt` **generati dai contenuti**, non scritti a mano.
- OG image per pagina. Un solo `SITE_URL` in config: **mai URL scritti a mano**
  (nella v1 metà del sito puntava a `vercel.app` e metà a `gaiaevents.it`).

**Form**
- Preventivatore: **un unico componente bilingue**, senza prezzi automatici.
- Le richieste vanno al nostro PHP (`admin/api/quote.php`), non più a Web3Forms,
  e finiscono nel back office.

**Instagram**: feed automatico via API ufficiale, più avanti. Le immagini verranno
scaricate in `uploads/`, quindi **non serve Instagram nel CSP**.

## Design token

| Token | Hex | Uso |
|---|---|---|
| avorio | `#FAF8F5` | fondo |
| beige | `#E8E2D9` | fondi alternati, bordi |
| salvia | `#8B9A7D` | accento naturale |
| bordeaux | `#722F37` | accento forte, CTA |
| oro | `#C9A962` | dettagli, filetti |
| antracite | `#2D2D2D` | testo |

Font: **Cormorant Garamond** (display) + **Montserrat** (testo), self-hosted.

> Home, gallery e preventivatore sono **segnaposto**: il design si fa dopo, con le
> skill di design. Non abbellire le pagine segnaposto di iniziativa.

## Struttura

```
site/                   Astro 5
  src/config/site.ts    SITE_URL, contatti, lingue — fonte unica
  src/i18n/             routes.ts (slug IT↔EN) · ui.ts (stringhe) · index.ts (helper)
  src/lib/              content.ts · seo.ts · jsonld.ts · images.ts
  src/content/          *.example.json in repo, *.json scritti dalla Action
  src/layouts/          BaseLayout.astro
  src/components/       Header · Footer · LangSwitcher · Seo · JsonLd · PlaceholderSection
  src/pages/            pagine IT in radice, pagine EN in en/
  public/.htaccess      header di sicurezza, cache, compressione, noindex staging
admin/                  back office PHP 8.2
  index.php             front controller
  src/                  Db · Auth · Csrf · Router · View · Validator · ImageService
  src/Controllers/      un controller per sezione
  views/                form minimi, nessun design
  api/                  export.php (token) · quote.php · contact.php
  bin/export-content.php  export da CLI
  sql/schema.sql        tabelle con colonne *_it / *_en
uploads/                solo sul server, mai in repo
```

## Comandi

```bash
cd site
npm install
npm run content:seed     # copia i *.example.json in *.json per lo sviluppo locale
npm run dev              # dev server su :4321
npm run build            # build statico in site/dist/
npm run preview          # anteprima del build

# export dei contenuti dal database (sul server)
php admin/bin/export-content.php --out=../site/src/content
```

## Layout sull'hosting

```
public_html/          sito statico (contenuto di site/dist/)
public_html/admin/    back office PHP — aggiornato a mano, mai dal deploy del sito
public_html/uploads/  immagini — mai toccate dal deploy
```

## Regole di lavoro

- **Nessun segreto nei commit, mai.** La repo è pubblica.
- Commit piccoli e descrittivi, push su `main`.
- Se qualcosa non è deciso, **chiedi invece di inventare**.
- Mai scrivere un URL a mano: passa da `SITE_URL` e dagli helper di `i18n/routes.ts`.
- Ogni campo nuovo nel database nasce **già bilingue** (`campo_it`, `campo_en`).
- Niente adapter, niente runtime Node in produzione, niente dipendenze dal cloud.

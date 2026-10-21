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
| Facebook | `facebook.com/GaiaCrognaleEventi` |
| Indirizzo | `Viale Vaticano 53 - 00165 Roma` |

L'indirizzo è confermato da Gaia (14/10/2026) e compare nella pagina contatti e
nel JSON-LD `LocalBusiness`. È però un «per ora»: se l'attività si sposta va
cambiato in `site/src/config/site.ts` e nell'impostazione `contact.address`.

## Decisioni prese (non rimetterle in discussione senza chiedere)

**Stack**
- Astro 7 con `output: 'static'`. **Nessun adapter** (niente Vercel): l'hosting di
  produzione è condiviso, solo PHP + MySQL, **niente Node**.
- React 19 **solo come islands**, dove serve interattività. Tutto il resto è `.astro`.
- **Node ≥ 22.12** (lo impone Astro 7). In CI il workflow usa `node-version: 22`.
- **Tailwind 4** via `@tailwindcss/vite` (`@astrojs/tailwind` è deprecato).
  Configurazione CSS-first: i token stanno in `@theme` dentro
  `src/styles/global.css`, **non** in un `tailwind.config`.
- **TypeScript resta su 5.x**: `@astrojs/check` accetta come peer solo
  `^5 || ^6`, e TypeScript 7 è già uscito. Non aggiornarlo finché
  `@astrojs/check` non lo supporta, altrimenti `npm install` fallisce.
- Font **self-hosted** in `site/public/fonts/`. I `.woff2` non sono ancora in
  repo e le `@font-face` in `src/styles/fonts.css` sono commentate: si
  attivano in fase di design.
- `trailingSlash: 'always'` con `build.format: 'directory'`. Tutti i percorsi
  passano da `path()` di `i18n/routes.ts`, che aggiunge lo slash finale: così
  l'URL canonico è esattamente quello che Apache serve, senza il 301 di
  `DirectorySlash`.
- La **sitemap è un endpoint nostro** (`src/pages/sitemap.xml.ts`), non
  `@astrojs/sitemap`: l'integrazione accoppia le lingue solo quando lo slug è
  identico, e qui gli slug sono tradotti.

**Contenuti**
- Back office su misura in `admin/` (PHP 8.2+, MySQL), sostituisce Sanity.
  Gaia gestisce: impostazioni e testi di ogni sezione, eventi/matrimoni con gallery,
  servizi, recensioni, blog, FAQ, richieste di preventivo — e dalla fase 2 anche
  **Luoghi, Location, Highlights, Partner, Storie e Hero** (vedi sotto).
- Astro legge **file JSON** in `site/src/content/`, mai il database direttamente.
- In repo stanno solo i `*.example.json`. I file reali (`settings.json`, …) sono
  gitignorati: li scrive la Action durante il build. Il loader usa l'example come
  fallback, così il build locale funziona sempre.

**Pubblicazione**

Un push su `main` o su un branch `design/**` fa partire **da solo** il deploy
sullo **staging** (i push che toccano solo `docs/**` o file `.md` sono
ignorati). La **produzione non parte mai da un push**: solo da
`workflow_dispatch` (pulsante Pubblica o avvio manuale) con target
`production`. La concurrency è per ambiente con `cancel-in-progress`: un
nuovo push annulla il deploy staging ancora in corso.

1. Gaia preme **Pubblica** nel back office.
2. `admin/` chiama la GitHub API con un **`workflow_dispatch`** su `deploy.yml`,
   passando `target` con l'ambiente da aggiornare.
3. La Action fa `GET admin/api/export.php` con un bearer token, riceve i JSON e li
   scrive in `site/src/content/`.
4. Build di Astro, poi upload SFTP di `site/dist/`.
5. Il deploy **non cancella e non sovrascrive** `admin/` e `uploads/`.

Si usa `workflow_dispatch` e non `repository_dispatch` per via dei permessi: un
token fine-grained per il primo richiede solo **`Actions: Read and write`**,
mentre il secondo pretende `Contents: Read and write`, cioè il permesso di
scrivere codice nella repo. Quel token vive su un hosting condiviso, quindi meno
può fare meglio è.

Il token non si incolla a mano in `config.php`: `admin/bin/imposta-token-github.php`
lo legge senza mostrarlo, verifica che apra davvero il workflow — con un avvio a
vuoto, l'unico modo per provare il permesso di scrittura — e solo allora lo salva.

Le credenziali stanno **solo** nei GitHub Secrets e in `admin/config.php` sul server.
Mai nel codice, mai nei commit.

**Immagini**
- Upload dal back office con crop; varianti generate da `ImageService`:
  **WebP sempre**, **AVIF solo se il server lo supporta** (`function_exists('imageavif')`).
  Il `<picture>` include l'AVIF soltanto se il file esiste.
- Le immagini **non passano mai dal build**: restano in `uploads/` sull'host e il sito
  statico le linka come `/uploads/...`. Ogni `<img>` ha sempre `width` e `height`
  (la v1 aveva 14 immagini senza dimensioni → CLS).
- **Ogni immagine si porta dietro il proprio limite di ingrandimento.** All'upload
  `ImageService` calcola `max_css_width` = larghezza del file ÷ 2 (su retina
  servono due pixel di file per ogni pixel CSS) e da lì ricava `usage_tier`:

  | Uso | Larghezza CSS | File largo almeno |
  |---|---|---|
  | `hero` | da 1440 px | 2880 px |
  | `gallery_large` | da 1000 px | 2000 px |
  | `card` | da 480 px | 960 px |
  | `thumbnail` | sotto | — |

  Il limite viaggia nel JSON (`maxWidth`, `usage`), `Picture.astro` lo applica
  come `max-width` e lo espone in `data-max-width`. **Nessun componente, lightbox
  compresa, deve mostrare un'immagine più larga del suo limite.** Serve a usare
  davvero anche le foto piccole, ciascuna al suo posto, invece di scartarle: una
  foto da 1024 px sta benissimo in una card, non in un hero.
- `images.max_original` in `admin/config.php` limita il **lato lungo**: su una
  verticale 2:3 la larghezza risultante è due terzi di quel valore. È per questo
  che vale 4500 e non 3000, altrimenti nessuna foto verticale potrebbe fare da hero.

**Bilingue IT/EN su tutto il sito**
- `/` italiano, `/en/` inglese. **Slug tradotti**: `/servizi` ↔ `/en/services`.
  La mappa sta in `site/src/i18n/routes.ts` — unica fonte, usala sempre.
- Ogni campo di testo ha versione IT ed EN. **Se l'EN è vuoto, la pagina EN non viene
  generata** e non compare né in sitemap né negli hreflang.
- La stessa regola vale per gli **elenchi**: se nessun elemento è tradotto, la
  pagina indice EN non esiste. Senza nessun articolo inglese niente `/en/blog/`,
  e la pagina ricompare da sola appena arriva la prima traduzione.
- Chi decide tutto questo è **`site/src/lib/availability.ts`**, fonte unica usata
  da sitemap, hreflang, menu, piè di pagina e selettore di lingua. I campi che
  rendono pubblicabile un contenuto (`EVENT_FIELDS`, `POST_FIELDS`, …) stanno lì:
  elenco e pagina di dettaglio devono usare gli stessi, altrimenti un indice
  linka una scheda che il build non ha generato.
- Le pagine indice EN vivono in `site/src/pages/en/[section].astro`, una rotta
  dinamica: un file statico verrebbe generato comunque.
- Nessun link verso una pagina che in quella lingua non esiste: menu, piè di
  pagina e CTA sono filtrati con `existsIn()`.
- Il **selettore di lingua resta sempre visibile**. Se la pagina non esiste
  nell'altra lingua porta alla home di quella lingua, senza `rel="alternate"`
  né `hreflang`: non è una traduzione di quella pagina.
- `hreflang` su ogni pagina (it, en, x-default) + sitemap bilingue.

**SEO / AEO**
- HTML statico pulito, JSON-LD: LocalBusiness, Service, Event, Article, FAQPage,
  BreadcrumbList. Helper in `site/src/lib/jsonld.ts`.
- `llms.txt` e `llms-full.txt` **generati dai contenuti**, non scritti a mano.
- OG image per pagina. Un solo `SITE_URL` in config: **mai URL scritti a mano**
  (nella v1 metà del sito puntava a `vercel.app` e metà a `gaiaevents.it`).

**Fase 2 — modello dati (16/10/2026, fatto)**
- Nuove entità: **luoghi** (gerarchia regione→zona via `parent_id`, slug bilingui
  unici globalmente, galleria), **locations** (nome non tradotto, `luogo_id` con
  ON DELETE **RESTRICT**: il back office rifiuta la cancellazione di un luogo con
  zone o location collegate e dice quante sono), **highlights** (alt su `media`,
  sezioni con ordine PER sezione), **partners**, **stories** (slug bilingue,
  fotografo = riferimento a partner), **page_hero** + foto ordinate.
- **Tabella `events` congelata**: dentro solo dati di esempio; il DROP con la
  rimozione di /portfolio va fatto in fase 3, in un commit dedicato.
- Rotte nuove: `places` /luoghi/ ↔ /en/destinations/ (dettagli a due livelli con
  slug composto, vedi `slugLuogo()` in `site/src/lib/content.ts`), `stories`
  /storie/ ↔ /en/stories/, `partners` /partner/ ↔ /en/partners/. In
  `availability.ts`: `LUOGO_FIELDS`, `STORY_FIELDS`; i partner hanno la regola
  morbida (indice EN se c'è almeno un partner attivo).
- Export: sei file nuovi (`luoghi`, `location`, `highlights`, `partner`,
  `storie`, `hero`) con i relativi `*.example.json`. JSON-LD: Place + ItemList
  per i luoghi, **Article** (non Event) per le storie, Organization per i
  partner. Luoghi e location sono dentro `llms.txt`/`llms-full.txt` e in sitemap
  con gli alternate ricalcolati per lingua.
- **Video hero**: su IONOS **non c'è ffmpeg** → Gaia carica dal back office MP4 e
  WebM già pronti (max 8 MB l'uno, MIME verificato con finfo, salvati in
  `uploads/video/`) e il poster passa da ImageService; `video_url` è il campo per
  un futuro passaggio a Vimeo.

**Migrazioni del database**
- Ogni modifica allo schema = **un file in `admin/sql/migrations/`** (mai solo
  schema.sql) + le stesse definizioni in `schema.sql`.
- Procedura sullo staging (admin/ NON viene toccato dal deploy del sito):
  1. upload dei file di `admin/` via SFTP con la chiave dedicata, **solo dentro
     `/gaia-staging/admin/`**, mai fuori da `/gaia-staging`;
  2. `php8.3 -q admin/bin/dump-sicurezza.php` → dump in `admin/storage/`
     (bloccata dal web dal .htaccess);
  3. `php8.3 -q admin/bin/applica-migrazione.php` (registra in
     `schema_migrations`, rilanciarlo è sempre sicuro; `--lista` per lo stato);
  4. verifica, poi **cancellare il dump**;
  5. controllare con curl che `/admin/bin/`, `/admin/sql/` e `/admin/storage/`
     rispondano 403.
- Il `.gitignore` ignora `*.sql`: la riga `!admin/sql/migrations/*.sql` è
  quella che tiene le migrazioni in repo (la prima, di fase 2, era rimasta
  fuori fino al 16/10).

**Import delle foto dei fotografi** (16/10/2026, fatto sullo staging)
- Gli originali non entrano mai in repo. Si caricano via SFTP in
  `admin/storage/import/` (cartella non servita dal web, `[F]` di mod_rewrite)
  insieme a un `manifesto.json` con nome parlante, alt IT/EN, nota interna e
  ruoli (`hero`, `home`, `chi_sono` = ordine nella sezione) per ogni file.
- `php8.3 -q admin/bin/importa-foto.php` (`--prova` per vedere senza
  scrivere) passa ogni file da `ImageService::storeFile()`, la stessa strada
  dell'upload dal back office: varianti WebP/AVIF, `max_css_width`,
  `usage_tier`. **GD riscrive solo i pixel**, quindi EXIF, GPS, fotocamera e
  autore non arrivano mai in `uploads/`; lo script lo verifica sul primo
  JPEG generato e si ferma se trova ancora un blocco EXIF. Rilanciarlo è
  sicuro: le foto già in `media` vengono saltate, i collegamenti
  riallineati.
- Sullo staging la shell SSH ha un tetto di **memoria virtuale di 768 MB**
  (`ulimit -v`, hard, non alzabile) che non c'entra con `memory_limit`: un
  processo che macina 110 foto lo supera a metà strada e l'encoder lascia
  varianti AVIF/WebP vuote, mentre una foto da 33 MP non si apre nemmeno.
  Per questo `--solo=FILE` importa una foto per processo (il ciclo è nel
  commento in testa allo script), `--verifica` controlla che originale e
  varianti ci siano davvero e `--ripara` cancella righe incomplete e file
  orfani. `php-cgi` senza stdin va in segfault: sempre `< /dev/null`.
- `ImageService` conta un formato come riuscito solo se **tutte** le
  larghezze sono andate a buon fine, altrimenti cancella i resti: un
  `<source>` AVIF con una candidata mancante non ricade sul WebP, il
  browser mostra l'immagine rotta. Conseguenza sullo staging: le foto di
  tier `hero` importate da shell hanno **solo WebP** (l'AVIF a 2400 px non
  entra nei 768 MB), le altre WebP + AVIF. Dal back office, che gira sotto
  Apache senza quel tetto, l'AVIF si genera: ricaricare da lì le foto
  grandi quando arriveranno i file senza watermark.
- A import finito **cancellare `admin/storage/import/`**: gli originali con i
  metadati non devono restare sul server.
- `media.note_interne` (migrazione `2026-10-16-media-note-interne.sql`) è la
  nota di servizio visibile solo nel back office, mai esportata: ci stanno
  i flag «da sostituire», «da verificare», «per Chi sono».

**Form**
- Preventivatore: **un unico componente bilingue**, senza prezzi automatici.
- Le richieste vanno al nostro PHP (`admin/api/quote.php`), non più a Web3Forms,
  e finiscono nel back office.

**Instagram** (19/10/2026): oggi i post li carica Gaia a mano (back office →
Instagram, tabella `instagram_posts`, export `instagram.json` con gli ultimi
`instagram.numero_post` visibili); la sincronizzazione via API è pianificata
in «Stato e prossimi passi». Le immagini stanno comunque in `uploads/`,
quindi **non serve Instagram nel CSP**.

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

> La tabella sopra è la palette di partenza. Il **design system completo** della
> fase design (branch `design/home`) vive in `site/src/styles/global.css`
> (@theme: base chiara + sezioni scure `notte`/`bordeaux-notte`, scala
> tipografica fluida, motion token) ed è documentato dal vivo su
> **`/styleguide/`** (noindex, fuori sitemap). Font **variabili** self-hosted in
> `public/fonts/` (3 file, OFL). Il logo è un SVG **ricostruito per tracciatura**
> dal PNG ufficiale (`LogoGaia.astro`, provvisorio: manca il vettoriale
> sorgente). L'**intro a velo avorio** (`IntroVelo.astro`) costruisce il logo
> (ghirigoro disegnato a penna, lettere, firma, riga di prestigio) in ~5 s;
> richiede lo snippet nel `<head>` della pagina (oggi solo in styleguide, in
> BaseLayout quando andrà sulla home) dove vive il flag **`INTRO_SEMPRE`**:
> oggi `true` per il testing, **da rimettere a `false` prima del lancio**.

> Home, gallery e preventivatore sono **segnaposto**: il design si fa con le
> skill di design, per gradi. Non abbellire le pagine segnaposto di iniziativa.

## Struttura

```
site/                   Astro 7
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

## Contenuti importati e domande aperte

I contenuti reali sono già dentro, in italiano:

| Da dove | Cosa | Dove sta l'import |
|---|---|---|
| Codice della v1 | 8 articoli, 12 FAQ, privacy, cookie | `admin/sql/seed-contenuti-v1.sql` |
| Sanity | 4 servizi, contatti, biografia, citazione, percorso, valori, hero delle pagine | `admin/sql/seed-contenuti-sanity.sql` |

Da Sanity **non** è stato importato nient'altro, e non va recuperato dopo:

- **eventi e Instagram**: materiale di prova;
- **recensioni**: Gaia ha confermato che sono tutte demo. Quelle vere arrivano
  più avanti, con nome, evento, data e consenso scritto della coppia;
- **immagini**: Gaia ha confermato che sono tutte fake, comprese quelle che
  sembravano lavori veri. **Nessuna foto di Sanity va sul sito nuovo.** Le foto
  le fornisce Manuel, che ha già gli originali dei fotografi per la gallery.

Il dataset completo è salvato in `E:\CLAUDE CODE\gaia-backup-sanity` — fuori
dalla repo, da non committare mai. Serve solo come documentazione del vecchio
sito: non è una riserva di contenuti da cui pescare.

Le risposte di Gaia e le due domande ancora aperte stanno in
[docs/DA-CHIEDERE-A-GAIA.md](docs/DA-CHIEDERE-A-GAIA.md).

## Secrets e variabili su GitHub

Per ambiente (**Settings → Environments**: `production` e `staging`), così lo
staging non può toccare la produzione.

| Nome | Tipo | Cosa contiene |
|---|---|---|
| `SITE_URL` | variabile | `https://gaiaevents.it` o `https://gaia.incognito.work` |
| `EXPORT_URL` | secret | URL completo di `admin/api/export.php` |
| `EXPORT_TOKEN` | secret | lo stesso valore di `export_token` in `config.php` |
| `SFTP_HOST` | secret | host SFTP dell'hosting |
| `SFTP_PORT` | secret | opzionale, default 22 |
| `SFTP_USER` | secret | utente SFTP |
| `SFTP_KEY` | secret | chiave privata SSH dedicata alla Action, per intero |
| `SFTP_KNOWN_HOSTS` | secret | riga di `ssh-keyscan -p 22 host`; se manca, la chiave del server non viene verificata |
| `REMOTE_PATH` | secret | cartella remota del sito, es. `/public_html` |

Il deploy si autentica **a chiave, non a password**: sul server c'è una chiave
dedicata alla sola GitHub Action, separata da quelle usate a mano. Per revocarla
basta togliere da `~/.ssh/authorized_keys` la riga che finisce con
`github-actions-gaia-staging`.

Il token GitHub che il back office usa per il `repository_dispatch` sta solo in
`admin/config.php` sul server, sotto `github.token`.

## Staging — in piedi dal 14/10/2026

**https://gaia.incognito.work** su IONOS Webhosting Plus. Sito, back office e
database funzionanti; manca solo il deploy automatico.

| | |
|---|---|
| Sito | `/gaia-staging/` nel webspace |
| Back office | `/gaia-staging/admin/`, `config.php` a 640 |
| Upload | `/gaia-staging/uploads/` |
| Percorso assoluto | `/kunden/homepages/30/d593572430/htdocs/gaia-staging` |
| Database | `dbs16227921` su `db5021647935.hosting-data.io`, MariaDB 11.8.8 |
| Accesso | chiave SSH dedicata, nessuna password |

Il server è più generoso del previsto: PHP **8.4**, `memory_limit` **illimitato**,
upload fino a 64 MB, e GD con **WebP e AVIF**. Una foto da 32 MP passa senza
problemi, quindi niente Imagick e niente ridimensionamento in più passaggi.
`max_upload_bytes` è a 32 MB: sotto il limite del server, così l'errore lo diamo
noi con un messaggio chiaro invece di lasciarlo dare ad Apache.

**Il webspace ospita altri progetti** (`incassi/`, `brasili.cozzolino/`, `gaia/`
e altri): non toccare mai nulla fuori da `/gaia-staging`. Il `REMOTE_PATH` del
deploy punta lì apposta, così il mirror con `--delete` resta confinato.

### Protezione

**Niente più basic auth (dal 19/10/2026): lo staging è pubblico, solo noindex.**
Il back office resta protetto dal suo login, `admin/api/export.php` dal bearer
token.

Il noindex sta in `site/public/.htaccess`, dentro un `<If>` legato al dominio
`incognito.work` (`X-Robots-Tag: noindex, nofollow, noarchive` su tutto lo
staging, admin e uploads compresi): lo stesso file vale per produzione e
staging e sopravvive ai deploy, che lo riscrivono ogni volta. Il
`robots.txt` con `Disallow: /` e il meta robots li genera invece il build,
perché lo staging ha un `SITE_URL` diverso da quello di produzione
(`IS_STAGING` in `site/src/config/site.ts`).

Il vecchio file delle password, `/kunden/homepages/30/d593572430/htdocs/.htpasswd-gaia-staging`,
è rimasto dov'era, fuori dalla cartella pubblica: non lo usa più nessuno.

### File derivati: mai serviti

Un `config.php.bak-20261014` per Apache **non è più PHP**: lo serve come testo,
con dentro le credenziali del database. Vale per `.bak`, `.old`, `.orig`,
`.save`, `.sql`, `.log`, `.env` e per i doppi suffissi tipo `qualcosa.php.altro`.

Il blocco sta in **mod_rewrite** (`RewriteRule ... [F,L]`) e non in un
`<FilesMatch>`, in entrambi i `.htaccess`: le sezioni `<If>` sono valutate per
ultime e hanno la precedenza sull'autorizzazione dei `<Files>`, quindi un
`Require` in un `<If>` (com'era la basic auth dello staging) faceva
**scavalcare** il `Require all denied` più in basso. `[F]` di mod_rewrite no:
resta valido qualunque cosa venga aggiunta in futuro.

Per la stessa ragione nessuno script deve lasciare copie accanto all'originale:
`imposta-token-github.php` scrive la sua in `admin/storage/` e la cancella appena
ha finito.

### Chiavi SSH su Windows

OpenSSH rifiuta una chiave privata leggibile da altri utenti. Su Windows non
basta `chmod`: servono le ACL.

```powershell
icacls "percorso\della\chiave" /inheritance:r /grant:r "$env:USERNAME:(F)"
```

E `known_hosts` va salvato con **fine riga LF**: con CRLF il `
` finisce dentro
la chiave, non combacia mai e ssh continua a chiedere conferma dell'host.

### Cose che su IONOS vanno sapute

- `RewriteBase /admin/` è **indispensabile**: la `DOCUMENT_ROOT` vista da Apache
  (`/kunden/homepages/...`) non coincide con il percorso fisico
  (`/homepages/...`), e senza quella riga la riscrittura verso `index.php`
  finisce nel 404 del sito.
- Apache **non passa l'header `Authorization` a PHP** in CGI: lo ricopiamo in
  una variabile d'ambiente con una `RewriteRule` in `admin/.htaccess`, altrimenti
  `export.php` non vede mai il token.
- Da shell c'è solo **php-cgi**, non la SAPI `cli`: gli script in `admin/bin/`
  si usano con `php8.3 -q`, non definiscono `STDIN`/`STDERR` da soli (li
  dichiariamo noi) e non accettano `-r`.

### Password e chiavi

Tutto fuori dalla repo, in `E:\CLAUDE CODE\gaia-staging-config\`:
`config.php`, `CREDENZIALI-STAGING.txt` (token di export),
`PASSWORD-BASIC-AUTH.txt` (non più in uso), `PASSWORD-ADMIN-BACKOFFICE.txt` e la chiave SSH in
`ssh/`. **Niente di tutto questo va committato.**

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

## Stato e prossimi passi (16/10/2026)

**Dove siamo**: branch `design/home` (push = deploy automatico sullo staging).

Fatto:
- **Fase 1 — design system**: token, tipografia, componenti base, motion
  (Lenis + GSAP), `/styleguide/` sullo staging; logo SVG ricostruito
  (provvisorio); intro a velo **avorio** definitiva, logo nei colori originali,
  ~5 s, uscita in due tempi, `INTRO_SEMPRE = true` per il testing.
- **CI**: push su `main`/`design/**` → deploy automatico sullo **staging**
  (produzione solo da workflow_dispatch); concurrency per ambiente.
- **Fase 2 — modello dati**: tabelle, back office, export, loader, rotte,
  JSON-LD, llms, sitemap e pagine segnaposto per Luoghi/Location/Highlights/
  Partner/Storie/Hero. Migrazione applicata allo staging (procedura
  dump-sicurezza → applica-migrazione, sopra).

- **Foto vere sullo staging** (16/10): 110 originali dei fotografi importati
  in `uploads/foto/` con `admin/bin/importa-foto.php`, alt IT/EN su ogni
  foto, nessun metadato. Galleria hero di `page_hero` «home» con 4 foto
  (ordine: cerimonia sul prato vista mare, prato sul lago al crepuscolo,
  pittore sul lago, sposi sotto le luci), 16 Highlights in sezione «home»
  nell'ordine deciso, 4 Highlights in sezione «chi-sono» (la planner al
  lavoro). Tre Partner fotografi creati **non pubblicati**: David Vilella /
  Vdimage, New Light Studio, Eleonora Grasso. Crediti e conteggi in
  [docs/FOTO-CREDITI.md](docs/FOTO-CREDITI.md); per le 78 foto senza autore
  negli EXIF nessun credito finché Gaia non dice chi le ha scattate.
  Nessun matrimonio compare sul sito: niente raggruppamento per coppia.

- **Fase 3 — home completa IT/EN** (16/10, rivista lo stesso giorno):
  header **senza barra** (`Header.astro`: sempre visibile, colore dalla
  sezione sotto via `theme-observer`, ombra testuale morbida sopra le
  sezioni con `data-foto`; dal 19/10 logo **fisso** a 130/80 px; dal 20/10
  nessuna sfumatura dietro logo e menu, solo il cambio di colore — vedi
  giro 4; menu corto, il resto nel footer), torna-su in basso a
  destra (`TornaSu.astro`, usa `getLenis()`), snippet intro in `BaseLayout`
  (`INTRO_SEMPRE` ancora `true`), footer su notte (`Footer.astro`: logo,
  tagline, Contatti/Esplora/Seguici/Preventivo, riga legale con
  `settings.legal.piva`), e le sezioni in `site/src/components/home/`:
  hero (foto di `page_hero` in dissolvenza CSS, video desktop
  predisposto), **Gaia** (`GaiaIntro`: highlight «chi-sono» n.1 grande +
  titolo + manifesto come citazione + presentazione breve), highlights
  (dal 20/10 **menu visivo**: 5 schede grandi impilate, una per area —
  vedi giro 4; prima schede 16:9 col titolo dell'highlight; tutte le
  altre foto nella fascia orizzontale agganciata), luoghi (nascosta finché
  non ci sono luoghi), servizi (dal 20/10 quattro pannelli verticali, vedi
  giro 4),
  recensione (nascosta finché non ce n'è una vera),
  destination a tutta larghezza (highlight sezione «destination» + velo),
  CTA finale in due metà con foto a filo bordo (highlight sezione «cta»,
  ENUM esteso in migrazione; vedi giro 4). Effetti in `scripts/home-effetti.ts`: solo
  ≥64rem, pin/sticky al bordo superiore (il header lì è nascosto), spenti
  con reduced-motion; sotto, scroll naturale e carosello a scroll-snap.
  Tutti i testi dal back office, in sentence case e con il «voi»; in EN i
  servizi e Chi sono usano le traduzioni in bozza
  (docs/DA-CHIEDERE-A-GAIA.md).
  Lighthouse **sullo staging** (16/10, due misure per preset): mobile
  100/100/100, LCP 1,65–1,74 s, CLS 0; desktop 100/100/100, LCP 0,58 s,
  CLS 0,014. SEO lì vale 61 solo per il `noindex` dello staging: sul
  build locale, che usa l'URL di produzione, è 100. Misurato con
  `lighthouse@12` nello scratchpad e `CHROME_PATH` sul Chromium di
  Playwright (mai chrome.exe), header di basic auth via `--extra-headers`
  (non serve più: dal 19/10 lo staging è senza password).
- La dissolvenza dell'hero parte solo a intro finita (evento
  `gaia:intro-fine` da `IntroVelo`): altrimenti uscendo dal velo si vedeva
  già la seconda foto a metà crossfade, diversa da quella che si ritrova
  tornando in cima.
- **Cose imparate in fase 3**: Chrome **esclude dall'LCP un'immagine che
  riempie tutto il viewport** (la considera sfondo), quindi l'LCP della
  home è il titolo dell'hero e dipende dall'arrivo del font: il preload
  della foto hero non ha `fetchpriority=high`. Tutto il CSS è inline
  (`inlineStylesheets: 'always'`): tre fogli esterni costavano 300 ms di
  render-blocking su mobile. L'oro `#c9a962` su avorio sta a 2,1:1: per il
  testo piccolo sulle sezioni chiare c'è `--color-oro-testo` (#8a6d2e),
  l'oro resta per filetti e per il notte. SplitText va creato con
  `aria: 'none'` (altrimenti mette `aria-label` sui `<p>`, vietato).
  L'HTML della home pesa ~150 KB non compressi: il logo SVG tracciato sta
  inline due volte (header + velo, ~30 KB l'uno), da alleggerire quando
  arriverà il vettoriale ufficiale. Il cambio font spostava il layout
  (CLS 0,05): in `fonts.css` ci sono le facce **Fallback** a metriche
  compatibili (`size-adjust`, `ascent/descent/line-gap-override`,
  calcolate con fontTools: vanno ricalcolate se cambiano i font) e la
  scena del velo ha larghezza fissa. Il deploy da push può fallire per un
  errore TLS transitorio di IONOS sull'export: `gh run rerun` richiede
  diritti admin e un commit vuoto non fa partire il workflow (nessun file
  cambiato), quindi si rilancia con un `workflow_dispatch` sul ramo
  giusto — il «Pubblica» dello staging punta a `main`, non a
  `design/home`.

- **Giro 3 della home + Instagram** (19/10/2026):
  - **Menu** a 15 px (tracking 0.14em), Preventivo/English a 14 px, pannello
    mobile a 22 px; menu in riga da **80rem** (a quella misura servono
    ~1200 px), sotto il pannello. Il contrasto del menu si misura con lo
    sfondo reale (header nascosto, 75 percentile dei pixel dietro ogni
    voce, ombra esclusa): senza fondo era sotto 4.5:1 in 32 posizioni su 48
    (cieli dell'hero, schede, fascia agganciata), quindi l'header ha una
    **sfumatura morbida** nel colore del tema (avorio/notte, nessun bordo,
    svanisce in basso): ora 0 su 46.
  - **Tema dell'header** (`theme-observer.ts`): non più IntersectionObserver
    ma la sezione sotto la linea a metà header, misurata a ogni frame di
    scroll; con sezioni annidate vince la più interna.
  - **Logo** fisso a 130 px desktop / 80 px mobile, mai ridotto;
    `--altezza-header` 146 / 96 px.
  - **Hero**: eyebrow e filetto in avorio sulle sezioni scure; il velo ha una
    sfumatura raccolta attorno al testo: eyebrow ≥ 5.1:1, titolo ≥ 7.5:1,
    sottotitolo ≥ 9.9:1 su tutte e 4 le foto, a 1440 e 390.
  - **Schede impilate**: una sola `--larghezza-scheda` per titolo, schede e
    foto, centrata (0,0 px di asimmetria a 390/1280/1440/1920 in ogni fase
    dello scroll), formato 16:9–2:1, agganciate sotto l'header. La
    larghezza è il 90% della finestra **ma mai oltre il maxWidth della foto
    più piccola della pila** (oggi 1024 px): con file più grandi (i
    VD Image senza watermark, per esempio) le schede si allargano da sole.
  - **Gaia**: foto 4:5 al ~48% (colonne 13:12), testo centrato accanto;
    citazione corta in `home.manifesto` da confermare (DA-CHIEDERE n. 8).
  - **Servizi**: `ServiziLista.astro` al posto dello sticky split; foto da
    `services.media_id` (sullo staging: terrazza sulle colline, violinista,
    fiori di campo, castello di notte), mai ripetute da altre sezioni.
  - **Instagram**: `InstagramHome.astro` prima della CTA finale (fascia
    sfalsata in loop su desktop, scroll-snap su mobile, griglia con
    movimento ridotto). Sullo staging 8 post di prova con foto della
    libreria mai usate in home, `note_interne` «esempio Instagram, da
    sostituire», senza permalink (portano al profilo).
  - **Attenzione — `schema.sql` indietro**: le migrazioni del 16-17/10
    (`media.note_interne`, `highlights.title_it/en`, sezione «cta»
    nell'ENUM, `home.manifesto`, `legal.piva`) non sono mai state riportate
    in `schema.sql`; `instagram_posts` sì. Da allineare prima di creare il
    database di produzione da `schema.sql`.
- **Giro 4 della home, parte A desktop** (20/10/2026, rif.
  sarahhaywood.com; parte B mobile da proporre e approvare prima):
  - **Header**: via la sfumatura del giro 3 (scelta di Manuel: «solo cambio
    colore»), quindi su qualche foto il contrasto del menu può tornare
    sotto 4.5:1; resta l'ombra testuale sopra le sezioni con `data-foto`.
    Il torna-su segue già il tema del header.
  - **CTA dell'hero**: «Contattami» / «Get in touch» (`page_hero` sullo
    staging + `hero.example.json`), al preventivatore.
  - **Menu visivo** (`MenuVisivo.astro`, ex `HighlightsImpilati`): 5 schede
    da ~90vw × ~75svh, raggio 24 px, impilate e agganciate sotto l'header,
    una per area — Servizi, Luoghi, Destination wedding, Storie, Preventivo
    (filtro `existsIn`, testi in `ui.ts` `menu.*`) — con la i-esima foto
    orizzontale della sezione «home» nell'ordine del back office
    (`SCHEDE_IMPILATE = 5` in `lib/home.ts`). **Eccezione voluta alla regola
    del maxWidth**: qui le foto superano il proprio limite (90vw), unico
    posto del sito; `LIMITE_SCHEDA` non esiste più.
  - **Fascia orizzontale**: foto tutte alla stessa altezza
    (`min(65svh, ALTEZZA_FASCIA = 702px)`), larghe secondo il formato,
    niente didascalie; una foto che a quell'altezza supererebbe il suo
    maxWidth è esclusa in fase di build. Sotto i 64rem carosello 82vw.
  - **Servizi** (`ServiziPannelli.astro`, ex `ServiziLista`): quattro
    pannelli affiancati alti 80svh, foto da `services.media_id`, al
    passaggio/focus il pannello si allarga (flex-grow) e mostra riga e
    «Scopri»; click a `/servizi/#slug`.
  - **Instagram** come profilo: intestazione centrata (icona, @handle come
    h2, «Segui»), griglia 1:1 4×2 (3×2 sotto i 64rem), gap 3 px, hover con
    velo e icona del tipo (`instagram_posts.tipo`). Via la fascia in loop.
  - **Discendenti tagliate**: `motion.ts` non usa più `mask: 'lines'` di
    SplitText (overflow hidden riga per riga): le righe salgono del 60% e
    sfumano. Mai overflow hidden sul testo.
  - **CTA finale**: due metà a tutta larghezza (min 90svh), foto a filo
    bordo a sinistra, a destra su beige titolo, riga, firma «Gaia» in
    Cormorant corsivo, bottone pieno e link WhatsApp/email da `BUSINESS`.
  - Nuove chiavi `ui.ts`: `cta.scopri`, `cta.scrivici`, `cta.firma`,
    `menu.*`, `instagram.segui`; tolta `instagram.cta`.
- **Giro 5 — mobile (parte B) e ritocchi Instagram** (20/10/2026):
  - **Regole trasversali** sotto i 64rem (`global.css`): `--spacing-section`
    72 px, `--spacing-section-sm` 56 px, corpo 17 px, eyebrow 12 px,
    margini laterali 20 px, tap ≥ 44 px. Un formato per sezione: hero
    100svh, Gaia 4:5, schede 4:5, fascia 4:5 (82vw, scroll-snap), servizi
    4:5 in colonna, Instagram 3 colonne, CTA foto 4:5 poi testo.
  - **Effetti leggeri solo sotto i 64rem** (`home-effetti.ts`,
    `[data-parallasse="da a"]`, yPercent in scrub, solo transform): hero
    (contenitore delle foto 0 → 14, lo zoom della dissolvenza resta) e
    destination (figura -5 → 5 che sborda del 6%). Menu visivo: pila
    **sticky in CSS** senza GSAP, un gradino di 8 px per scheda; il titolo
    della sezione ha sotto un margine pari a `--altezza-header`, così al
    primo frame dello snap è fuori schermo e il logo non lo copre.
    `data-reveal-gruppo` ora esiste davvero in `motion.ts` (ritardo 0,12 s
    per figlio). Tutto spento con prefers-reduced-motion.
  - **Menu mobile** (`Header.astro`, sotto gli 80rem): voci Cormorant 34 px
    alte 60 px con numero, Preventivo pieno a tutta larghezza, English,
    WhatsApp e Instagram in fondo; dissolvenza e cascata in CSS.
  - **Torna-su**: 40 px sotto i 64rem e **nascosto mentre il footer è in
    vista** (IntersectionObserver, classe `coperto`); nella CTA finale i
    link sono in colonna così non finisce mai sopra un bottone.
  - **Intro**: logo a 112 px sul velo mobile (`.velo-scena { --logo-h }`).
  - **Menu visivo, testi**: `sections.portfolio.title/text` sono ora
    «Scoprite di più» / «Servizi, luoghi, storie e preventivo» (IT) e
    «Explore» / «Services, places, stories and quote» (EN), staging e
    `settings.example.json`.
  - **Instagram**: griglia a tutta larghezza senza contenitore, gap 2 px,
    foto 4:5, 6 colonne da 64rem (12 post), 4 da 48rem, 3 sotto (primi 6);
    bottone «Seguimi su Instagram» con icona. `instagram.numero_post` = 12
    sullo staging, 4 post di prova in più (media 115, 114, 110, 89).
  - **Dati sullo staging senza migrazione**: gli script temporanei vanno
    in `admin/bin/`, si lanciano con `php8.3 -q ... < /dev/null` e si
    cancellano subito; `export-content.php --out=` è relativo a `bin/`.
    Prima un `dump-sicurezza.php`, cancellato a fine lavoro.
  - **Foto per sezione (parte 3)**: provino in
    `C:\Users\Utente\gaia-giro5\provino-foto.html` (fuori repo), in attesa
    dell'OK di Manuel prima di toccare `highlight_sections`, `services` e
    `page_hero_images`.
- **Giro 6 — foto per sezione, fascia, Instagram, contrasto** (20/10/2026):
  - **Foto della home** (sullo staging, da `highlight_sections`, `services`,
    `page_hero_images`): hero 60, 57, 44; menu visivo 87 Servizi, 38
    Luoghi, 108 Destination, 3 Storie, 61 Preventivo (prime 5 orizzontali
    della sezione «home»); fascia 14 foto (66, 39, 10, 100, 58, 59, 33,
    101, 88, 84, 113, 11, 82, 109: quattro orizzontali alternate); servizi
    37, 27 (Gaia al tavolo, watermark), 102, 48 (lago); destination 111;
    CTA 62; Gaia 26. Nessuna foto ripetuta in home. Miniature finali in
    `C:\Users\Utente\gaia-giro6\foto-finali.html` (fuori repo).
  - **Fascia**: angoli 16 px, orizzontali e verticali alla stessa altezza;
    ogni tessera dichiara `data-theme="dark" data-foto`.
  - **Instagram**: niente icona sopra l'handle (resta nel bottone), 56 px
    sopra e 136 sotto a desktop (`--spacing-section-sm` / `--spacing-section`),
    hover solo con `@media (hover: hover)`: zoom 1.05 lento, velo dal basso
    con icona del tipo e «Vedi su Instagram» (`instagram.vedi`), le altre
    foto a opacità 0,6; su touch il tap apre il post; reduced-motion: solo
    il velo. La griglia dichiara `data-theme="dark" data-foto`.
  - **Contrasto di logo, menu e torna-su** (`theme-observer.ts` riscritto):
    il tema si decide PER PUNTO, non per riga — `temaInPunto(x, y)` prende
    l'elemento `[data-theme]` più interno che contiene il punto. Il logo
    guarda sotto di sé, OGNI voce del menu (link, Preventivo, lingua,
    hamburger) sotto il proprio centro (`data-tema`/`data-foto` sulla voce),
    il torna-su il proprio centro. Regola: sopra qualsiasi foto avorio,
    inchiostro solo sui fondi chiari pieni; ogni blocco con foto dentro una
    sezione chiara dichiara `data-theme="dark" data-foto` (foto di Gaia,
    foto della CTA, tessere della fascia, griglia Instagram). Il pin
    orizzontale muove il binario senza eventi di scroll: `home-effetti.ts`
    chiama `aggiornaTema()` a ogni frame. Verifica: scansione a passi di
    60 px, giù e su, a 1440 e 390 (`tema-sweep.js` nello scratchpad): zero
    voci col colore sbagliato, salvo un frame d'angolo arrotondato.
- **Giro 7 — WhatsApp flottante, fascia mobile, Instagram 3×3** (20/10/2026,
  home desktop approvata da Manuel):
  - **WhatsApp flottante** (`WhatsAppFlottante.astro`, in `BaseLayout` con
    il torna-su): tondo 44 px in basso a destra, torna-su 12 px sopra
    (`bottom: base + 44 + 12`). Logica comune in `scripts/flottanti.ts`:
    compare dopo una schermata (cioè dopo l'hero), classe `coperto` mentre
    il footer è in vista, colore dal punto sotto il proprio centro
    (`temaInPunto`). Desktop con puntatore: pillola «Scrivimi su WhatsApp»
    (`grid-template-columns 42px 0fr → 1fr`). Link wa.me al numero di
    `BUSINESS.whatsapp` con `whatsapp.messaggio` precompilato (IT/EN).
  - **Fascia su mobile**: tessere 78vw (la successiva sporge ~50 px),
    spinta d'invito una volta per visita (`sessionStorage`
    `gaia-fascia-spinta`, 60 px e ritorno in 0,8 s, scroll-snap spento
    durante la spinta altrimenti riaggancia subito a 0, mai con
    reduced-motion), barra di avanzamento 2 px bordeaux e «Scorri →»
    (`fascia.scorri`) che sparisce al primo scroll dell'utente.
  - **Instagram su mobile**: 3 × 3 (primi 9 post).
  - **Ritocchi** (20/10, sera): il WhatsApp flottante è il solo fumetto
    con la cornetta a linea sottile (svg 44 px, stroke 0.6 su viewBox 24 ≈
    1 px), senza cerchio; la pillola col bordo compare all'hover/focus.
    Sopra la fascia un titolo piccolo allineato al bordo sinistro delle
    foto: `sections.moments.text` (eyebrow «Istanti») e
    `sections.moments.title` («Frammenti di giorni felici»), migrazione
    `2026-10-20-fascia-testi.sql` applicata sullo staging e righe anche
    in `seed.example.sql`. A desktop la sezione agganciata è ora lei a
    essere alta 100svh (griglia: titolo + binario centrato nel resto).
- **Giro 8 — WhatsApp, prestazioni, preventivatore** (20/10/2026):
  - **WhatsApp flottante**: glifo ufficiale in SVG quadrato con viewBox
    allargato (glifo 39 px in 44, 35 in 40 su mobile, centro allineato
    al torna-su), sempre visibile dal caricamento al footer (dopo l'intro
    se attiva); il torna-su compare dopo l'hero, 12 px sopra.
    `scripts/flottanti.ts` ha le opzioni dopoHero, viaSulFooter, dopoIntro,
    punto.
  - **LCP**: l'intro NON era la causa (misurato con e senza: identico).
    Logo definito una volta come `<symbol id="logo-gaia">` (LogoSimbolo
    all'inizio del body, tracciati in LogoTracciati, LogoGaia usa `<use>`;
    il velo tiene la copia animabile): HTML della home da 47 a 36 KB
    compressi. GSAP/Lenis/reveal con import dinamico dopo il load
    (`motion-leggero.ts`: avviaMotionDopoIlPaint → evento
    `gaia:motion-pronta`; riduciMotion e getLenis da qui, mai da motion.ts
    nei componenti, altrimenti GSAP torna nel bundle iniziale). Un solo
    font in preload (Cormorant). Home mobile: LCP 1,7 s, 100.
  - **Posta**: su IONOS `mail()` passa da un account SMTP del pannello e
    fallisce (535). Si usa `src/Mailer.php` (PHPMailer 7.1.1 copiato in
    `src/Lib/PHPMailer`, LGPL; la cartella si chiama Lib perché `vendor/`
    è nel .gitignore) con la chiave `smtp` di config.php. Sullo staging il
    blocco c'è (smtp.ionos.it, 587, noreply@incognito.work) con la
    password VUOTA, da scrivere a mano (riga ~115). Produzione:
    smtps.aruba.it 465 ssl, noreply@gaiaevents.it (la posta di
    gaiaevents.it è su Aruba: da IONOS lo SPF fallirebbe). Se l'invio
    fallisce la richiesta resta salvata con `mail_error`.
  - **Preventivatore**: isola React `components/preventivo/`
    (Preventivatore.tsx, testi.ts con IT/EN e la lettera,
    preventivatore.css), pagina `QuotePage.astro`, impostazioni del
    gruppo «preventivo» (`settings.quote.*`, migrazione
    `2026-10-20-preventivatore.sql`, anche la foto di ogni passaggio).
    Endpoint `admin/api/quote.php` → `Inquiry::receiveQuote` (trappola,
    8 s minimi, 3/ora per IP). Richieste nel back office con stati nuova ·
    contattata · chiusa. Copia alla coppia dietro `quote.sendCopy` (0).
    Fasce di budget per tipo di evento e promesse della pagina: da
    confermare con Gaia (DA-CHIEDERE-A-GAIA n. 9). Senza luoghi pubblicati
    il passaggio «Dove» mostra un campo libero.
  - Preventivo su staging: desktop 100, mobile 97 (IT ed EN), CLS 0.
- **Giro 10** (20/10/2026):
  - **Hero della home**: via gli sposi fronte contro fronte (#57) e la
    cerimonia sul prato col mare (#60), su richiesta di Manuel. Galleria:
    sposa sul prato al lago al crepuscolo (#48, prima, quindi LCP e OG),
    tendone sulle colline (#44), pittore sotto l'albero sul lago (#49);
    tutte tier hero, tutte con watermark VD Image da sostituire. Il
    pannello Destination wedding dei servizi passa dalla #48 alla terrazza
    sui tetti di Roma al tramonto (#53).
  - **Preventivatore**: su mobile fascia foto 160 px in cima a ogni
    passaggio (nell'HTML iniziale, lazy, nascosta da 64rem: su desktop non
    si scarica); servizi con inclusioni dal back office (Servizi →
    «Include», colonna `include_slugs`, export `includes`; il completo
    include coordinamento e design, da confermare: DA-CHIEDERE n. 10),
    Destination preselezionato con «Veniamo dall'estero», «Non lo so»
    esclusivo; riepilogo con i soli titoli dei servizi; Dove: `zoneMode` è
    solo la scelta ('' | consigliateci | location), città/zona sempre a
    parte (location → nome obbligatorio; nessuna scelta → città
    obbligatoria). Focus sul primo campo in errore in ogni passaggio.
- **Giro 10 — header a colore unico, menu mobile, bottoni** (21/10/2026):
  - **Colore del header**: via la misura dei pixel per elemento
    (`luminosita.ts` cancellato). `theme-observer.ts` decide UN tema dal
    data-theme della sezione che contiene la linea a metà header — conta
    solo un `[data-theme]` largo ≥ 60% dello schermo, così la foto di Gaia
    e quella della CTA (metà sezione) non cambiano il colore (il menu
    sull'altra metà chiara resterebbe illeggibile): lì il header è
    inchiostro e la foto ha una fascia alta CHIARA. Sezioni con foto →
    avorio + text-shadow leggero; fondi chiari → inchiostro. I flottanti
    seguono la stessa regola misurata sotto di sé (avorio sul footer, dove
    il torna-su resta visibile).
  - **Leggibilità sulle foto**: fascia scura in cima alla FOTO (schede,
    pannelli, destination, griglia Instagram, tessere; nell'hero il velo
    alto è più deciso). Limite strutturale: il header è fisso e la foto
    scorre sotto per tutta la sua altezza, la fascia protegge solo i primi
    ~240 px; oltre, il contrasto dipende dalla foto. Schede e pannelli hanno
    lo stesso margine del header (niente tetto a 80rem), così logo ed EN
    cadono sempre sulla foto. Verifica: `tema-g10.js` nello scratchpad
    (pixel veri, header nascosto, 1280/1440/1920).
  - **Header**: «EN»/«IT» discreto con nome intero in aria-label; icone
    Instagram e Facebook (18 px) nella riga desktop; nel pannello mobile
    niente numeri, logo nei colori originali (`--logo-accento` pesca a
    pannello aperto), Preventivo a cornice, EN 16 px, contatti WhatsApp /
    Instagram / Facebook in fondo, voci Cormorant allineate al logo.
  - **Bottoni a cornice**: `--btn-tinta` / `--btn-contro` in `global.css`;
    hover e focus-visible = fondo pieno nella tinta, testo nel contro; il
    tema scuro (`[data-theme='dark']`, `[data-header-theme='dark']`)
    inverte. Mai `currentColor` come tinta (all'hover si risolve nel testo
    già cambiato). Footer: meno aria sopra il logo, logo 110/80 px.
  - **Preventivatore**: titolo con focus programmatico ma senza contorno;
    ospiti come campo di testo numerico senza spinner, largo come gli altri.

Prossimi passi (fase 3, resta):
- **Instagram via API (non ancora implementato)**: Instagram API with
  Instagram Login (account professionale di Gaia, niente pagina Facebook
  necessaria); app Meta in **modalità sviluppo** con Gaia aggiunta come
  tester, così non serve la revisione dell'app; token a lunga durata
  (**60 giorni**) salvato nel back office e **rinnovato in automatico**
  prima della scadenza (refresh dell'endpoint `refresh_access_token`);
  una **GitHub Action notturna** chiama un endpoint del back office
  protetto da token (come `export.php`), che legge i post nuovi, scarica
  le immagini in `uploads/` passando da ImageService e li inserisce in
  `instagram_posts` con `source = 'api'` e `ig_media_id` (UNIQUE: niente
  doppioni); **rebuild del sito solo se sono arrivati post nuovi**
  (`workflow_dispatch` come il Pubblica); **email a Manuel/Gaia** se il
  token è scaduto o il rinnovo fallisce. I post caricati a mano restano e
  si possono sempre nascondere o riordinare.
- Allineare `schema.sql` alle migrazioni del 16-17/10 (vedi sopra).
- Tabella `instagram_items` dello schema iniziale, mai usata: DROP insieme
  a `events`.
- Ok di Manuel sulla home dello staging; poi Lighthouse anche sullo
  staging (senza più header di autenticazione).
- Rimettere `INTRO_SEMPRE = false` prima del lancio.
- Scrivere la password SMTP in config.php (staging: noreply@incognito.work;
  produzione: noreply@gaiaevents.it su Aruba) e provare una richiesta vera.
- Footer: è ancora quello segnaposto, da disegnare.
- Pagine interne (Chi sono, Servizi, Luoghi, Storie, Partner, preventivatore)
  sono ancora segnaposto.
- DROP di `events`/`event_images` + rimozione di /portfolio quando /storie la
  sostituisce (commit dedicato).
- Contenuti veri da Gaia (luoghi, location, storie, testi dei partner).
- **Prima del lancio, foto** (le note stanno in `media.note_interne`, sezione
  Immagini del back office):
  - **18 foto da sostituire con i file senza watermark**: sono quelle di
    David Vilella / Vdimage, con il logo VD IMAGE impresso in basso a destra.
    Sullo staging ci sono con il logo (il fotografo le ha fornite a Gaia);
    tre di esse sono nella galleria hero della home e tre tra gli
    Highlights home, tre in «chi-sono». Chiedere i file puliti e
    ricaricarli dal back office al posto di quelli attuali.
  - **4 foto da verificare**: menù e partecipazioni con nomi di invitati
    leggibili (`mise-en-place-menu-salvia`, `menu-piatto-dettaglio`,
    `menu-stemma-piatto`, `partecipazioni-parquet-scarpe`). Non sono negli
    highlights né nell'hero.
  - 53 foto arrivate come screenshot o anteprime a ~1000 px (tier card):
    chiedere gli originali ai fotografi, se servono più grandi.
- Logo: sostituire l'SVG ricostruito se arriva il vettoriale ufficiale.
- Merge di `design/home` su `main` a fine fase design.

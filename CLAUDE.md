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

Tutto il sito è dietro **basic auth**, dichiarata in `site/public/.htaccess`
dentro un `<If>` legato al dominio `incognito.work`: lo stesso file vale per
produzione e staging e sopravvive ai deploy, che lo riscrivono ogni volta.

L'unica eccezione è `admin/api/export.php`, che la GitHub Action deve poter
chiamare e che si difende da sé col bearer token. L'eccezione sta **nella stessa
condizione `<If>`**, non in un `<Files>` dentro `admin/api/`: le sezioni `<If>`
vengono valutate per ultime e hanno la precedenza, quindi un'eccezione
dichiarata più in basso verrebbe ignorata.

Il file delle password sta **fuori dalla cartella pubblica**, in
`/kunden/homepages/30/d593572430/htdocs/.htpasswd-gaia-staging`, e deve essere
**644**: Apache gira come `www-data` e con 640 risponde 500.

### File derivati: mai serviti

Un `config.php.bak-20261014` per Apache **non è più PHP**: lo serve come testo,
con dentro le credenziali del database. Vale per `.bak`, `.old`, `.orig`,
`.save`, `.sql`, `.log`, `.env` e per i doppi suffissi tipo `qualcosa.php.altro`.

Il blocco sta in **mod_rewrite** (`RewriteRule ... [F,L]`) e non in un
`<FilesMatch>`, in entrambi i `.htaccess`. Il motivo è lo stesso incontrato con
`export.php`: le sezioni `<If>` sono valutate per ultime e hanno la precedenza
sull'autorizzazione dei `<Files>`, quindi con la basic auth attiva un
`Require all denied` più in basso **viene scavalcato**. `[F]` di mod_rewrite no.

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
`PASSWORD-BASIC-AUTH.txt`, `PASSWORD-ADMIN-BACKOFFICE.txt` e la chiave SSH in
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

- **Fase 3 — home completa IT/EN** (16/10): header fisso con `LogoGaia`
  che cambia colore sulle sezioni scure, snippet intro in `BaseLayout`
  (`INTRO_SEMPRE` ancora `true`), e le sezioni in
  `site/src/components/home/`: hero (foto di `page_hero` in dissolvenza
  CSS, video desktop predisposto), manifesto (`settings.home.manifesto`),
  highlights (5 schede impilate + 11 in fascia orizzontale agganciata),
  luoghi (nascosta finché non ci sono luoghi), servizi in sticky split
  (foto del servizio, ripiego = highlights sezione «servizi»), chi è Gaia
  (highlight «chi-sono» n.1), recensione (nascosta finché non ce n'è una
  vera), destination, CTA finale. Effetti in `scripts/home-effetti.ts`:
  solo ≥64rem, spenti con reduced-motion; sotto, scroll naturale e
  carosello a scroll-snap. Tutti i testi dal back office; in EN i servizi
  e Chi sono usano le traduzioni in bozza (docs/DA-CHIEDERE-A-GAIA.md).
  Lighthouse sul build locale: mobile 99/96/100/100, desktop
  100/96/100/100, CLS 0, LCP mobile ~1,9 s.
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
  arriverà il vettoriale ufficiale.

Prossimi passi (fase 3, resta):
- Ok di Manuel sulla home dello staging; poi Lighthouse anche sullo
  staging (con l'header di basic auth).
- Rimettere `INTRO_SEMPRE = false` prima del lancio.
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

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
  servizi, recensioni, blog, FAQ, richieste di preventivo.
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

E `known_hosts` va salvato con **fine riga LF**: con CRLF il `` finisce dentro
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

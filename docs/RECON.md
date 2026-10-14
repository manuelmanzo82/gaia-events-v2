# Gaia Events — Recon del sito attuale (v1)

Repo: `manuelmanzo82/gaia-events-astro` (pubblica) · ultimo commit `58684f8` del 19/02/2026
Live: `gaia-events-astro.vercel.app` · dominio previsto: `gaiaevents.it`

## Stack attuale
- Astro 5 (`output: static`) + adapter Vercel, React 19 islands, Tailwind 3, `@astrojs/sitemap`
- Sanity (`yfcp9735`, dataset `production`), letto al build; studio nella cartella `studio/`
- Web3Forms per form contatti e preventivo; WhatsApp `+39 327 385 5362`
- Header di sicurezza in `vercel.json` (vanno riportati in `.htaccess` sul nuovo hosting)
- Circa 8.000 righe in tutto, di cui ~1.200 nei due preventivatori (IT/EN quasi duplicati)

## Pagine
| Pagina | Fonte contenuti |
|---|---|
| `/` home | Sanity (impostazioni, servizi, recensioni, eventi, Instagram) |
| `/chi-sono`, `/servizi`, `/contatti`, `/portfolio`, `/portfolio/[slug]` | Sanity |
| `/en/destination-wedding` | Sanity (unica pagina EN, con hreflang) |
| `/blog`, `/blog/[slug]` | **misto**: l'indice legge Sanity + file locale, ma le pagine articolo leggono solo `src/data/blog-articles.ts` (8 articoli) → un articolo creato in Sanity non ha la sua pagina |
| `/faq`, `/dicono-di-me`, `/preventivo`, `/en/wedding-estimate`, `/privacy`, `/cookie`, `404` | testo nel codice |

## Modello dati (da portare nel back office)
- **Impostazioni** (singleton enorme, ~400 righe di schema): dati azienda, social, hero home, testi di ogni sezione e di ogni pagina, pagina destination wedding completa (servizi, destinazioni, processo, FAQ, testimonial, CTA)
- **Evento**: macroCategoria (wedding / corporate / celebrations), titolo, slug, location, data, categoria, descrizione breve, copertina, hero, video (YouTube/Vimeo), descrizione rich text, galleria, in evidenza, ordine
- **Servizio**: titolo, slug, sottotitolo, descrizione, caratteristiche, icona, immagine, attivo, ordine
- **Recensione**: autore, evento, data, testo, valutazione, immagine, attivo, in evidenza
- **Articolo blog**: titolo, slug, meta descrizione, estratto, categoria, data, tempo lettura, copertina, contenuto rich text, attivo
  > **Correzione (14/10/2026).** Lo schema esisteva su Sanity, ma gli articoli
  > pubblicati stavano nel codice. Vale lo stesso per FAQ, privacy e cookie.
  > Vedi la sezione «Contenuti già importati» in fondo.
- **Instagram gallery**: lista manuale di immagini con tipo (foto/video), caption, link al post — l'account è `@gaiacrognale_events`

## Cosa teniamo
- Design token: avorio `#FAF8F5`, beige `#E8E2D9`, salvia `#8B9A7D`, bordeaux `#722F37`, oro `#C9A962`, antracite `#2D2D2D`; Cormorant Garamond + Montserrat
- Logo, favicon, struttura della home (hero → chi sono → servizi → portfolio → recensione → Instagram → CTA)
- Testi: FAQ, 8 articoli blog, privacy e cookie policy, llms.txt / llms-full.txt
- Logica del preventivatore (senza prezzi automatici), da riscrivere come un unico componente bilingue
- JSON-LD LocalBusiness e Article, BreadcrumbList

## Cosa rifacciamo
- **Gallery**: oggi griglia 2/3 colonne con prima foto grande, immagini a 800px, nessun lightbox, nessuna navigazione, `aspect` forzato che taglia le foto
- **Hero e animazioni**: solo fade-in allo scroll; da portare a un livello editoriale
- **Instagram**: caricamento manuale → feed automatico via API
- **Blog**: unificare in un'unica fonte (il back office)
- **Immagini**: 14 `<img>` senza `width`/`height` (rischio CLS), niente `srcset`/AVIF; nella v2 crop e varianti generate dal back office

## Problemi da sistemare nella v2
- URL incoerenti: layout e destination wedding puntano a `gaia-events-astro.vercel.app`, blog e robots a `gaiaevents.it` → un'unica costante `SITE_URL` da config
- `og-image.jpg` referenziata ma **non esiste** in `public/`
- Descrizione di default "Wedding & Event Planner in Toscana", ma il business è a Roma
- Email incoerenti: `gaiacrognale@gmail.com` nel JSON-LD, `info@gaiaevents.it` in privacy, cookie e llms.txt → **risolto**: vale `gaiacrognale@gmail.com`, già corretta nelle policy importate
- Bilingue solo su una pagina → **risolto**: IT/EN su tutto il sito, con la regola «niente traduzione, niente pagina»
- La chiave Web3Forms è nel codice di una repo pubblica (è una chiave pensata per stare nel frontend, ma con il back office nostro i form possono passare dal nostro PHP)

## Contenuti già importati nella v2 (14/10/2026)

**Correzione alla recon.** Articoli, FAQ, privacy e cookie **non erano su Sanity**:
stavano nel codice della v1. Sono già stati importati, solo in italiano.

| Cosa | Dove stava nella v1 | Dove sta ora |
|---|---|---|
| 8 articoli blog | `src/data/blog-articles.ts` | `posts` + `posts.example.json` |
| 12 FAQ in 4 gruppi | `src/pages/faq.astro` | `faqs` + `faq.example.json` |
| Privacy policy | `src/pages/privacy.astro` | `pages` (`privacy`) |
| Cookie policy | `src/pages/cookie.astro` | `pages` (`cookie`) |

L'import è in `admin/sql/seed-contenuti-v1.sql`. Nelle policy l'email è stata
corretta da `info@gaiaevents.it` a `gaiacrognale@gmail.com`.

L'inglese di questi contenuti è vuoto: finché resta così, le loro pagine EN non
vengono generate.

## Censimento di Sanity — fatto (14/10/2026)

Progetto `yfcp9735`, dataset `production`, API pubblica in sola lettura.
Backup completo in `E:\CLAUDE CODE\gaia-backup-sanity` (fuori dalla repo):
43 documenti e 25 immagini a dimensioni originali.

| Tipo | Quanti | Esito |
|---|---|---|
| `servizio` | 4 | **importati** |
| `impostazioni` | 1 | **importata** in parte (vedi sotto) |
| `evento` | 6 | scartati, materiale di prova |
| `recensione` | 6 | scartate, **demo confermate da Gaia** |
| `instagramGallery` | 1 (8 foto) | scartata |
| `sanity.imageAsset` | 25 | **nessuna usata, sono tutte fake** |
| `articoloBlog` | **0** | il tipo non esisteva: il blog era nel codice |

Dalle impostazioni sono entrati: indirizzo, pagina Facebook, meta description,
biografia in tre paragrafi, citazione, percorso, valori e i testi hero di home,
chi sono, servizi, portfolio e contatti. Import in
`admin/sql/seed-contenuti-sanity.sql`.

Che fosse in gran parte materiale di prova si vedeva: cinque eventi su sei senza
galleria, l'unico con le foto descritto da «testo prova testo prova», una
location «Palazzo Brancaccio, Roma PROVA», una recensione «Prova q23, hja,.»,
didascalie Instagram «jhjgbv» e una tappa di percorso «Prova / Test funzionalità».

**Risposte di Gaia (14/10/2026)**

- recensioni: tutte demo, non si usano. Quelle vere arrivano più avanti.
- immagini: le foto sono tutte fake, comprese quelle che sembravano lavori veri.
  Nessuna va sul sito nuovo; le foto le fornisce Manuel. (Favicon e logo in uso
  nella v2 arrivano dalla cartella `public/` della repo v1, non da Sanity.)
- gallery: gli originali dei fotografi ci sono già.
- indirizzo: confermato, `Viale Vaticano 53 - 00165 Roma`.

Quindi il backup è **documentazione del vecchio sito, non una riserva di
contenuti**: da Sanity non c'è altro da recuperare e il dataset si può spegnere.

Le due domande ancora aperte (pagina Facebook attiva? email sul dominio?) stanno
in [DA-CHIEDERE-A-GAIA.md](DA-CHIEDERE-A-GAIA.md).

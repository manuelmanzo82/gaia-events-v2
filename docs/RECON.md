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
- Email incoerenti: `gaiacrognale@gmail.com` nel JSON-LD, `info@gaiaevents.it` in privacy, cookie e llms.txt → da confermare quale usare
- Bilingue solo su una pagina: decidere se IT/EN su tutto il sito
- La chiave Web3Forms è nel codice di una repo pubblica (è una chiave pensata per stare nel frontend, ma con il back office nostro i form possono passare dal nostro PHP)

## Non verificato
- Quanti eventi, recensioni, articoli e immagini ci sono davvero su Sanity: l'API non è raggiungibile da questo ambiente. Lo controlliamo dallo studio o da Claude Code prima di spegnerlo.

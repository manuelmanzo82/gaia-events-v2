# Gaia Events v2 — Decisioni di design e contenuto (15/10/2026)

## Direzione visiva
- **Ibrido A + Haywood**: base chiara "Roma Editoriale" (avorio/bianco caldo, inchiostro al posto del nero, bordeaux unico accento forte, oro solo nei dettagli) con **alcune sezioni scure profonde** in stile Sarah Haywood (hero, citazioni, CTA finali) per un tocco cinematografico.
- Tipografia: Cormorant Garamond grande e leggero con corsivi + sans pulito. Font self-hosted (.woff2).
- Motion: Lenis + GSAP/ScrollTrigger (reveal di testi riga per riga, foto con maschera), View Transitions tra pagine. Tutto off con `prefers-reduced-motion`. Lento ed elegante, mai invadente.
- Obiettivo prestazioni: Lighthouse mobile ≥95, LCP < 2s.

## Punti fermi
- **Intro di apertura** (stile Sarah Haywood, scelta definitiva 16/10): velo a tutto schermo **avorio** (`--color-avorio`), il logo si costruisce **nei suoi colori originali** (ghirigoro pesca disegnato come tracciato a penna, testo in inchiostro), riga di prestigio in inchiostro e sottoriga in grigio caldo, **senza filetti oro**. In uscita prima logo e righe svaniscono salendo, poi il velo scorre verso l'alto e scopre l'hero. Durata ~5 s, **solo alla prima visita** (ricordata in sessionStorage/localStorage), saltabile con un clic o un tasto, disattivata con `prefers-reduced-motion`. Non deve bloccare il rendering: l'hero sotto è già carico e misurabile. Lo stato iniziale del velo lo decide uno snippet inline nel `<head>` prima dei CSS (classe `con-intro` su `<html>`, niente lampi); lì vive il flag **`INTRO_SEMPRE`**, oggi `true` per il testing (intro a ogni caricamento): **da rimettere a `false` prima del lancio**.
- **Hero desktop: video** in loop, muto, a tutto schermo, self-hosted (niente Vimeo, niente iframe): clip 15-20 s, 1080p, MP4 H.264 + WebM, 2-4 MB totali. Si mostra prima la **foto poster** (AVIF, preload, è l'elemento LCP), il video parte solo dopo il load e solo su desktop con connessione buona (no `saveData`, no 2g/3g). Fallback: foto in dissolvenza.
- **Hero mobile:** 3-4 foto in dissolvenza lenta (6-8 s) con leggero zoom, niente video.
- Back office: campo "video hero" (upload MP4, generazione automatica di poster e versioni compresse se il server lo consente, altrimenti upload delle versioni già pronte) + campo "foto hero" multiplo. Possibilità futura di passare a un URL Vimeo cambiando un solo campo.
- **Obiettivo prestazioni vincolante:** Lighthouse ≥95 sia mobile sia desktop, LCP < 2 s, CLS < 0,05. Il video non deve mai essere l'elemento LCP né entrare nel caricamento iniziale.
- **Logo**: stesse dimensioni della v1 (231×152, alto 80px mobile / 130px desktop), SVG con `currentColor`, cambia colore in base allo sfondo della sezione (`data-theme` + IntersectionObserver). **Stato**: il vettoriale ufficiale non esiste da nessuna parte (solo PNG; i favicon.svg della v1 sono PNG incapsulati); in `LogoGaia.astro` c'è un SVG **ricostruito per tracciatura** dal PNG 231×152 — ghirigoro su `--logo-accento` (pesca), testo su `currentColor`, monocromo sulle sezioni scure — **da sostituire** quando arriverà il sorgente ufficiale.
- **Bilingue IT/EN** ovunque.

## Riferimenti di motion e pagine (da sarahhaywood.com)
Studiare dal vivo queste pagine e reinterpretarle nello stile Gaia (non copiarle):
- **Home — testo che scorre, foto ferma** (sticky split): a sinistra il testo scorre, a destra la foto resta fissa; quando il testo arriva a un certo punto la foto cambia con la successiva (crossfade o maschera). Da usare per Servizi o Chi sono.
- **Home — schede immagine sovrapposte** che ruotano/si impilano verticalmente con lo scroll (stacked cards). Da usare per gli Highlights.
- **https://sarahhaywood.com/weddings/** — sezione che si "aggancia" e lo scroll verticale diventa scorrimento **orizzontale** (pinned horizontal scroll). Da usare per Highlights o Luoghi.
- **https://sarahhaywood.com/destinations/** — **mega card** a tutta larghezza per le destinazioni. Modello per la pagina **Luoghi**.
- **https://sarahhaywood.com/about/** — spunto per **Chi sono / Gaia**.
- **https://sarahhaywood.com/media/** — spunto per una pagina **Media/Stampa** e per l'impaginazione di **Partner** e **Recensioni**.

Regole per tutti questi effetti: GSAP ScrollTrigger + Lenis, versione semplificata e senza pin su mobile (scroll naturale), tutto disattivato con `prefers-reduced-motion`, nessun effetto deve peggiorare LCP/CLS o i punteggi Lighthouse ≥95.

## Portfolio: highlights, non matrimoni
- **Niente archivio "matrimonio per matrimonio".** Il sito mostra solo **highlights**: le foto migliori, scelte in base alla sezione/pagina in cui si trova l'utente.
- **2-3 storie complete** solo dei matrimoni più spettacolari, scelte da Gaia.
- Niente filtri, niente moodboard, niente cuori/preferiti.

## Pagine Luoghi (asse SEO principale)
- Struttura **Italia → Regione → Città/zona** (es. Lazio → Roma, Toscana → Val d'Orcia, Campania → Costiera Amalfitana).
- Ogni pagina luogo: testo **unico e vero** (IT/EN), foto generiche più belle della zona (non serve una foto per ogni location), **elenco delle location come testo** (nome, tipo: villa/castello/chiesa/terrazza…).
- Le location sono testo leggibile da Google e dalle AI, anche nei dati strutturati (JSON-LD).
- In futuro una location molto cercata può avere una pagina sua.

## Partner
- **Pagina Partner** dedicata: fotografi (crediti) e, se Gaia vuole, altri fornitori di fiducia (fiori, catering, musica, location). Nome/logo, una riga, link.
- **Niente crediti sotto le singole foto.**

## Preventivatore
- Parte **sempre vuoto**: niente precompilazione dal luogo o dalla pagina di provenienza.
- Da rifare a step, bilingue, senza prezzi automatici, con riepilogo finale, invio al nostro PHP.

## Recensioni
- Recensioni reali più avanti. **Non** collegate ai luoghi.

## Modello dati — FATTO (fase 2, 16/10/2026)
Implementato su `design/home` e migrato sullo staging. Decisioni prese in corso d'opera:
- **Luoghi**: gerarchia regione→zona via `parent_id`; **slug unici globalmente** (non per ramo); galleria della zona; cancellazione protetta (zone/location collegate bloccano con messaggio).
- **Location**: nome non tradotto; `luogo_id` con **ON DELETE RESTRICT**; flag `own_page` per il futuro, oggi inerte.
- **Highlights**: l'**alt vive su media** (unico per foto, non duplicato); sezioni con **ordine per sezione** (home, chi-sono, servizi, destination, luogo); niente didascalie con credito.
- **Partner**: regola **morbida** per l'EN — l'indice esiste se c'è almeno un partner attivo, la descrizione EN vuota nasconde solo il testo.
- **Storie**: slug bilingue come il blog; fotografo = riferimento a Partner; JSON-LD **Article, non Event**.
- **Hero per pagina**: tabella dedicata (`page_hero` + foto ordinate), non nel k/v settings. **Niente ffmpeg su IONOS**: Gaia carica dal back office MP4 + WebM già pronti (max 8 MB l'uno, MIME verificato) + poster via ImageService; `video_url` per un futuro Vimeo.
- **Tabella events: tenuta e congelata** (dentro solo dati di esempio, niente da migrare); il DROP con la rimozione di /portfolio arriva in fase 3, in un commit dedicato.
- **Rotte**: `/luoghi/` ↔ `/en/destinations/` (dettagli `/luoghi/[regione]/[zona]/` con slug tradotti), `/storie/` ↔ `/en/stories/`, `/partner/` ↔ `/en/partners/`. Luoghi e location dentro `llms.txt`/`llms-full.txt` e sitemap con alternate ricalcolati.

## Struttura home
1. Hero (scuro) con foto in dissolvenza, titolo breve, CTA "Raccontaci il vostro giorno" / "Tell us about your day"
2. Manifesto in una frase grande + firma di Gaia
3. Highlights (selezione editoriale)
4. Luoghi: Roma, Lazio e le altre regioni principali
5. Servizi: le 4 formule, lista numerata elegante
6. Chi è Gaia: ritratto + testo breve
7. Una recensione grande (sezione scura)
8. Destination wedding (aggancio EN)
9. Instagram (automatico, più avanti)
10. CTA finale verso il preventivatore (scura)

## Da chiedere a Gaia
- In quali regioni/città lavora davvero e dove vuole crescere?
- Solo matrimoni o anche eventi privati/aziendali?
- Elenco delle location che conosce bene, per luogo.
- 3-4 righe vere per ogni luogo (scritte o dettate).
- Quali 2-3 matrimoni raccontare per intero.
- Elenco dei partner da citare.

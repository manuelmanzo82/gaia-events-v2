# Gaia Events v2 — Decisioni di design e contenuto (15/10/2026)

## Direzione visiva
- **Ibrido A + Haywood**: base chiara "Roma Editoriale" (avorio/bianco caldo, inchiostro al posto del nero, bordeaux unico accento forte, oro solo nei dettagli) con **alcune sezioni scure profonde** in stile Sarah Haywood (hero, citazioni, CTA finali) per un tocco cinematografico.
- Tipografia: Cormorant Garamond grande e leggero con corsivi + sans pulito. Font self-hosted (.woff2).
- Motion: Lenis + GSAP/ScrollTrigger (reveal di testi riga per riga, foto con maschera), View Transitions tra pagine. Tutto off con `prefers-reduced-motion`. Lento ed elegante, mai invadente.
- Obiettivo prestazioni: Lighthouse mobile ≥95, LCP < 2s.

## Punti fermi
- **Intro di apertura** (stile Sarah Haywood): velo a tutto schermo nel colore scuro del brand (bordeaux profondo o inchiostro), il logo appare/si disegna in bianco, poi il velo scorre verso l'alto e scopre l'hero. Durata max 1,5-2 s, **solo alla prima visita** (ricordata in sessionStorage/localStorage), saltabile con un clic, disattivata con `prefers-reduced-motion`. Non deve bloccare il rendering: l'hero sotto è già carico e misurabile.
- **Hero desktop: video** in loop, muto, a tutto schermo, self-hosted (niente Vimeo, niente iframe): clip 15-20 s, 1080p, MP4 H.264 + WebM, 2-4 MB totali. Si mostra prima la **foto poster** (AVIF, preload, è l'elemento LCP), il video parte solo dopo il load e solo su desktop con connessione buona (no `saveData`, no 2g/3g). Fallback: foto in dissolvenza.
- **Hero mobile:** 3-4 foto in dissolvenza lenta (6-8 s) con leggero zoom, niente video.
- Back office: campo "video hero" (upload MP4, generazione automatica di poster e versioni compresse se il server lo consente, altrimenti upload delle versioni già pronte) + campo "foto hero" multiplo. Possibilità futura di passare a un URL Vimeo cambiando un solo campo.
- **Obiettivo prestazioni vincolante:** Lighthouse ≥95 sia mobile sia desktop, LCP < 2 s, CLS < 0,05. Il video non deve mai essere l'elemento LCP né entrare nel caricamento iniziale.
- **Logo**: stesse dimensioni della v1, SVG con `currentColor`, cambia colore in base allo sfondo della sezione (`data-theme` + IntersectionObserver).
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

## Modello dati da aggiungere al back office
- **Luoghi**: regione, città/zona, slug IT/EN, testo IT/EN, foto copertina, foto della zona, stato pubblicato.
- **Location**: nome, tipo, luogo collegato, descrizione breve opzionale, flag "pagina propria" (per il futuro).
- **Highlights**: foto con sezione/i in cui compaiono, luogo opzionale, ordine.
- **Partner**: nome, categoria, logo, descrizione IT/EN, link.
- **Storie**: rimpiazzano gli "eventi" attuali, poche e curate.

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

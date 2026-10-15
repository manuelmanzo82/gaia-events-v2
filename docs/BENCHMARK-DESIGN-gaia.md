# Gaia Events v2 — Benchmark design e direzioni

## 1. Chi abbiamo guardato

| Sito | Piattaforma | Hero | Tipografia | Motion | Cosa rubare |
|---|---|---|---|---|---|
| **Sarah Haywood** (Londra) | WordPress custom | Video a tutto schermo | Serif display custom + Manrope | **GSAP + ScrollTrigger + Lenis** | Logo in due versioni (scuro/chiaro) che cambia col fondo, menu a scomparsa, tono "quiet luxury", stampa (Vogue, Harper's) come prova sociale |
| **Colin Cowie** (NY) | WordPress + Slider Revolution | Video | Didot + Avenir | Slider classico | Titolo corto e sicuro ("40 anni di straordinario"), servizi in una riga |
| **Federica Beni** (Italia) | Showit | Nome gigante su nero, poi foto | Serif ultra-light + sans thin | Nessuna libreria | Ritmo lento, testi di una riga in grande, CTA "Inquire" ripetuta in alto e in fondo, ville con regione come tessere |
| **Emma Events** (Amalfi) | Showit | 3 foto affiancate | — | Nessuna | Portfolio organizzato **per destinazione** (Positano, Ravello, Capri) |
| **Mindy Weiss** (LA) | WordPress | Fascia con citazioni stampa | Azo Sans + serif | Nessuna | Ticker scorrevole con premi/stampa in apertura |
| **The Lake Como Wedding Planner** | WordPress custom | Testo + "scroll down" | — | Carosello | Progetti titolati "Nomi · Location", form di contatto con data, ospiti, luogo |
| **Easton Events** | Webflow | — | Montserrat | — | — |

## 2. Cosa emerge

**Il livello medio è più basso di quanto sembri.** Quasi tutti i planner italiani di fascia alta usano Showit o WordPress con page builder: belle foto, ma niente movimento curato, solo inglese, prestazioni mediocri. Chi fa davvero la differenza (Sarah Haywood) usa lo stack che possiamo usare noi: **GSAP + ScrollTrigger + Lenis**. Con Astro statico possiamo superarli anche su velocità e SEO.

**Pattern che funzionano:**
- Hero a tutto schermo, una sola grande immagine (o pochissime in dissolvenza lenta), con un titolo breve.
- Logo che cambia colore in base allo sfondo, menu essenziale.
- Prova sociale presto: stampa, premi, una recensione forte.
- Portfolio raccontato come **storie**: "Nomi · Location · Regione", copertina e poi la gallery.
- CTA unica e ripetuta ("Raccontaci il vostro giorno" / "Inquire") in alto, a metà e in fondo.
- Molto bianco, testi brevi, una sola idea per schermata.

**Errori da evitare (li fanno quasi tutti):**
- Solo inglese, o solo italiano: noi siamo IT/EN.
- Video in hero pesantissimi: noi facciamo foto AVIF con dissolvenza.
- Zoom disabilitato su mobile, form senza riepilogo, CTA tutte diverse.
- Portfolio come griglia piatta di 16 foto uguali.

## 3. Raccomandazioni tecniche (valgono per tutte le direzioni)

- **Hero:** 3-4 foto in dissolvenza lenta (6-8 s) con leggero zoom (Ken Burns 1.00→1.06). La prima immagine è precaricata in AVIF, le altre arrivano dopo il caricamento. Peso: circa 250-350 KB per la prima su desktop, 120 KB su mobile.
- **Logo:** SVG inline, colore guidato da `currentColor`. Ogni sezione dichiara `data-theme="dark|light"` e un IntersectionObserver cambia il colore dell'header (come già fa la v1).
- **Movimento:** Lenis per lo scroll morbido, GSAP + ScrollTrigger per i reveal (testi che salgono riga per riga, foto che si scoprono con maschera), View Transitions di Astro tra le pagine. Tutto disattivato con `prefers-reduced-motion`.
- **Prestazioni:** obiettivo Lighthouse ≥95 su mobile, LCP < 2 s. JS solo dove serve (isole).
- **Gallery:** layout editoriale a ritmo variabile (una grande, due affiancate, una verticale con testo), lightbox a schermo intero con frecce, swipe e tastiera, che rispetta `maxWidth` di ogni foto.

## 4. Tre direzioni possibili

### A — "Roma Editoriale" (evoluzione del Tuscan Elegance) ⭐ consigliata
Avorio e bianco caldo come base, testo in inchiostro (non nero puro), **bordeaux** come unico accento forte, oro solo in dettagli minimi. Cormorant Garamond molto grande e leggero per i titoli, con corsivi; sans pulito per il testo. Impaginazione da rivista: tanto spazio, foto grandi e asimmetriche, numeri e didascalie piccole. Movimento lento e morbido.
*Per chi:* coppie italiane e straniere che cercano eleganza classica romana. È la più vicina al sito attuale, ma molto più raffinata.

### B — "Notte Romana" (quiet luxury scuro)
Sezioni alternate chiaro/scuro con un blu notte o verde salvia molto profondo, oro sottile. Più cinematografica, foto che emergono dal buio. Ispirazione Sarah Haywood.
*Per chi:* destination wedding di fascia altissima. Rischio: più fredda, e si allontana dal brand attuale.

### C — "Diario di Matrimonio"
Ogni matrimonio è una storia a capitoli, con gallery orizzontale a scorrimento e testi brevi in corsivo. La home stessa scorre come un racconto. Palette Tuscan invariata.
*Per chi:* massimo impatto emotivo. Rischio: più complessa da aggiornare per Gaia.

## 5. Struttura home proposta (vale per A)

1. **Hero** a tutto schermo, foto in dissolvenza, titolo breve, CTA "Raccontaci il vostro giorno"
2. **Manifesto** in una frase grande + firma di Gaia
3. **Matrimoni scelti**: 3 storie con copertina grande ("Andrea & Federica · Villa … · Toscana")
4. **Servizi**: le 4 formule, in lista elegante numerata, non a card
5. **Chi è Gaia**: ritratto + breve testo
6. **Una recensione** grande
7. **Destination wedding** (aggancio EN)
8. **Instagram** (automatico)
9. **CTA finale** verso il preventivatore

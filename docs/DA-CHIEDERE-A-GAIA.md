# Domande per Gaia

Domande emerse dal censimento di Sanity del 14 ottobre 2026, con le risposte
ricevute. Tenere aggiornato: è il registro di cosa è stato deciso e perché.

---

## Da confermare subito (21/10/2026)

**Le date e i numeri della timeline di «Chi sono»** vengono dalla v1
(Sanity) e non sono mai stati verificati con Gaia: **2014** (il primo
matrimonio per un'amica), **2016** (prima collaborazione corporate),
**2018** (destination wedding), **2022** (150+ eventi). Il «150+» in
particolare è un numero che compare sul sito: se non è vero oggi, va
cambiato. Le foto delle quattro tappe le abbiamo scelte noi dalla
libreria (navata di gipsofila, serra di sera, castello sul mare,
scalinata della villa): si cambiano nel blocco «timeline» della pagina.

## Risolte

### 1. Le recensioni di Sanity sono vere o scritte per la demo?

**Risposta (14/10/2026): sono tutte demo. Non si usano.**

Le recensioni reali arriveranno più avanti. Nessuna delle sei è stata importata
e nessuna va recuperata dal backup.

Conseguenza: la sezione «dicono di me» resta con i segnaposto finché non ci
sono testi veri. Quando arriveranno serviranno comunque nome, evento, data e il
**consenso scritto** della coppia: sono dati personali di terzi.

---

### 2. Di chi sono le foto su Sanity, e possiamo usarle?

**Risposta (14/10/2026): sono tutte fake. Non se ne usa nessuna.**

Comprese le serie che sembravano lavori veri (`Camille-e-Phelipe`,
`Alexa-e-Roberto`, `Corporate-6`, `16vdimagephotography`). Restano nel backup
solo come documentazione del vecchio sito.

Le foto nuove le fornisce Manuel. Decade quindi anche la questione dei diritti:
non c'è niente da liberare, perché non si pubblica niente di quel materiale.

---

### 3. Le foto originali dei fotografi per la gallery

**Risposta (14/10/2026): già disponibili.**

Manuel ha gli originali dei fotografi. La gallery della v2 si progetta su quelli,
non sui file quadrati a 1024 px recuperati da Sanity.

Da tenere presente quando arriveranno i file: servono **non ritagliati** (al
ritaglio pensa il back office, che tiene anche il punto focale) e con un mix di
orizzontali e verticali, altrimenti l'impaginazione editoriale non sta in piedi.

---

### 4. Conferma dell'indirizzo

**Risposta (14/10/2026): confermato, Viale Vaticano 53 - 00165 Roma.**

Vale per ora, quindi è un dato da ricontrollare se l'attività si sposta.

L'indirizzo è in `site/src/config/site.ts` e compare nella pagina contatti e nel
JSON-LD `LocalBusiness`.

---

## Ancora aperte

Due cose minori, nessuna bloccante.

### 5. La pagina Facebook è attiva?

In Sanity c'era `facebook.com/GaiaCrognaleEventi`, mai citata prima. Per ora sta
solo nei `sameAs` del JSON-LD. Se è attiva e Gaia la usa, va messa anche nel piè
di pagina accanto a Instagram; se è abbandonata, meglio toglierla del tutto —
un profilo morto linkato dal sito fa più danno che altro.

### 7. Partita IVA

Sui siti delle attività italiane la P.IVA è obbligatoria (art. 35 DPR
633/72). Il footer ha la riga legale pronta con l'impostazione
`legal.piva` (Impostazioni → generale): finché è vuota il sito mostra il
segnaposto «[P.IVA da Gaia]». Serve il numero.

### 6. Un'email sul dominio?

Oggi è `gaiacrognale@gmail.com`. La v1 usava anche `info@gaiaevents.it` nelle
policy, ma quella casella non risulta esistere. Se prima del lancio si attiva una
casella sul dominio, conviene passare a quella: su un sito professionale una
Gmail si nota.

---

### 8. La frase della sezione «Ciao, sono Gaia» in home (19/10/2026)

In home la citazione lunga («Il mio obiettivo è trasformare la vostra
visione in realtà, curando ogni dettaglio…») non va più: serve una frase
corta. Per ora, scritta da noi nel campo `home.manifesto` (Impostazioni →
home):

- IT: «Io penso a tutto. Voi vivete ogni momento.»
- EN: «I take care of everything. You live every moment.»

**Da confermare con Gaia**, o da sostituire con una frase sua: è la sua
voce, deve riconoscerla. La frase lunga resta disponibile per la pagina
Chi sono.

---

### 9. Il preventivatore: promesse, fasce e domande (20/10/2026)

Testi scritti da noi per la pagina Preventivo, tutti modificabili in
Impostazioni → preventivo (IT/EN). **Da confermare con Gaia**, perché sono
impegni suoi verso le coppie:

- **«Risposta entro 48 ore»** — «Leggo ogni richiesta di persona e vi
  rispondo entro due giorni.» Le ore sono anche un'impostazione
  (`quote.replyHours`) e compaiono nel ringraziamento dopo l'invio.
- **«Primo colloquio gratuito»** — «Ci conosciamo a Roma o in
  videochiamata, senza costi.» È davvero gratuito? Anche fuori Roma?
- **«Nessun impegno»** — «Inviare la richiesta non vi vincola a nulla.»
- **Tre domande frequenti**: «Quanto costa il primo incontro?» (niente) ·
  «Lavori anche fuori Roma?» (sì, in tutta Italia e con chi arriva
  dall'estero) · «Con quanto anticipo conviene scriverti?» (12–18 mesi per
  un matrimonio, ma non è mai troppo tardi).
- **Fasce di budget**, sempre con «Preferiamo parlarne», e mai un prezzo
  calcolato:
  - matrimonio e unione civile: fino a 20.000 € · 20.000–40.000 € ·
    40.000–70.000 € · 70.000–100.000 € · oltre 100.000 €;
  - festa privata: fino a 5.000 € · 5.000–10.000 € · 10.000–20.000 € ·
    20.000–40.000 € · oltre 40.000 €;
  - evento aziendale: fino a 10.000 € · 10.000–25.000 € · 25.000–50.000 € ·
    50.000–100.000 € · oltre 100.000 €.
- **«Come mi avete conosciuta?»**: Instagram · Google · Passaparola · A un
  matrimonio · Da una location o un fornitore · ChatGPT o un'altra AI ·
  Altro. Ne manca qualcuna che conta per lei (Matrimonio.com, una fiera)?
- **Copia della lettera alla coppia**: c'è, ma è spenta
  (`quote.sendCopy` = 0). La vuole?
- **Foto dei passaggi**: tre hanno il watermark VD Image (Gaia al tavolo,
  candele, bouquet) e vanno sostituite insieme alle altre (vedi le note
  della libreria).

### 10. Che cosa comprende ogni servizio (20/10/2026)

Nel preventivatore, scegliendo «Wedding planning completo», «Coordinamento
del giorno» e «Design & Styling» si segnano da soli come inclusi e non si
possono togliere. «Destination wedding» si combina con tutto e si
preseleziona quando la coppia indica «Veniamo dall'estero». **Da
confermare con Gaia**: il completo comprende davvero il coordinamento e il
design? Ci sono altre inclusioni? Si cambia nel back office, Servizi →
campo «Include (altri servizi)», con gli slug separati da virgola.

Anche i sottotitoli italiani dei servizi sono scritti da noi (prima c'erano
quelli inglesi): «Servizio completo», «Il giorno dell'evento»,
«Creatività», «In Italia e nel mondo».

### 11. Pagina «Dicono di me»: recensioni, stampa, loghi dei clienti (21/10/2026)

La pagina (/dicono-di-me/ ↔ /en/kind-words/) ha tre blocchi e **oggi è
vuota, quindi non esiste né sta nel menu**: compare da sola appena uno dei
tre ha contenuti.

- **Recensioni** (back office → Recensioni): solo quelle vere, con nome,
  evento e data. Niente testimonianze inventate: le due di Sanity erano
  demo e non sono state riportate.
- **Stampa** (back office → Stampa): testata, titolo, data, link, logo
  della testata (opzionale), estratto IT/EN. Servono gli articoli e le
  interviste che parlano di Gaia, con il link.
- **Hanno scelto Gaia** (back office → Clienti): i loghi delle aziende.
  La bio cita Calzedonia, BNL e Zurich: **i loghi NON sono stati caricati**.
  Servono i file (SVG o PNG su fondo trasparente) e il **permesso scritto
  di ogni azienda** a usare il logo sul sito; senza, si pubblica solo il
  nome o nulla.

### 12. I tre eventi di prova in «I miei eventi» (21/10/2026)

Sullo staging ci sono tre eventi pubblicati, creati da noi con le foto già
in libreria raggruppate per cartella d'origine, così la pagina e la voce di
menu si vedono. Titoli provvisori e generici, **nessun nome né location**:

| Evento (staging) | Cartella d'origine | Foto |
|---|---|---|
| Un matrimonio in una villa storica | FOTO E VIDEO 2026 / Benedetta e Tommaso | copertina + 12 |
| Un matrimonio in un castello sul mare | FOTO E VIDEO 2026 / Vittoria e Davide | copertina + 12 |
| Un matrimonio tra edera e affreschi | FOTO 2025 STAGIONE / Flavia e Gabriele | copertina + 7 (la cartella non ne ha altre) |

Per ognuno servono da Gaia: **i nomi degli sposi** (solo con il loro
consenso a comparire sul sito), **la location** (nome vero, e se va
collegata a un Luogo), **la data**, **il racconto vero** al posto della
bozza segnata «[Bozza — testo da Gaia]», e **il fotografo** da collegare
tra i Partner (i file di Benedetta e Tommaso e di Vittoria e Davide non
portano il nome dello studio: non sappiamo se sono di Vilella, New Light o
Grasso). Le altre cartelle (Yael e Simone, Alexa e Roberto, Giorgia e
Mirko, John e Valeria, Michelle e Paolo) hanno 5–7 foto l'una, quasi tutte
già in home: si possono aggiungere come eventi quando arrivano più foto.

### 13. Pagina «Chi sono» — PRIORITÀ ALTA (21/10/2026)

La pagina nuova (/chi-sono/ ↔ /en/about/) mostra solo quello che c'è nel
back office; queste cose mancano e finché mancano le sezioni restano
nascoste:

- **I numeri** (Impostazioni → chi sono → Numero 1-4): valore + etichetta
  IT/EN, per esempio anni di attività, eventi realizzati, location, paesi.
  **Solo numeri veri.** Finché sono vuoti la sezione «I numeri» non esiste.
- **L'anno di inizio** (Impostazioni → chi sono → Anno di inizio): serve per
  la riga «Wedding & event planner a Roma, dal 20XX» sotto il nome.
- **Foto per le tappe del percorso** (Pagine → Chi sono → blocco «Il mio
  percorso»): oggi le quattro tappe (2014, 2016, 2018, 2022, importate da
  Sanity) non hanno foto; il campo c'è.
- **Altre foto di Gaia al lavoro** (Highlights, sezione «chi sono»): oggi ne
  usiamo quattro; la foto di apertura (planner-sala-ritratto) porta il
  watermark VD IMAGE ed è già in lista «da sostituire» — qui servono i
  ritratti veri.
- **Meta title e meta description** della pagina (Pagine → Chi sono) li
  abbiamo composti noi con la tagline e la prima frase del testo: bozza da
  confermare.
- **Il testo inglese** della pagina resta la bozza del 16/10 (sotto).

### 14. Pagina «Servizi» (21/10/2026)

- **BLOCCANTE PER IL LANCIO — consenso degli sposi della foto 57**
  (`sposi-fronte-luci-notte`, apertura della pagina Servizi): sono
  riconoscibili. Senza il loro consenso scritto, prima del lancio si passa
  alla foto 60 (`cerimonia-prato-vista-mare`, senza persone).
- **Tabella «Quale fa per voi?» — DA CONFERMARE**: le 12 voci e le spunte
  le abbiamo scritte noi (back office → «Confronto servizi»). Per ogni voce
  si indica il servizio che la comprende; il wedding planning completo
  eredita da solo quelle di coordinamento e design. Bozza attuale:
  location, ricerca e selezione dei fornitori, budget e contratti, inviti
  e conferme → planning; coordinamento dei fornitori scelti, timeline,
  coordinamento del giorno, imprevisti → coordinamento; concept e design,
  allestimenti e styling, scelta di fioristi e noleggi → design;
  sopralluoghi con i fornitori → coordinamento e design. Servono le voci
  vere e quali servizi le comprendono.
- **Altre persone riconoscibili nella pagina Servizi**: nei passi di «Come
  lavoro» gli sposi di `uscita-chiesa-arco-fiori` (Il giorno) e di
  `brindisi-sposi-tramonto` (Dopo il giorno); in «Non solo matrimoni» il
  cantante e la ragazza di `cantante-scalinata-villa`; nella chiusura la
  sposa di `sposa-bouquet-sguardo`. Serve il loro consenso, altrimenti si
  cambiano le foto (si fa dal back office senza toccare il codice, tranne
  i passi del metodo che oggi richiedono una migrazione).
- **Prezzi «a partire da»**: si mostrano sì o no? Se sì, la cifra per
  servizio.
- **Consulenza iniziale gratuita**: sì o no? La FAQ «La consulenza
  iniziale è a pagamento?» oggi risponde di sì (gratuita) e così le
  rassicurazioni del preventivo: confermare.
- **I 5 passi del metodo** (consulenza, proposta, progettazione, il giorno,
  dopo il giorno): i testi sono una bozza nostra.
- **Partner con consenso**: l'elenco dei fornitori da citare, con il loro
  consenso a comparire (nome, categoria, sito, logo o foto). Senza almeno
  un partner con consenso la sezione non compare.
- **FAQ in inglese**: nessuna FAQ ha la traduzione; sulla pagina EN la
  sezione resta nascosta.
- **Foto del Coordinamento del giorno**: quella del servizio
  (`planner-controlla-tavolo`) ha il watermark VD IMAGE.

### 15. Pagina «Destination wedding» (22/10/2026)

Pubblico soprattutto straniero: l'inglese conta più dell'italiano. Tutti i
testi sono una bozza nostra (IT ed EN), salvati nel database.

- **Lingue parlate — DA CONFERMARE**: oggi la pagina dice «in italiano e
  in inglese» / «in English and Italian» (colonna «La vostra lingua» e
  FAQ «In che lingua organizziamo tutto?»). Servono le lingue vere.
- **Regioni in cui lavora — DA CONFERMARE**: sullo staging ci sono Roma e
  Lazio, Toscana, Costiera Amalfitana e Puglia (pubblicate) e Lago di Como
  (non pubblicata: era nella vecchia bozza). **In produzione si pubblicano
  solo le regioni confermate da Gaia.** Servono anche:
  - le foto di Costiera Amalfitana e Puglia (oggi senza foto: mostrano
    solo il testo);
  - la conferma che la foto della Toscana (`tavolo-terrazza-colline`) sia
    stata scattata in Toscana;
  - i testi brevi delle regioni (back office → «Destination: regioni»).
- **Riti — DA VERIFICARE (informazioni legali)**: quali riti segue (civile,
  simbolico, religioso, altre confessioni) e **se cura lei le pratiche**
  (la vecchia bozza diceva «Vi guido fra i documenti»). I testi delle tre
  card e della FAQ «Possiamo sposarci legalmente in Italia?» sono volutamente
  generici: niente numeri di giorni, niente elenchi di documenti. Vanno
  fatti verificare prima del lancio.
- **Paesi di provenienza delle coppie**: da dove arrivano più spesso (serve
  per i testi e per capire quali lingue mettere).
- **Servizi per gli ospiti**: cosa cura direttamente tra cena di
  benvenuto, transfer, camere e esperienze, e cosa invece consiglia
  soltanto.
- **Anticipo minimo**: la FAQ oggi dice «almeno un anno prima».
- **Il conto alla rovescia** (12 mesi, 6, 3, la settimana, il giorno) e
  cosa succede a ogni tappa: bozza nostra.
- **Matrimoni arrivati da lontano**: quali eventi di «I miei eventi» sono
  destination wedding (casella nel back office). Senza, la sezione resta
  nascosta.
- **FAQ destination**: le sei domande e risposte, IT ed EN.
- **Foto**: nessuna persona riconoscibile nelle foto scelte. La 60 è
  l'apertura della pagina (e resta nella fascia di Servizi); nella
  timeline di Chi sono la tappa 2018 ora usa la 111.

### 16. Magazine (23/10/2026)

- **Budget — fasce reali**: l'articolo sul budget non ha più cifre (solo
  le proporzioni tra le voci: location e catering 40–50%, foto e video
  8–12%, fiori e allestimenti 8–15%). Servono le fasce di budget reali di
  Gaia, se vuole darle, e la conferma delle proporzioni.
- **Tutti i testi e le traduzioni inglesi** dei sei articoli pubblicati
  sono una bozza nostra, compresi i riquadri «In breve».
- **«10 domande al wedding planner»**: le domande 6–10 le abbiamo aggiunte
  noi (cosa è compreso, costi e pagamenti, chi c'è il giorno, come si resta
  in contatto, piano B per il meteo).
- **Fiori di primavera**: «collaboro con i migliori fioristi di Roma e
  d'Italia» è stato tolto; se vale, si rimette.
- **Video veri**: il Magazine accetta clip brevi (caricate dal back office)
  e video YouTube/Vimeo. Gaia ne ha? Quello sullo staging è una prova.
- **Copertine**: tre articoli non hanno una foto abbastanza nitida per la
  copertina (fiori, tendenze, tableau) e restano tipografici. Servono foto
  in alta risoluzione (almeno 2000 px di lato lungo) per quei temi.
- **Ritratto per la firma e il box autrice**: oggi è la foto nella serra,
  stretta sul volto. Un ritratto vero, anche piccolo, sarebbe meglio.

## Traduzioni in bozza da far rivedere a Gaia prima del lancio (16/10/2026)

Scritte da noi per non lasciare buchi nella home EN, **solo nel database
dello staging** (non negli example in repo). Tono elegante e naturale, non
letterale. Gaia le legge, corregge e conferma; fino ad allora la home EN le
mostra così come sono.

| Dove | Campo | Bozza EN |
|---|---|---|
| Impostazioni → home | `home.manifesto` | *Sostituita il 19/10, vedi sotto.* |
| Impostazioni → generale | `about.text` | For more than ten years I have devoted my life to creating dream weddings. My story with events began almost by chance, planning a dear friend's wedding — and from that moment I knew this was my calling. |
| Servizi → Wedding Planning Completo | titolo / sottotitolo / descrizione / 8 punti | Full Wedding Planning · Full service · A complete service that walks beside you from the first idea to the last dance. I take care of every part of the planning, so you can enjoy your engagement without the stress. |
| Servizi → Coordinamento del Giorno | titolo / sottotitolo / descrizione / 7 punti | Day-of Coordination · On the day · Everything is planned, and now you want a professional to run the day? This service makes sure it all unfolds exactly as it should, while you savour every moment. |
| Servizi → Design & Styling | titolo / sottotitolo / descrizione / 7 punti | Design & Styling · Creative · Beauty lives in the details. I create unique concepts and settings that turn any venue into a magical space, reflecting who you are and the style you love. |
| Servizi → Destination Wedding | titolo / sottotitolo / descrizione / 7 punti | Destination Wedding · Worldwide · Dreaming of a wedding far from home? I plan destination weddings across Italy and abroad, handling every logistical detail with the same care as a local event. |
| Pagina Chi sono | corpo (3 paragrafi + citazione) | Hello, I'm Gaia … (sostituisce il finto «Placeholder text.» che faceva esistere `/en/about/` senza testo) |
| Pagina Chi sono | blocco «Il mio percorso» → My journey | Where it all began · First corporate collaboration · Destination weddings · 150+ events, con i quattro testi |
| Pagina Chi sono | blocco «I miei valori» → My values | Elegance · Passion · Excellence · Creativity, con i quattro testi |
| Pagina Servizi | corpo | Discover the services designed to accompany you towards the most beautiful day of your life… |

I punti dei servizi e i testi dei blocchi si leggono per intero nel back
office, nelle schede corrispondenti (colonna English).

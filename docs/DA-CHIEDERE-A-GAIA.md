# Domande per Gaia

Domande emerse dal censimento di Sanity del 14 ottobre 2026, con le risposte
ricevute. Tenere aggiornato: è il registro di cosa è stato deciso e perché.

---

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

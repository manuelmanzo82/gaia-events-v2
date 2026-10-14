# Da chiedere a Gaia

Domande aperte emerse dal censimento di Sanity del 14 ottobre 2026, prima di
spegnerlo. Nessuna di queste si può decidere dal codice.

Aggiornare questo file quando arriva una risposta, e segnare cosa è stato fatto
di conseguenza.

---

## 1. Le recensioni sono vere o scritte per la demo?

**Stato: aperta — bloccante per pubblicarle**

Su Sanity c'erano 6 recensioni. Una è palesemente di prova («Prova q23, hja,.»).
Le altre 5 sono scritte bene, ma diversi indizi dicono che siano riempitivo:

- i riferimenti non tornano: la coppia «Sara & Marco» è un evento a Villa
  Cetinale, ma firma una recensione per Palazzo Brancaccio;
- un nome è «Maria Rossi», che in Italia è il segnaposto per eccellenza;
- le location citate sono le stesse dei sei eventi, che sono dimostrativi.

**Cosa serve sapere**

- Sono recensioni vere di clienti reali?
- Se sì: i nomi sono quelli giusti o erano stati cambiati?
- Abbiamo il **consenso** a pubblicarle con nome, evento e data? Senza consenso
  scritto non vanno online: sono dati personali di terzi.
- Se non ce ne sono di vere, meglio non avere affatto la sezione «dicono di me»
  che averla con testi inventati: le recensioni finte sono una pratica
  commerciale scorretta, non solo un problema di gusto.

Nel frattempo **nessuna recensione è stata importata**.

---

## 2. Di chi sono le foto, e possiamo usarle?

**Stato: aperta — bloccante per la gallery**

Nel backup ci sono foto che sembrano lavori veri, riconoscibili dal nome:

| Serie | Quante | Note |
|---|---|---|
| `Camille-e-Phelipe-*` | 8 | tutte 1024×1024 |
| `Alexa-e-Roberto-*` | 3 | 1024×1024 e 768×768 |
| `Corporate-6` | 1 | 1024×1024 |
| `16vdimagephotography_` | 1 | il nome sembra quello del fotografo |
| `senza-titolo-*` | 3 | nessuna indicazione |

**Cosa serve sapere**

- Sono eventi organizzati davvero da Gaia?
- **Chi detiene il copyright?** Di norma è il fotografo, non chi ha organizzato
  l'evento: serve una liberatoria scritta per pubblicarle sul sito.
- Le persone ritratte hanno dato il consenso a comparire online?
- Il fotografo va **accreditato**? Molti contratti lo richiedono.

---

## 3. Le foto originali dei fotografi per la gallery v2

**Stato: aperta — serve prima della fase di design**

Le immagini salvate da Sanity **non bastano** per la gallery nuova: 18 su 25
sono quadrate a 1024 px, cioè già ritagliate e ridotte per il vecchio sito. Su
uno schermo grande si vedrebbero sgranate, e il taglio quadrato impedisce
qualunque impaginazione editoriale.

**Cosa serve**

- I file **originali** dei fotografi, a piena risoluzione e **non ritagliati**
  (il ritaglio lo fa il back office, che tiene anche il punto focale).
- Un mix di orizzontali e verticali: una gallery fatta solo di quadrati non si
  può impaginare.
- Possibilmente raggruppati per evento, così entrano in portfolio con la loro
  storia invece che come immagini sciolte.

Senza questo materiale la gallery resta un segnaposto.

---

## 4. Conferma dell'indirizzo

**Stato: aperta — non bloccante**

Nelle impostazioni di Sanity c'era **Viale Vaticano 53 - 00165 Roma**, un dato
che non avevamo da nessun'altra parte.

**Cosa serve sapere**

- È l'indirizzo giusto e attuale?
- È uno **studio che riceve clienti** o un domicilio privato? Cambia tutto:
  se è privato non va pubblicato sul sito e non va messo su Google Business.
- Vuole che compaia nei contatti, o preferisce solo «Roma»?

Nel frattempo l'indirizzo è in `site/src/config/site.ts` e finisce **solo nel
JSON-LD** `LocalBusiness`, dove serve a qualificare l'attività come locale. Non
compare in nessuna pagina. Se la risposta è «è casa mia», va tolto anche da lì.

---

## 5. Altre due cose minori

- **Pagina Facebook**: in Sanity c'era `facebook.com/GaiaCrognaleEventi`, mai
  citata prima. È attiva e la vuole nel piè di pagina, o è abbandonata? Per ora
  è solo nei `sameAs` del JSON-LD.
- **Email**: la v1 usava `info@gaiaevents.it` nelle policy e
  `gaiacrognale@gmail.com` nel resto. Abbiamo uniformato su quella Gmail. Se
  esiste o esisterà una casella sul dominio, meglio passare a quella prima del
  lancio: su un sito professionale fa una certa differenza.

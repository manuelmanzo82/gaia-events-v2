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

### 6. Un'email sul dominio?

Oggi è `gaiacrognale@gmail.com`. La v1 usava anche `info@gaiaevents.it` nelle
policy, ma quella casella non risulta esistere. Se prima del lancio si attiva una
casella sul dominio, conviene passare a quella: su un sito professionale una
Gmail si nota.

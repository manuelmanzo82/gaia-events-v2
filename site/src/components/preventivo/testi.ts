/**
 * Testi del preventivatore (IT/EN in un solo posto), le fasce, le righe
 * del RIEPILOGO (giro 9: al posto della lettera) e il riepilogo per Gaia.
 *
 * Il riepilogo è schematico: etichetta → valore, una riga per passaggio,
 * ognuna col suo «Modifica». Una risposta che manca diventa «Da definire»
 * o «Non indicato», mai un buco. Le fasce di budget cambiano col tipo di
 * evento (DA-CHIEDERE-A-GAIA n. 9: da confermare con lei).
 */

export type Lingua = 'it' | 'en';
export type TipoEvento = 'matrimonio' | 'unione_civile' | 'festa' | 'aziendale';
export type ModoData = '' | 'giorno' | 'mese' | 'da_definire';
/** La scelta del passaggio Dove (giro 10): città/zona è sempre a parte. */
export type ModoLuogo = '' | 'consigliateci' | 'location';

/** Vecchi salvataggi avevano anche 'zone' e 'libero': diventano «nessuna scelta». */
export const modoLuogo = (m: string): ModoLuogo => (m === 'consigliateci' || m === 'location' ? m : '');

/** Città o zona indicate: le zone scelte (nomi) o il testo libero. */
export function cittaZona(r: Risposte, zone: Zona[]): string {
  if (r.zones.length > 0) return r.zones.map((s) => zone.find((z) => z.slug === s)?.nome ?? s).join(', ');
  return r.zoneText.trim();
}
export type Contatto = 'email' | 'whatsapp' | 'telefono';

export const PASSAGGI = ['evento', 'voi', 'quando', 'dove', 'ospiti', 'aiuto', 'budget', 'contatti', 'riepilogo'] as const;
export type Passaggio = (typeof PASSAGGI)[number] | 'grazie';

export interface Risposte {
  kind: TipoEvento | '';
  name: string;
  partner: string;
  company: string;
  dateMode: ModoData;
  date: string;
  monthM: string; // '01'..'12'
  monthY: string; // '2027'
  zoneMode: ModoLuogo;
  zones: string[];
  zoneText: string;
  venue: string;
  abroad: boolean;
  guestsRange: string;
  guests: string;
  services: string[];
  /** Destination wedding aggiunto da solo con «Veniamo dall'estero». */
  destinationAuto: boolean;
  budget: string;
  email: string;
  phone: string;
  contactPref: Contatto;
  source: string;
  message: string;
  privacy: boolean;
}

export const RISPOSTE_VUOTE: Risposte = {
  kind: '',
  name: '',
  partner: '',
  company: '',
  dateMode: '',
  date: '',
  monthM: '',
  monthY: '',
  zoneMode: '',
  zones: [],
  zoneText: '',
  venue: '',
  abroad: false,
  guestsRange: '',
  guests: '',
  services: [],
  destinationAuto: false,
  budget: '',
  email: '',
  phone: '',
  contactPref: 'email',
  source: '',
  message: '',
  privacy: false,
};

/** Una zona pubblicata in Luoghi, come arriva dal JSON in build. */
export interface Zona {
  slug: string;
  nome: string;
  regione: boolean;
}

/** Un servizio pubblicato, come arriva dal JSON in build. */
export interface Servizio {
  slug: string;
  titolo: string;
  sottotitolo: string;
  /** Slug degli altri servizi compresi in questo (back office, «Include»). */
  include: string[];
}

/** Lo slug del servizio Destination wedding, preselezionato con «Veniamo dall'estero». */
export const SERVIZIO_DESTINATION = 'destination';

/** I servizi già compresi in quelli scelti: slug → titolo di chi li include. */
export function inclusiDa(scelti: string[], servizi: Servizio[]): Map<string, string> {
  const inclusi = new Map<string, string>();
  for (const slug of scelti) {
    const s = servizi.find((x) => x.slug === slug);
    for (const dentro of s?.include ?? []) if (dentro !== slug && !inclusi.has(dentro)) inclusi.set(dentro, s!.titolo);
  }
  return inclusi;
}

export const coppia = (k: TipoEvento | ''): boolean => k === 'matrimonio' || k === 'unione_civile';

// ---------------------------------------------------------------------
// Fasce
// ---------------------------------------------------------------------

export const FASCE_OSPITI = ['fino-50', '50-100', '100-150', '150-250', 'oltre-250'] as const;

/** Fasce di budget in migliaia di euro, per tipo di evento. */
const BUDGET: Record<'nozze' | 'festa' | 'aziendale', number[]> = {
  nozze: [20, 40, 70, 100],
  festa: [5, 10, 20, 40],
  aziendale: [10, 25, 50, 100],
};

export function fasceBudget(kind: TipoEvento | ''): string[] {
  const soglie = BUDGET[kind === 'festa' ? 'festa' : kind === 'aziendale' ? 'aziendale' : 'nozze'];
  const fasce = [`fino-${soglie[0]}`];
  for (let i = 0; i < soglie.length - 1; i++) fasce.push(`${soglie[i]}-${soglie[i + 1]}`);
  fasce.push(`oltre-${soglie[soglie.length - 1]}`);
  fasce.push('parlarne');
  return fasce;
}

/** 5000 → «5.000 €» / «€5,000» (it-IT non separa le migliaia sotto 10.000). */
const euro = (migliaia: number, lang: Lingua): string => {
  const cifre = String(migliaia * 1000).replace(/\B(?=(\d{3})+(?!\d))/g, lang === 'it' ? '.' : ',');
  return lang === 'it' ? `${cifre} €` : `€${cifre}`;
};

/** «e» davanti a una parola che comincia per e diventa «ed» (Sara ed Elena). */
const congiungi = (parola: string): string => (/^[eèé]/i.test(parola) ? 'ed' : 'e');

/** Etichetta di una fascia di budget per le schede del modulo. */
export function etichettaBudget(fascia: string, lang: Lingua): string {
  if (fascia === 'parlarne') return lang === 'it' ? 'Preferiamo parlarne' : "We'd rather talk about it";
  const [a, b] = fascia.split('-');
  if (a === 'fino') return lang === 'it' ? `fino a ${euro(+b, lang)}` : `up to ${euro(+b, lang)}`;
  if (a === 'oltre') return lang === 'it' ? `oltre ${euro(+b, lang)}` : `over ${euro(+b, lang)}`;
  return `${euro(+a, lang)} – ${euro(+b, lang)}`;
}

export function etichettaOspiti(fascia: string, lang: Lingua): string {
  const [a, b] = fascia.split('-');
  if (a === 'fino') return lang === 'it' ? `fino a ${b}` : `up to ${b}`;
  if (a === 'oltre') return lang === 'it' ? `oltre ${b}` : `over ${b}`;
  return `${a}–${b}`;
}

export const FONTI = ['instagram', 'google', 'passaparola', 'matrimonio', 'location', 'ai', 'altro'] as const;

const MESI: Record<Lingua, string[]> = {
  it: ['gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'],
  en: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
};

export const nomeMese = (m: number, lang: Lingua): string => MESI[lang][m - 1] ?? '';

// ---------------------------------------------------------------------
// Testi dell'interfaccia
// ---------------------------------------------------------------------

export const T = {
  it: {
    passaggi: ['Evento', 'Voi', 'Quando', 'Dove', 'Ospiti', 'Aiuto', 'Budget', 'Contatti', 'Riepilogo'],
    passaggioDi: (n: number, tot: number) => `Passaggio ${n} di ${tot}`,
    avanti: 'Avanti',
    indietro: 'Indietro',
    alRiepilogo: 'Vai al riepilogo',
    tornaRiepilogo: 'Torna al riepilogo',
    invia: 'Invia a Gaia',
    invio: 'Invio in corso…',
    ricomincia: 'Ricomincia',
    ripreso: 'Bentornati: ripartiamo da dove eravate rimasti.',
    facoltativo: 'facoltativo',

    evento: {
      titolo: 'Che cosa state immaginando?',
      opzioni: {
        matrimonio: ['Un matrimonio', 'Il giorno del vostro sì'],
        unione_civile: ["Un'unione civile", ''],
        festa: ['Una festa privata', 'Anniversario, compleanno, battesimo…'],
        aziendale: ['Un evento aziendale', 'Cena, convention, lancio, festa di fine anno'],
      } as Record<TipoEvento, [string, string]>,
      errore: 'Scegliete il tipo di evento.',
    },
    voi: {
      titoloCoppia: 'Iniziamo dai nomi: come vi chiamate?',
      titoloFesta: 'Come ti chiami?',
      titoloAzienda: 'Chi siete?',
      tuoNome: 'Il tuo nome',
      nomePartner: 'Il nome di chi sposi',
      azienda: 'Azienda',
      erroreNome: 'Scrivete il vostro nome.',
      errorePartner: 'Manca il secondo nome.',
      erroreAzienda: "Scrivete il nome dell'azienda.",
    },
    quando: {
      titolo: (k: TipoEvento | '', nomi: string) =>
        coppia(k)
          ? `${nomi}, quando immaginate il grande giorno?`
          : k === 'festa'
            ? `${nomi}, quando sarà la festa?`
            : "Quando si terrà l'evento?",
      data: 'Abbiamo la data',
      mese: 'Sappiamo il mese',
      nonSappiamo: 'Non lo sappiamo ancora',
      nonSappiamoNota: 'Nessun problema: ne parliamo insieme.',
      etichettaData: 'La data',
      etichettaMese: 'Mese',
      etichettaAnno: 'Anno',
      errore: 'Diteci quando, anche solo «non lo sappiamo ancora».',
      erroreData: 'Scegliete una data futura.',
      erroreMese: 'Scegliete mese e anno.',
    },
    dove: {
      titolo: (k: TipoEvento | '') => (coppia(k) ? 'Dove vi piacerebbe sposarvi?' : 'Dove si svolgerà?'),
      zoneAiuto: 'Potete sceglierne più di una.',
      libero: 'Città o zona che avete in mente',
      liberoLocation: 'Città o zona della location',
      liberoConsiglio: 'Città o zona, se avete già un’idea',
      consigliata: 'consigliata',
      consigliateci: 'Consigliateci voi',
      location: 'Abbiamo già la location',
      nomeLocation: 'Nome della location',
      estero: (k: TipoEvento | '') => (k === 'aziendale' ? "Gli ospiti arrivano dall'estero" : "Veniamo dall'estero"),
      errore: 'Scrivete una città o una zona, oppure scegliete «Consigliateci voi».',
      erroreZone: 'Scegliete almeno una zona, oppure «Consigliateci voi».',
      erroreLocation: 'Scrivete il nome della location.',
    },
    ospiti: {
      titolo: (k: TipoEvento | '') => (k === 'aziendale' ? 'Quante persone parteciperanno?' : 'Quanti ospiti immaginate?'),
      oppure: 'Oppure un numero indicativo',
      errore: 'Scegliete una fascia o scrivete un numero.',
      erroreNumero: 'Scrivete un numero tra 1 e 5000.',
    },
    aiuto: {
      titolo: (k: TipoEvento | '', nomi: string) =>
        k === 'aziendale' ? 'Come posso aiutarvi?' : k === 'festa' ? `${nomi}, come posso aiutarti?` : `${nomi}, come posso aiutarvi?`,
      aiuto: 'Potete sceglierne più di uno.',
      nonSo: 'Non lo so ancora, parliamone',
      errore: 'Scegliete almeno una voce.',
      incluso: 'Incluso',
      inclusoIn: (titolo: string) => `Incluso nel ${titolo}`,
    },
    budget: {
      titolo: 'C’è un budget a cui state pensando?',
      nota: 'Facoltativo: serve solo a proporvi la soluzione giusta.',
    },
    contatti: {
      titolo: 'Come posso ricontattarvi?',
      nome: 'Nome',
      email: 'Email',
      telefono: 'Telefono',
      preferenza: 'Preferite essere ricontattati via',
      pref: { email: 'Email', whatsapp: 'WhatsApp', telefono: 'Telefono' } as Record<Contatto, string>,
      fonte: 'Come mi avete conosciuta?',
      fonteVuota: 'Scegliete…',
      fonti: {
        instagram: 'Instagram',
        google: 'Google',
        passaparola: 'Passaparola',
        matrimonio: 'A un matrimonio',
        location: 'Da una location o un fornitore',
        ai: "ChatGPT o un'altra AI",
        altro: 'Altro',
      } as Record<string, string>,
      messaggio: 'Volete aggiungere qualcosa?',
      privacyPrima: 'Ho letto l’',
      privacyLink: 'informativa privacy',
      privacyDopo: ' e acconsento a essere ricontattati.',
      erroreNome: 'Scrivete il vostro nome.',
      erroreEmail: 'Serve un indirizzo email valido.',
      erroreTelefono: 'Per WhatsApp o telefono serve il numero.',
      errorePrivacy: 'Serve il consenso per potervi ricontattare.',
    },
    riepilogo: {
      titolo: 'Ecco il riepilogo',
      nota: 'Controllate e inviate a Gaia.',
      modifica: 'Modifica',
      modificaVoce: (voce: string) => `Modifica: ${voce}`,
      messaggio: 'Volete aggiungere qualcosa?',
      erroreInvio: 'Invio non riuscito. Riprovate tra poco, oppure scrivete a Gaia su WhatsApp.',
      voci: {
        evento: 'Evento',
        nomi: 'Nomi',
        nome: 'Nome',
        data: 'Data',
        luogo: 'Luogo',
        ospiti: 'Ospiti',
        servizi: 'Servizi',
        budget: 'Budget',
        contatto: 'Contatto preferito',
      },
      tipi: {
        matrimonio: 'Matrimonio',
        unione_civile: 'Unione civile',
        festa: 'Festa privata',
        aziendale: 'Evento aziendale',
      } as Record<TipoEvento, string>,
      daDefinire: 'Da definire',
      nonIndicato: 'Non indicato',
      consiglio: 'Da consigliare',
      location: 'Location',
      zona: 'Zona',
      estero: 'Dall’estero',
      circa: (n: string) => `circa ${n}`,
      nonSo: 'Da capire insieme',
    },
    grazie: {
      titolo: (nomi: string) => `Grazie, ${nomi}.`,
      /** Il testo segue il canale scelto e cita il recapito indicato. */
      testo: (ore: number, canale: Contatto, recapito: string, tu: boolean) => {
        const via =
          canale === 'whatsapp'
            ? `su WhatsApp al ${recapito}`
            : canale === 'telefono'
              ? `con una telefonata al ${recapito}`
              : `via email a ${recapito}`;
        return tu
          ? `La tua richiesta è arrivata. Gaia la leggerà con calma e ti risponderà entro ${ore} ore ${via}.`
          : `La vostra richiesta è arrivata. Gaia la leggerà con calma e vi risponderà entro ${ore} ore ${via}.`;
      },
      intanto: (tu: boolean) => (tu ? 'Nel frattempo, se ti va, puoi scriverle subito.' : 'Nel frattempo, se vi va, potete scriverle subito.'),
      whatsapp: 'Scrivi a Gaia su WhatsApp',
      whatsappDiscreto: 'Oppure scrivi a Gaia su WhatsApp',
      home: 'Torna alla home',
    },
  },
  en: {
    passaggi: ['Event', 'You', 'When', 'Where', 'Guests', 'Help', 'Budget', 'Contact', 'Summary'],
    passaggioDi: (n: number, tot: number) => `Step ${n} of ${tot}`,
    avanti: 'Next',
    indietro: 'Back',
    alRiepilogo: 'Review',
    tornaRiepilogo: 'Back to the summary',
    invia: 'Send to Gaia',
    invio: 'Sending…',
    ricomincia: 'Start again',
    ripreso: 'Welcome back: we picked up where you left off.',
    facoltativo: 'optional',

    evento: {
      titolo: 'What are you dreaming of?',
      opzioni: {
        matrimonio: ['A wedding', 'The day you say yes'],
        unione_civile: ['A civil union', ''],
        festa: ['A private party', 'Anniversary, birthday, christening…'],
        aziendale: ['A corporate event', 'Dinner, convention, launch, end-of-year party'],
      } as Record<TipoEvento, [string, string]>,
      errore: 'Please choose the type of event.',
    },
    voi: {
      titoloCoppia: "Let's start with your names",
      titoloFesta: "What's your name?",
      titoloAzienda: 'Who are you?',
      tuoNome: 'Your name',
      nomePartner: "Your partner's name",
      azienda: 'Company',
      erroreNome: 'Please write your name.',
      errorePartner: "Your partner's name is missing.",
      erroreAzienda: 'Please write the company name.',
    },
    quando: {
      titolo: (k: TipoEvento | '', nomi: string) =>
        coppia(k)
          ? `${nomi}, when do you picture the big day?`
          : k === 'festa'
            ? `${nomi}, when is the party?`
            : 'When will the event take place?',
      data: 'We have the date',
      mese: 'We know the month',
      nonSappiamo: "We don't know yet",
      nonSappiamoNota: "No problem: we'll talk it through together.",
      etichettaData: 'The date',
      etichettaMese: 'Month',
      etichettaAnno: 'Year',
      errore: "Tell us when, even just «we don't know yet».",
      erroreData: 'Please choose a future date.',
      erroreMese: 'Please choose month and year.',
    },
    dove: {
      titolo: (k: TipoEvento | '') => (coppia(k) ? 'Where would you love to get married?' : 'Where will it take place?'),
      zoneAiuto: 'You can choose more than one.',
      libero: 'City or area you have in mind',
      liberoLocation: 'City or area of the venue',
      liberoConsiglio: 'City or area, if you already have an idea',
      consigliata: 'recommended',
      consigliateci: 'We would love your advice',
      location: 'We already have a venue',
      nomeLocation: 'Name of the venue',
      estero: (k: TipoEvento | '') => (k === 'aziendale' ? 'Guests are coming from abroad' : 'We are coming from abroad'),
      errore: 'Write a city or an area, or choose «We would love your advice».',
      erroreZone: 'Choose at least one area, or «We would love your advice».',
      erroreLocation: 'Please write the name of the venue.',
    },
    ospiti: {
      titolo: (k: TipoEvento | '') => (k === 'aziendale' ? 'How many people will attend?' : 'How many guests do you picture?'),
      oppure: 'Or a rough number',
      errore: 'Choose a range or write a number.',
      erroreNumero: 'Please write a number between 1 and 5000.',
    },
    aiuto: {
      titolo: (k: TipoEvento | '', nomi: string) =>
        k === 'aziendale' ? 'How can I help you?' : `${nomi}, how can I help?`,
      aiuto: 'You can choose more than one.',
      nonSo: "I'm not sure yet, let's talk",
      errore: 'Please choose at least one.',
      incluso: 'Included',
      inclusoIn: (titolo: string) => `Included in ${titolo}`,
    },
    budget: {
      titolo: 'Do you have a budget in mind?',
      nota: 'Optional: it only helps me suggest the right solution.',
    },
    contatti: {
      titolo: 'How can I get back to you?',
      nome: 'Name',
      email: 'Email',
      telefono: 'Phone',
      preferenza: 'You would rather be contacted by',
      pref: { email: 'Email', whatsapp: 'WhatsApp', telefono: 'Phone' } as Record<Contatto, string>,
      fonte: 'How did you find me?',
      fonteVuota: 'Choose…',
      fonti: {
        instagram: 'Instagram',
        google: 'Google',
        passaparola: 'Word of mouth',
        matrimonio: 'At a wedding',
        location: 'Through a venue or supplier',
        ai: 'ChatGPT or another AI',
        altro: 'Other',
      } as Record<string, string>,
      messaggio: 'Anything you would like to add?',
      privacyPrima: 'I have read the ',
      privacyLink: 'privacy policy',
      privacyDopo: ' and agree to be contacted.',
      erroreNome: 'Please write your name.',
      erroreEmail: 'A valid email address is needed.',
      erroreTelefono: 'A phone number is needed for WhatsApp or a call.',
      errorePrivacy: 'Your consent is needed so I can get back to you.',
    },
    riepilogo: {
      titolo: 'Here is your summary',
      nota: 'Check it and send it to Gaia.',
      modifica: 'Edit',
      modificaVoce: (voce: string) => `Edit: ${voce}`,
      messaggio: 'Anything you would like to add?',
      erroreInvio: 'Sending failed. Please try again shortly, or message Gaia on WhatsApp.',
      voci: {
        evento: 'Event',
        nomi: 'Names',
        nome: 'Name',
        data: 'Date',
        luogo: 'Place',
        ospiti: 'Guests',
        servizi: 'Services',
        budget: 'Budget',
        contatto: 'Preferred contact',
      },
      tipi: {
        matrimonio: 'Wedding',
        unione_civile: 'Civil union',
        festa: 'Private party',
        aziendale: 'Corporate event',
      } as Record<TipoEvento, string>,
      daDefinire: 'To be decided',
      nonIndicato: 'Not given',
      consiglio: 'To be advised',
      location: 'Venue',
      zona: 'Area',
      estero: 'Coming from abroad',
      circa: (n: string) => `around ${n}`,
      nonSo: 'To work out together',
    },
    grazie: {
      titolo: (nomi: string) => `Thank you, ${nomi}.`,
      /** The text follows the chosen channel and quotes the contact given. */
      testo: (ore: number, canale: Contatto, recapito: string, _tu: boolean) => {
        const via =
          canale === 'whatsapp'
            ? `on WhatsApp at ${recapito}`
            : canale === 'telefono'
              ? `with a phone call at ${recapito}`
              : `by email at ${recapito}`;
        return `Your request has arrived. Gaia will read it carefully and get back to you within ${ore} hours ${via}.`;
      },
      intanto: (_tu: boolean) => 'In the meantime, if you like, you can message her right away.',
      whatsapp: 'Message Gaia on WhatsApp',
      whatsappDiscreto: 'Or message Gaia on WhatsApp',
      home: 'Back to home',
    },
  },
};

// ---------------------------------------------------------------------
// Nomi, luoghi, servizi in frase
// ---------------------------------------------------------------------

/** «Giulia e Marco», «Laura», «Paolo». */
export function nomi(r: Risposte, lang: Lingua): string {
  const a = r.name.trim();
  const b = r.partner.trim();
  if (coppia(r.kind) && a && b) return `${a} ${lang === 'it' ? congiungi(b) : '&'} ${b}`;
  return a;
}

// ---------------------------------------------------------------------
// Riepilogo: righe per il modulo (IT/EN) e per Gaia (sempre IT)
// ---------------------------------------------------------------------

export interface Riga {
  chiave: string;
  etichetta: string;
  valore: string;
  /** Righe secondarie (cosa include un servizio, la location…). */
  dettagli: string[];
  passaggio: Passaggio;
}

export function righeRiepilogo(r: Risposte, lang: Lingua, zone: Zona[], servizi: Servizio[]): Riga[] {
  const t = T[lang].riepilogo;
  const v = t.voci;
  const righe: Riga[] = [];
  const riga = (chiave: string, etichetta: string, valore: string, passaggio: Passaggio, dettagli: string[] = []): void => {
    righe.push({ chiave, etichetta, valore: valore || t.nonIndicato, dettagli: dettagli.filter(Boolean), passaggio });
  };

  if (r.kind) riga('evento', v.evento, t.tipi[r.kind], 'evento');

  const chi = nomi(r, lang);
  riga(
    'nomi',
    coppia(r.kind) ? v.nomi : v.nome,
    r.kind === 'aziendale' && r.company.trim() ? `${chi} — ${r.company.trim()}` : chi,
    'voi',
  );

  let data = t.daDefinire;
  if (r.dateMode === 'giorno' && r.date) {
    const [y, m, d] = r.date.split('-').map(Number);
    data = `${d} ${nomeMese(m, lang)} ${y}`;
  } else if (r.dateMode === 'mese' && r.monthM && r.monthY) {
    data = `${nomeMese(+r.monthM, lang)} ${r.monthY}`;
    if (lang === 'it') data = data.charAt(0).toUpperCase() + data.slice(1);
  }
  riga('data', v.data, data, 'quando');

  let luogo = '';
  const dettagliLuogo: string[] = [];
  // «Location: nome, città» · «Zona: …» · «Da consigliare» (con la zona, se c'è).
  const dove = cittaZona(r, zone);
  const modo = modoLuogo(r.zoneMode);
  if (modo === 'location') luogo = `${t.location}: ${[r.venue.trim(), dove].filter(Boolean).join(', ')}`;
  else if (modo === 'consigliateci') luogo = dove ? `${t.consiglio} · ${t.zona.toLowerCase()}: ${dove}` : t.consiglio;
  else if (dove) luogo = `${t.zona}: ${dove}`;
  if (r.abroad) dettagliLuogo.push(t.estero);
  riga('luogo', v.luogo, luogo, 'dove', dettagliLuogo);

  riga(
    'ospiti',
    v.ospiti,
    r.guests ? t.circa(r.guests) : r.guestsRange ? etichettaOspiti(r.guestsRange, lang) : '',
    'ospiti',
  );

  // Solo i titoli dei servizi scelti (giro 10), senza cosa includono.
  if (r.services.includes('da_definire')) {
    riga('servizi', v.servizi, t.nonSo, 'aiuto');
  } else {
    const titoli = r.services.map((slug) => servizi.find((s) => s.slug === slug)?.titolo).filter((x): x is string => !!x);
    riga('servizi', v.servizi, titoli.join(', '), 'aiuto');
  }

  riga('budget', v.budget, r.budget ? etichettaBudget(r.budget, lang) : t.nonIndicato, 'budget');

  const canale = T[lang].contatti.pref[r.contactPref];
  const recapito = r.contactPref === 'email' ? r.email.trim() : r.phone.trim();
  const altri: string[] = [];
  if (r.contactPref !== 'email' && r.email.trim()) altri.push(`Email: ${r.email.trim()}`);
  if (r.contactPref === 'email' && r.phone.trim()) altri.push(`${T[lang].contatti.telefono}: ${r.phone.trim()}`);
  riga('contatto', v.contatto, `${canale} · ${recapito}`, 'contatti', altri);

  return righe;
}

/** Il riepilogo per Gaia (email e back office): sempre in italiano. */
export function riepilogo(r: Risposte, zone: Zona[], servizi: Servizio[]): Array<{ label: string; value: string }> {
  const righe = righeRiepilogo(r, 'it', zone, servizi).map((g) => ({
    label: g.etichetta,
    value: [g.valore, ...g.dettagli].join('\n'),
  }));
  if (r.source) righe.push({ label: 'Come l’hanno conosciuta', value: T.it.contatti.fonti[r.source] ?? r.source });
  if (r.message.trim()) righe.push({ label: 'Messaggio', value: r.message.trim() });
  return righe;
}

export function messaggioWhatsApp(r: Risposte, lang: Lingua): string {
  const chi = nomi(r, lang);
  if (lang === 'it') {
    if (r.kind === 'matrimonio') return `Ciao Gaia, siamo ${chi}: ti abbiamo appena scritto dal sito per il nostro matrimonio.`;
    if (r.kind === 'unione_civile') return `Ciao Gaia, siamo ${chi}: ti abbiamo appena scritto dal sito per la nostra unione civile.`;
    if (r.kind === 'festa') return `Ciao Gaia, sono ${chi}: ti ho appena scritto dal sito per una festa privata.`;
    return `Ciao Gaia, sono ${chi}${r.company.trim() ? ` di ${r.company.trim()}` : ''}: ti ho appena scritto dal sito per un evento aziendale.`;
  }
  if (r.kind === 'matrimonio') return `Hi Gaia, we are ${chi}: we've just written to you from the website about our wedding.`;
  if (r.kind === 'unione_civile') return `Hi Gaia, we are ${chi}: we've just written to you from the website about our civil union.`;
  if (r.kind === 'festa') return `Hi Gaia, I'm ${chi}: I've just written to you from the website about a private party.`;
  return `Hi Gaia, I'm ${chi}${r.company.trim() ? ` from ${r.company.trim()}` : ''}: I've just written to you from the website about a corporate event.`;
}

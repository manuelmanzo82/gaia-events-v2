/**
 * Testi del preventivatore (IT/EN in un solo posto), le fasce, le
 * etichette del riepilogo per Gaia e la LETTERA generata dalle risposte.
 *
 * La lettera è fatta di frasi complete, una per risposta: una risposta
 * che manca toglie la frase intera, non lascia buchi né frasi
 * sgrammaticate. Registro: matrimonio e unione civile «noi» (i due nomi),
 * festa «io», azienda «noi» con la firma dell'azienda; a Gaia sempre del
 * tu. Le fasce di budget cambiano col tipo di evento (DA-CHIEDERE-A-GAIA
 * n. 9: da confermare con lei).
 */

export type Lingua = 'it' | 'en';
export type TipoEvento = 'matrimonio' | 'unione_civile' | 'festa' | 'aziendale';
export type ModoData = '' | 'giorno' | 'mese' | 'da_definire';
export type ModoLuogo = '' | 'zone' | 'libero' | 'consigliateci' | 'location';
export type Contatto = 'email' | 'whatsapp' | 'telefono';

export const PASSAGGI = ['evento', 'voi', 'quando', 'dove', 'ospiti', 'aiuto', 'budget', 'contatti', 'lettera'] as const;
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
  budget: string;
  email: string;
  phone: string;
  contactPref: Contatto;
  source: string;
  message: string;
  privacy: boolean;
  letter: string;
  letterEdited: boolean;
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
  budget: '',
  email: '',
  phone: '',
  contactPref: 'email',
  source: '',
  message: '',
  privacy: false,
  letter: '',
  letterEdited: false,
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

/** La stessa fascia, dentro una frase della lettera. */
function budgetInFrase(fascia: string, lang: Lingua): string {
  const [a, b] = fascia.split('-');
  if (a === 'fino') return lang === 'it' ? `fino a ${euro(+b, lang)}` : `of up to ${euro(+b, lang)}`;
  if (a === 'oltre') return lang === 'it' ? `oltre ${euro(+b, lang)}` : `of over ${euro(+b, lang)}`;
  return lang === 'it' ? `tra ${euro(+a, lang)} e ${euro(+b, lang)}` : `between ${euro(+a, lang)} and ${euro(+b, lang)}`;
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
    passaggi: ['Evento', 'Voi', 'Quando', 'Dove', 'Ospiti', 'Aiuto', 'Budget', 'Contatti', 'Lettera'],
    passaggioDi: (n: number, tot: number) => `Passaggio ${n} di ${tot}`,
    avanti: 'Avanti',
    indietro: 'Indietro',
    allaLettera: 'Scrivi la lettera',
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
      consigliateci: 'Consigliateci voi',
      location: 'Abbiamo già la location',
      nomeLocation: 'Come si chiama?',
      estero: (k: TipoEvento | '') => (k === 'aziendale' ? "Gli ospiti arrivano dall'estero" : "Veniamo dall'estero"),
      errore: 'Scegliete una zona, scrivetela, oppure chiedete un consiglio.',
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
    lettera: {
      titolo: 'Ecco la vostra lettera per Gaia',
      nota: 'Potete cambiarla come volete prima di inviarla.',
      etichetta: 'La lettera',
      riscrivi: 'Riscrivila dalle risposte',
      errore: 'La lettera è vuota.',
      erroreInvio: 'Invio non riuscito. Riprovate tra poco, oppure scrivete a Gaia su WhatsApp.',
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
    passaggi: ['Event', 'You', 'When', 'Where', 'Guests', 'Help', 'Budget', 'Contact', 'Letter'],
    passaggioDi: (n: number, tot: number) => `Step ${n} of ${tot}`,
    avanti: 'Next',
    indietro: 'Back',
    allaLettera: 'Write the letter',
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
      consigliateci: 'We would love your advice',
      location: 'We already have a venue',
      nomeLocation: "What's it called?",
      estero: (k: TipoEvento | '') => (k === 'aziendale' ? 'Guests are coming from abroad' : 'We are coming from abroad'),
      errore: 'Choose an area, write one, or ask for advice.',
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
    lettera: {
      titolo: 'Here is your letter to Gaia',
      nota: 'Change it as you like before sending it.',
      etichetta: 'The letter',
      riscrivi: 'Rewrite it from your answers',
      errore: 'The letter is empty.',
      erroreInvio: 'Sending failed. Please try again shortly, or message Gaia on WhatsApp.',
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

/** Elenco naturale: «a, b e c» / «a, b and c». */
export function elenco(voci: string[], lang: Lingua, congiunzione?: string): string {
  if (voci.length <= 1) return voci[0] ?? '';
  const c = congiunzione ?? (lang === 'it' ? congiungi(voci[voci.length - 1]) : 'and');
  return `${voci.slice(0, -1).join(', ')} ${c} ${voci[voci.length - 1]}`;
}

/** Il nome di un servizio dentro una frase: tutto minuscolo, tranne le sigle. */
const inFrase = (s: string): string =>
  s
    .split(' ')
    .map((parola) => (/^[A-Z0-9]{2,}$/.test(parola) ? parola : parola.toLowerCase()))
    .join(' ');

// ---------------------------------------------------------------------
// La lettera
// ---------------------------------------------------------------------

export function componiLettera(r: Risposte, lang: Lingua, zone: Zona[], servizi: Servizio[]): string {
  const it = lang === 'it';
  const k = r.kind;
  const noi = k !== 'festa'; // la festa parla in prima persona singolare
  const chi = nomi(r, lang);
  const paragrafi: string[] = [];

  // 1. Chi, che cosa, quando, dove (zone dall'elenco), quanti.
  let quando = '';
  if (r.dateMode === 'giorno' && r.date) {
    const [y, m, d] = r.date.split('-').map(Number);
    quando = it ? ` il ${d} ${nomeMese(m, lang)} ${y}` : ` on ${d} ${nomeMese(m, lang)} ${y}`;
  } else if (r.dateMode === 'mese' && r.monthM && r.monthY) {
    quando = it ? ` a ${nomeMese(+r.monthM, lang)} ${r.monthY}` : ` in ${nomeMese(+r.monthM, lang)} ${r.monthY}`;
  }

  let dove = '';
  if (r.zoneMode === 'zone' && r.zones.length > 0) {
    const scelte = r.zones.map((slug) => zone.find((z) => z.slug === slug)).filter((z): z is Zona => !!z);
    if (scelte.length > 0) {
      dove = it
        ? ' ' + elenco(scelte.map((z) => `${z.regione ? 'in' : 'a'} ${z.nome}`), lang, 'o')
        : ` in ${elenco(scelte.map((z) => z.nome), lang, 'or')}`;
    }
  }

  let quanti = '';
  const persone = it ? (k === 'aziendale' ? 'persone' : 'ospiti') : k === 'aziendale' ? 'people' : 'guests';
  if (r.guests && Number(r.guests) > 0) {
    quanti = it ? `, con circa ${Number(r.guests)} ${persone}` : `, with around ${Number(r.guests)} ${persone}`;
  } else if (r.guestsRange) {
    const [a, b] = r.guestsRange.split('-');
    if (a === 'fino') quanti = it ? `, con al massimo ${b} ${persone}` : `, with up to ${b} ${persone}`;
    else if (a === 'oltre') quanti = it ? `, con più di ${b} ${persone}` : `, with more than ${b} ${persone}`;
    else quanti = it ? `, con ${a}–${b} ${persone}` : `, with ${a}–${b} ${persone}`;
  }

  const coda = `${quando}${dove}${quanti}`;
  let apertura: string;
  if (it) {
    if (k === 'matrimonio') apertura = `siamo ${chi} e sogniamo di sposarci${coda}.`;
    else if (k === 'unione_civile') apertura = `siamo ${chi} e sogniamo la nostra unione civile${coda}.`;
    else if (k === 'festa') apertura = `sono ${chi} e sto organizzando una festa privata${coda}.`;
    else apertura = `sono ${chi}${r.company.trim() ? ` di ${r.company.trim()}` : ''} e stiamo organizzando un evento aziendale${coda}.`;
  } else {
    if (k === 'matrimonio') apertura = `we are ${chi} and we dream of getting married${coda}.`;
    else if (k === 'unione_civile') apertura = `we are ${chi} and we dream of our civil union${coda}.`;
    else if (k === 'festa') apertura = `I'm ${chi} and I'm planning a private party${coda}.`;
    else apertura = `I'm ${chi}${r.company.trim() ? ` from ${r.company.trim()}` : ''} and we are planning a corporate event${coda}.`;
  }

  const frasi: string[] = [apertura];

  if (r.dateMode === 'da_definire' || (r.dateMode === 'mese' && !(r.monthM && r.monthY))) {
    frasi.push(
      it
        ? k === 'festa'
          ? 'Non ho ancora fissato la data.'
          : k === 'aziendale'
            ? 'La data è ancora da definire.'
            : 'Non abbiamo ancora fissato la data.'
        : k === 'festa'
          ? "I haven't set the date yet."
          : k === 'aziendale'
            ? 'The date is still to be decided.'
            : "We haven't set the date yet.",
    );
  }

  if (r.zoneMode === 'libero' && r.zoneText.trim()) {
    frasi.push(
      it
        ? `Come luogo ${noi ? 'abbiamo' : 'ho'} in mente: ${r.zoneText.trim()}.`
        : `For the place, ${noi ? 'we have' : 'I have'} in mind: ${r.zoneText.trim()}.`,
    );
  } else if (r.zoneMode === 'consigliateci') {
    frasi.push(
      it
        ? `Per il luogo ${noi ? 'ci' : 'mi'} piacerebbe un tuo consiglio.`
        : `${noi ? 'We' : 'I'} would love your advice on the venue.`,
    );
  } else if (r.zoneMode === 'location' && r.venue.trim()) {
    frasi.push(
      it
        ? `${noi ? 'Abbiamo' : 'Ho'} già scelto la location: ${r.venue.trim()}.`
        : `${noi ? 'We have' : 'I have'} already chosen the venue: ${r.venue.trim()}.`,
    );
  }

  if (r.abroad) {
    frasi.push(
      it
        ? k === 'aziendale'
          ? "Molti ospiti arriveranno dall'estero."
          : k === 'festa'
            ? "Arrivo dall'estero."
            : "Veniamo dall'estero."
        : k === 'aziendale'
          ? 'Many guests will be coming from abroad.'
          : k === 'festa'
            ? "I'm coming from abroad."
            : 'We are coming from abroad.',
    );
  }
  paragrafi.push(frasi.join(' '));

  // 2. Aiuto e budget.
  const aiuto: string[] = [];
  const scelti = r.services
    .filter((s) => s !== 'da_definire')
    .map((slug) => servizi.find((s) => s.slug === slug)?.titolo)
    .filter((s): s is string => !!s);
  if (scelti.length > 0) {
    aiuto.push(
      it
        ? `${noi ? 'Ci' : 'Mi'} piacerebbe il tuo aiuto per ${scelti.length > 1 ? 'questi servizi' : 'questo servizio'}: ${elenco(scelti.map(inFrase), lang)}.`
        : `${noi ? 'We' : 'I'} would love your help with ${elenco(scelti.map(inFrase), lang)}.`,
    );
  }
  if (r.services.includes('da_definire')) {
    aiuto.push(
      it
        ? noi
          ? 'Vorremmo capire insieme a te di che cosa abbiamo bisogno.'
          : 'Vorrei capire insieme a te di che cosa ho bisogno.'
        : noi
          ? "We'd like to work out together what we need."
          : "I'd like to work out together what I need.",
    );
  }
  if (r.budget === 'parlarne') {
    aiuto.push(
      it
        ? `Del budget ${noi ? 'preferiremmo' : 'preferirei'} parlare a voce.`
        : `${noi ? "We'd" : "I'd"} rather talk about the budget in person.`,
    );
  } else if (r.budget) {
    aiuto.push(
      it
        ? `${noi ? 'Abbiamo' : 'Ho'} in mente un budget ${budgetInFrase(r.budget, lang)}.`
        : `${noi ? 'We have' : 'I have'} a budget ${budgetInFrase(r.budget, lang)} in mind.`,
    );
  }
  if (aiuto.length > 0) paragrafi.push(aiuto.join(' '));

  // 3. Il messaggio libero, così com'è.
  if (r.message.trim()) paragrafi.push(r.message.trim());

  // 4. Come ricontattare.
  const tel = r.phone.trim();
  if (r.contactPref === 'whatsapp' && tel) {
    paragrafi.push(
      it
        ? `${noi ? 'Preferiamo che ci ricontatti' : 'Preferisco che mi ricontatti'} su WhatsApp, al ${tel}.`
        : `${noi ? "We'd" : "I'd"} prefer to be contacted on WhatsApp, at ${tel}.`,
    );
  } else if (r.contactPref === 'telefono' && tel) {
    paragrafi.push(it ? `${noi ? 'Preferiamo' : 'Preferisco'} una telefonata, al ${tel}.` : `${noi ? "We'd" : "I'd"} prefer a phone call, at ${tel}.`);
  } else if (r.email.trim()) {
    paragrafi.push(
      it
        ? `Puoi ${noi ? 'scriverci' : 'scrivermi'} all'indirizzo ${r.email.trim()}.`
        : `You can write to ${noi ? 'us' : 'me'} at ${r.email.trim()}.`,
    );
  }

  const saluto = k === 'aziendale' ? (it ? 'Buongiorno Gaia,' : 'Hello Gaia,') : it ? 'Cara Gaia,' : 'Dear Gaia,';
  const chiusa = k === 'aziendale' ? (it ? 'Grazie,' : 'Thank you,') : it ? 'A presto,' : 'See you soon,';
  const firma = k === 'aziendale' && r.company.trim() ? `${chi} — ${r.company.trim()}` : chi;

  return `${saluto}\n${paragrafi.join('\n\n')}\n\n${chiusa}\n${firma}`;
}

// ---------------------------------------------------------------------
// Riepilogo per Gaia (sempre in italiano) e messaggio WhatsApp
// ---------------------------------------------------------------------

export function riepilogo(r: Risposte, zone: Zona[], servizi: Servizio[]): Array<{ label: string; value: string }> {
  const righe: Array<{ label: string; value: string }> = [];
  const add = (label: string, value: string): void => {
    if (value.trim()) righe.push({ label, value: value.trim() });
  };
  const TIPI: Record<TipoEvento, string> = {
    matrimonio: 'Matrimonio',
    unione_civile: 'Unione civile',
    festa: 'Festa privata',
    aziendale: 'Evento aziendale',
  };
  if (r.kind) add('Tipo di evento', TIPI[r.kind]);
  add('Nome', r.name);
  if (coppia(r.kind)) add('Partner', r.partner);
  if (r.kind === 'aziendale') add('Azienda', r.company);
  if (r.dateMode === 'giorno' && r.date) {
    const [y, m, d] = r.date.split('-').map(Number);
    add('Quando', `${d} ${nomeMese(m, 'it')} ${y}`);
  } else if (r.dateMode === 'mese' && r.monthM && r.monthY) {
    add('Quando', `${nomeMese(+r.monthM, 'it')} ${r.monthY}`);
  } else {
    add('Quando', 'da definire');
  }
  if (r.zoneMode === 'zone') add('Dove', r.zones.map((s) => zone.find((z) => z.slug === s)?.nome ?? s).join(', '));
  if (r.zoneMode === 'libero') add('Dove', r.zoneText);
  if (r.zoneMode === 'consigliateci') add('Dove', 'chiedono un consiglio');
  if (r.zoneMode === 'location') add('Dove', `location già scelta: ${r.venue}`);
  if (r.abroad) add('Dall’estero', 'sì');
  add('Ospiti', r.guests ? `circa ${r.guests}` : r.guestsRange ? etichettaOspiti(r.guestsRange, 'it') : '');
  add(
    'Aiuto',
    r.services.map((s) => (s === 'da_definire' ? 'non lo sanno ancora' : (servizi.find((x) => x.slug === s)?.titolo ?? s))).join(', '),
  );
  add('Budget', r.budget ? etichettaBudget(r.budget, 'it') : 'non indicato');
  add('Ricontattare via', T.it.contatti.pref[r.contactPref]);
  if (r.source) add('Come l’hanno conosciuta', T.it.contatti.fonti[r.source] ?? r.source);
  add('Messaggio', r.message);
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

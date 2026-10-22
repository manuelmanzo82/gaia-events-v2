/**
 * Il preventivatore: un solo componente bilingue (isola React).
 *
 * Nove passaggi — Evento · Voi · Quando · Dove · Ospiti · Aiuto · Budget ·
 * Contatti · Riepilogo — poi il ringraziamento. Il riepilogo (giro 9, al
 * posto della lettera) è un elenco etichetta → valore, tutto visibile, con
 * un «Modifica» per riga che porta al passaggio e poi torna al riepilogo;
 * sotto, il messaggio libero che si allarga da solo. Nessun prezzo
 * calcolato, mai.
 *
 * Esperienza: barra sottile con i nomi dei passaggi; Avanti/Indietro;
 * Invio prosegue (è un vero <form>); al cambio di passaggio il focus va
 * sul titolo della domanda; errori in linea con aria-invalid e un'area
 * aria-live; salvataggio automatico in localStorage (try/catch) con
 * «Ricomincia» sempre disponibile, cancellato dopo l'invio; transizioni
 * morbide spente con prefers-reduced-motion (CSS).
 *
 * Foto: su desktop una colonna a sinistra con la foto del passaggio, in
 * dissolvenza incrociata; si caricano solo le foto dei passaggi visitati.
 * Su mobile (giro 10) la stessa foto in una fascia orizzontale bassa in
 * cima al passaggio: è nell'HTML iniziale con altezza fissa (niente CLS),
 * con le sorgenti valide solo sotto i 64rem (<source media>) e un
 * segnaposto vuoto come src, così su mobile parte subito (è l'LCP) e su
 * desktop, dove è nascosta, non si scarica. Variante da 480w: per una
 * fascia alta 160 px basta (sizes 270px).
 * Su mobile niente foto nei passaggi (resta solo nel ringraziamento):
 * la colonna è nascosta dal CSS e le immagini non vengono montate.
 *
 * Invio: JSON a admin/api/quote.php, con il campo trappola `website`, i
 * secondi di compilazione (antispam) e il riepilogo in italiano per Gaia.
 */
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';

import {
  FASCE_OSPITI,
  FONTI,
  PASSAGGI,
  RISPOSTE_VUOTE,
  T,
  coppia,
  etichettaBudget,
  etichettaOspiti,
  fasceBudget,
  messaggioWhatsApp,
  nomeMese,
  nomi,
  riepilogo,
  righeRiepilogo,
  inclusiDa,
  cittaZona,
  modoLuogo,
  SERVIZIO_DESTINATION,
  type Contatto,
  type Lingua,
  type Passaggio,
  type Risposte,
  type Servizio,
  type TipoEvento,
  type Zona,
} from './testi';
import './preventivatore.css';

interface FotoSorgente {
  type: string;
  srcset: string;
  sizes: string;
}

export interface Foto {
  sources: FotoSorgente[];
  src: string;
  width: number;
  height: number;
  alt: string;
  objectPosition?: string;
}

interface Props {
  lang: Lingua;
  zone: Zona[];
  servizi: Servizio[];
  /** Foto per passaggio (chiavi di PASSAGGI più «grazie»). */
  foto: Partial<Record<Passaggio, Foto | null>>;
  endpoint: string;
  privacyHref: string;
  homeHref: string;
  whatsapp: string;
  oreRisposta: number;
}

const CHIAVE = 'gaia-preventivo-v1';

interface Salvato {
  step: Passaggio;
  r: Risposte;
  inizio: number | null;
}

const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

function oggiIso(): string {
  const d = new Date();
  const p = (n: number): string => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
}

/** Gli errori di un passaggio, per campo. Vuoto = si può andare avanti. */
function valida(step: Passaggio, r: Risposte, lang: Lingua, haZone: boolean): Record<string, string> {
  const t = T[lang];
  const e: Record<string, string> = {};
  switch (step) {
    case 'evento':
      if (!r.kind) e.kind = t.evento.errore;
      break;
    case 'voi':
      if (!r.name.trim()) e.name = t.voi.erroreNome;
      if (coppia(r.kind) && !r.partner.trim()) e.partner = t.voi.errorePartner;
      if (r.kind === 'aziendale' && !r.company.trim()) e.company = t.voi.erroreAzienda;
      break;
    case 'quando':
      if (!r.dateMode) e.dateMode = t.quando.errore;
      else if (r.dateMode === 'giorno' && (!r.date || r.date < oggiIso())) e.date = t.quando.erroreData;
      else if (r.dateMode === 'mese' && (!r.monthM || !r.monthY)) e.month = t.quando.erroreMese;
      break;
    case 'dove': {
      // Location → nome obbligatorio; consiglio → niente di obbligatorio;
      // nessuna delle due → città o zona obbligatoria.
      const modo = modoLuogo(r.zoneMode);
      if (modo === 'location' && !r.venue.trim()) e.venue = t.dove.erroreLocation;
      if (modo === '' && r.zones.length === 0 && !r.zoneText.trim()) e.zone = haZone ? t.dove.erroreZone : t.dove.errore;
      break;
    }
    case 'ospiti':
      if (r.guests) {
        const n = Number(r.guests);
        if (!Number.isInteger(n) || n < 1 || n > 5000) e.guests = t.ospiti.erroreNumero;
      } else if (!r.guestsRange) {
        e.guests = t.ospiti.errore;
      }
      break;
    case 'aiuto':
      if (r.services.length === 0) e.services = t.aiuto.errore;
      break;
    case 'contatti':
      if (!r.name.trim()) e.cname = t.contatti.erroreNome;
      if (!EMAIL.test(r.email.trim())) e.email = t.contatti.erroreEmail;
      if (r.contactPref !== 'email' && !r.phone.trim()) e.phone = t.contatti.erroreTelefono;
      if (!r.privacy) e.privacy = t.contatti.errorePrivacy;
      break;
    default:
      break;
  }
  return e;
}

export default function Preventivatore(props: Props): ReactNode {
  const { lang, zone, servizi, foto } = props;
  const t = T[lang];

  const [r, setR] = useState<Risposte>(RISPOSTE_VUOTE);
  const [step, setStep] = useState<Passaggio>('evento');
  const [errori, setErrori] = useState<Record<string, string>>({});
  const [annuncio, setAnnuncio] = useState('');
  const [ripreso, setRipreso] = useState(false);
  const [inviando, setInviando] = useState(false);
  const [desktop, setDesktop] = useState(false);
  const [fotoViste, setFotoViste] = useState<Passaggio[]>(['evento']);
  const [caricato, setCaricato] = useState(false);
  const [daRiepilogo, setDaRiepilogo] = useState(false);

  const inizio = useRef<number | null>(null);
  const titolo = useRef<HTMLHeadingElement>(null);
  const radice = useRef<HTMLDivElement>(null);
  const trappola = useRef<HTMLInputElement>(null);
  const primoRender = useRef(true);
  const messaggio = useRef<HTMLTextAreaElement>(null);

  // Il messaggio del riepilogo si allarga col testo: niente scroll interno.
  useEffect(() => {
    const el = messaggio.current;
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight + 2}px`;
  }, [r.message, step]);

  // Ripresa da localStorage (dopo l'idratazione, per non disallineare l'HTML).
  useEffect(() => {
    try {
      const grezzo = window.localStorage.getItem(CHIAVE);
      if (grezzo) {
        const s = JSON.parse(grezzo) as Salvato;
        if (s && s.r && s.step && s.step !== 'grazie') {
          const passo = (PASSAGGI as readonly string[]).includes(s.step) ? s.step : 'riepilogo';
          setR({ ...RISPOSTE_VUOTE, ...s.r, zoneMode: modoLuogo(s.r.zoneMode ?? '') });
          setStep(passo);
          setFotoViste([passo]);
          inizio.current = s.inizio ?? null;
          if (s.step !== 'evento' || s.r.kind) setRipreso(true);
        }
      }
    } catch {
      /* storage bloccato o dati rovinati: si parte da capo */
    }

    // Servizio già scelto dal link «Chiedi un preventivo per questo» della
    // pagina Servizi (?servizio=<slug>): vince sullo stato salvato. Gli
    // inclusi si segnano da soli (inclusiDa). Il parametro si toglie
    // dall'indirizzo, così un ricaricamento non annulla le scelte fatte dopo.
    try {
      const url = new URL(window.location.href);
      const scelto = url.searchParams.get('servizio');
      if (scelto && servizi.some((x) => x.slug === scelto)) {
        setR((prima) => ({ ...prima, services: [scelto], destinationAuto: false }));
        url.searchParams.delete('servizio');
        window.history.replaceState(window.history.state, '', url.pathname + url.search + url.hash);
      }
    } catch {
      /* indirizzo non leggibile: niente preselezione */
    }
    setCaricato(true);
  }, []);

  // Salvataggio automatico a ogni risposta.
  useEffect(() => {
    if (!caricato || step === 'grazie') return;
    try {
      const s: Salvato = { step, r, inizio: inizio.current };
      window.localStorage.setItem(CHIAVE, JSON.stringify(s));
    } catch {
      /* niente storage, pazienza */
    }
  }, [r, step, caricato]);

  // Su mobile i flottanti (WhatsApp, torna-su) si nascondono mentre il
  // modulo è sullo schermo: coprirebbero scelte, campi e «Ricomincia». Nel
  // ringraziamento tornano (lì il WhatsApp è nel contenuto).
  useEffect(() => {
    const el = radice.current;
    if (!el || !('IntersectionObserver' in window)) return;
    const html = document.documentElement;
    const io = new IntersectionObserver(
      ([voce]) => html.classList.toggle('modulo-in-vista', !!voce?.isIntersecting && step !== 'grazie'),
      { threshold: 0 },
    );
    io.observe(el);
    return () => {
      io.disconnect();
      html.classList.remove('modulo-in-vista');
    };
  }, [step]);

  // Colonna delle foto solo da 64rem: su mobile non si monta nemmeno.
  useEffect(() => {
    const mq = window.matchMedia('(min-width: 64rem)');
    const aggiorna = (): void => setDesktop(mq.matches);
    aggiorna();
    mq.addEventListener('change', aggiorna);
    return () => mq.removeEventListener('change', aggiorna);
  }, []);

  // Cambio di passaggio: focus sul titolo e la domanda in vista.
  useEffect(() => {
    if (primoRender.current) {
      primoRender.current = false;
      return;
    }
    // Dopo l'invio la testa della pagina si toglie (classe su <html>, CSS in
    // QuotePage) e la vista torna in cima: il ringraziamento sta subito
    // sotto lo spazio dell'header, senza titoli che ci passano sotto.
    if (step === 'grazie') {
      setFotoViste((viste) => (viste.includes('grazie') ? viste : [...viste, 'grazie']));
      document.documentElement.classList.add('preventivo-inviato');
      const riduci = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.scrollTo({ top: 0, behavior: riduci ? 'auto' : 'smooth' });
      titolo.current?.focus({ preventScroll: true });
      return;
    }
    setFotoViste((viste) => (viste.includes(step) ? viste : [...viste, step]));
    const el = radice.current;
    if (el) {
      const top = el.getBoundingClientRect().top;
      const header = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--sotto-header') || '96', 10);
      // La linea di avanzamento resta sempre sotto l'header, con aria.
      if (top < header + 16 || top > window.innerHeight * 0.5) {
        const y = window.scrollY + top - header - 32;
        const riduci = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: y, behavior: riduci ? 'auto' : 'smooth' });
      }
    }
    titolo.current?.focus({ preventScroll: true });
  }, [step]);

  const aggiorna = (parziale: Partial<Risposte>): void => {
    if (inizio.current === null) inizio.current = Date.now();
    setR((prima) => ({ ...prima, ...parziale }));
    // Un errore sparisce appena il campo viene toccato.
    setErrori((prima) => {
      const resto = { ...prima };
      for (const k of Object.keys(parziale)) delete resto[k];
      if ('zones' in parziale || 'zoneMode' in parziale || 'zoneText' in parziale) delete resto.zone;
      if ('zoneMode' in parziale) delete resto.venue;
      if ('guestsRange' in parziale) delete resto.guests;
      if ('monthM' in parziale || 'monthY' in parziale) delete resto.month;
      if ('name' in parziale) delete resto.cname;
      return resto;
    });
  };

  const indice = step === 'grazie' ? PASSAGGI.length : PASSAGGI.indexOf(step);
  const chi = nomi(r, lang) || (lang === 'it' ? 'Voi' : 'You');

  /** Porta il focus sul campo del primo errore (dopo il rendering). */
  const focusErrore = (e: Record<string, string>): void => {
    const ID: Record<string, string> = {
      name: 'pv-name',
      partner: 'pv-partner',
      company: 'pv-company',
      date: 'pv-date',
      month: 'pv-month',
      venue: 'pv-venue',
      zone: 'pv-zonetext',
      guests: 'pv-guests',
      cname: 'pv-cname',
      email: 'pv-email',
      phone: 'pv-phone',
      privacy: 'pv-privacy',
    };
    const chiave = Object.keys(e)[0];
    window.setTimeout(() => {
      const el =
        (ID[chiave] && document.getElementById(ID[chiave])) ||
        document.querySelector<HTMLElement>(`[aria-describedby="pv-err-${chiave}"] input, [aria-describedby="pv-err-${chiave}"]`);
      (el as HTMLElement | null)?.focus();
    }, 0);
  };

  const avanti = (): void => {
    const e = valida(step, r, lang, zone.length > 0);
    if (Object.keys(e).length > 0) {
      setErrori(e);
      setAnnuncio(Object.values(e)[0]);
      focusErrore(e);
      return;
    }
    setErrori({});
    setAnnuncio('');
    // Dal riepilogo (o arrivando al riepilogo) si controlla tutto: se una
    // modifica ha reso incompleto un altro passaggio (es. un altro tipo di
    // evento), si va lì prima.
    const prossimo = daRiepilogo ? 'riepilogo' : PASSAGGI[indice + 1];
    if (prossimo === 'riepilogo') {
      const mancante = PASSAGGI.slice(0, -1).find((p) => Object.keys(valida(p, r, lang, zone.length > 0)).length > 0);
      if (mancante && mancante !== step) {
        setDaRiepilogo(true);
        setStep(mancante);
        return;
      }
      setDaRiepilogo(false);
    }
    if (prossimo) setStep(prossimo);
  };

  const modifica = (passo: Passaggio): void => {
    setErrori({});
    setAnnuncio('');
    setDaRiepilogo(true);
    setStep(passo);
  };

  const indietro = (): void => {
    setErrori({});
    setAnnuncio('');
    const prima = PASSAGGI[indice - 1];
    if (prima) setStep(prima);
  };

  const ricomincia = (): void => {
    try {
      window.localStorage.removeItem(CHIAVE);
    } catch {
      /* niente */
    }
    inizio.current = null;
    setDaRiepilogo(false);
    setR(RISPOSTE_VUOTE);
    setErrori({});
    setAnnuncio('');
    setRipreso(false);
    setStep('evento');
  };

  const invia = async (): Promise<void> => {
    setInviando(true);
    setAnnuncio(t.invio);
    try {
      const risposta = await fetch(props.endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({
          lang,
          kind: r.kind,
          name: r.name.trim(),
          partner: coppia(r.kind) ? r.partner.trim() : '',
          company: r.kind === 'aziendale' ? r.company.trim() : '',
          email: r.email.trim(),
          phone: r.phone.trim(),
          contactPref: r.contactPref,
          dateMode: r.dateMode,
          date: r.dateMode === 'giorno' ? r.date : '',
          month: r.dateMode === 'mese' && r.monthY && r.monthM ? `${r.monthY}-${r.monthM}` : '',
          // zoneMode per il server: la scelta, oppure da dove viene la città.
          zoneMode: modoLuogo(r.zoneMode) || (r.zones.length > 0 ? 'zone' : 'libero'),
          zones: r.zones.map((s) => zone.find((z) => z.slug === s)?.nome ?? s),
          zoneText: r.zones.length > 0 ? '' : r.zoneText.trim(),
          venue: modoLuogo(r.zoneMode) === 'location' ? r.venue.trim() : '',
          abroad: r.abroad,
          guestsRange: r.guests ? '' : r.guestsRange,
          guests: r.guests ? Number(r.guests) : null,
          services: r.services,
          budget: r.budget,
          source: r.source,
          message: r.message.trim(),
          privacy: r.privacy,
          elapsed: inizio.current ? Math.round((Date.now() - inizio.current) / 1000) : 0,
          website: trappola.current?.value ?? '',
          summary: riepilogo(r, zone, servizi),
        }),
      });
      const dati = (await risposta.json().catch(() => null)) as { ok?: boolean; error?: string } | null;
      if (!risposta.ok || !dati?.ok) {
        throw new Error(dati?.error || t.riepilogo.erroreInvio);
      }
      try {
        window.localStorage.removeItem(CHIAVE);
      } catch {
        /* niente */
      }
      setAnnuncio('');
      setStep('grazie');
    } catch (errore) {
      const testo = errore instanceof Error && errore.message ? errore.message : t.riepilogo.erroreInvio;
      setErrori({ invio: testo });
      setAnnuncio(testo);
    } finally {
      setInviando(false);
    }
  };

  const onSubmit = (e: { preventDefault(): void }): void => {
    e.preventDefault();
    if (inviando) return;
    if (step === 'riepilogo') void invia();
    else avanti();
  };

  /** Props comuni di un campo con il suo eventuale errore. */
  const campo = (chiave: string): { 'aria-invalid'?: true; 'aria-describedby'?: string } =>
    errori[chiave] ? { 'aria-invalid': true, 'aria-describedby': `pv-err-${chiave}` } : {};

  const Errore = ({ chiave }: { chiave: string }): ReactNode =>
    errori[chiave] ? (
      <p className="pv-errore" id={`pv-err-${chiave}`}>
        {errori[chiave]}
      </p>
    ) : null;

  const anni = useMemo(() => {
    const y = new Date().getFullYear();
    return [y, y + 1, y + 2, y + 3, y + 4].map(String);
  }, []);

  // -------------------------------------------------------------------
  // I passaggi
  // -------------------------------------------------------------------

  const domanda = ((): { titolo: string; nota?: string; corpo: ReactNode } => {
    switch (step) {
      case 'evento':
        return {
          titolo: t.evento.titolo,
          corpo: (
            <div className="pv-schede pv-schede--evento" role="radiogroup" aria-labelledby="pv-titolo" {...campo('kind')}>
              {(['matrimonio', 'unione_civile', 'festa', 'aziendale'] as TipoEvento[]).map((k) => (
                <label key={k} className={`pv-scheda${k === 'matrimonio' ? ' pv-scheda--grande' : ''}`}>
                  <input
                    type="radio"
                    name="pv-kind"
                    value={k}
                    checked={r.kind === k}
                    onChange={() => aggiorna({ kind: k, budget: '' })}
                  />
                  <span className="pv-scheda-titolo">{t.evento.opzioni[k][0]}</span>
                  {t.evento.opzioni[k][1] && <span className="pv-scheda-nota">{t.evento.opzioni[k][1]}</span>}
                </label>
              ))}
              <Errore chiave="kind" />
            </div>
          ),
        };

      case 'voi':
        return {
          titolo: coppia(r.kind) ? t.voi.titoloCoppia : r.kind === 'festa' ? t.voi.titoloFesta : t.voi.titoloAzienda,
          corpo: (
            <div className="pv-campi">
              <div className="pv-campo">
                <label htmlFor="pv-name">{t.voi.tuoNome}</label>
                <input
                  id="pv-name"
                  type="text"
                  autoComplete="given-name"
                  value={r.name}
                  onChange={(e) => aggiorna({ name: e.target.value })}
                  {...campo('name')}
                />
                <Errore chiave="name" />
              </div>
              {coppia(r.kind) && (
                <div className="pv-campo">
                  <label htmlFor="pv-partner">{t.voi.nomePartner}</label>
                  <input
                    id="pv-partner"
                    type="text"
                    autoComplete="off"
                    value={r.partner}
                    onChange={(e) => aggiorna({ partner: e.target.value })}
                    {...campo('partner')}
                  />
                  <Errore chiave="partner" />
                </div>
              )}
              {r.kind === 'aziendale' && (
                <div className="pv-campo">
                  <label htmlFor="pv-company">{t.voi.azienda}</label>
                  <input
                    id="pv-company"
                    type="text"
                    autoComplete="organization"
                    value={r.company}
                    onChange={(e) => aggiorna({ company: e.target.value })}
                    {...campo('company')}
                  />
                  <Errore chiave="company" />
                </div>
              )}
            </div>
          ),
        };

      case 'quando':
        return {
          titolo: t.quando.titolo(r.kind, chi),
          corpo: (
            <>
              <div className="pv-pillole" role="radiogroup" aria-labelledby="pv-titolo" {...campo('dateMode')}>
                {(
                  [
                    ['giorno', t.quando.data],
                    ['mese', t.quando.mese],
                    ['da_definire', t.quando.nonSappiamo],
                  ] as const
                ).map(([valore, etichetta]) => (
                  <label key={valore} className="pv-pillola">
                    <input
                      type="radio"
                      name="pv-datemode"
                      value={valore}
                      checked={r.dateMode === valore}
                      onChange={() => aggiorna({ dateMode: valore })}
                    />
                    <span>{etichetta}</span>
                  </label>
                ))}
              </div>
              <Errore chiave="dateMode" />
              {r.dateMode === 'giorno' && (
                <div className="pv-campo pv-campo--corto">
                  <label htmlFor="pv-date">{t.quando.etichettaData}</label>
                  <input
                    id="pv-date"
                    type="date"
                    min={oggiIso()}
                    value={r.date}
                    onChange={(e) => aggiorna({ date: e.target.value })}
                    {...campo('date')}
                  />
                  <Errore chiave="date" />
                </div>
              )}
              {r.dateMode === 'mese' && (
                <div className="pv-riga">
                  <div className="pv-campo">
                    <label htmlFor="pv-month">{t.quando.etichettaMese}</label>
                    <select id="pv-month" value={r.monthM} onChange={(e) => aggiorna({ monthM: e.target.value })} {...campo('month')}>
                      <option value="">—</option>
                      {Array.from({ length: 12 }, (_, i) => String(i + 1).padStart(2, '0')).map((m) => (
                        <option key={m} value={m}>
                          {nomeMese(Number(m), lang)}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="pv-campo">
                    <label htmlFor="pv-year">{t.quando.etichettaAnno}</label>
                    <select id="pv-year" value={r.monthY} onChange={(e) => aggiorna({ monthY: e.target.value })} {...campo('month')}>
                      <option value="">—</option>
                      {anni.map((y) => (
                        <option key={y} value={y}>
                          {y}
                        </option>
                      ))}
                    </select>
                  </div>
                  <Errore chiave="month" />
                </div>
              )}
              {r.dateMode === 'da_definire' && <p className="pv-nota">{t.quando.nonSappiamoNota}</p>}
            </>
          ),
        };

      case 'dove':
        return {
          titolo: t.dove.titolo(r.kind),
          nota: zone.length > 0 ? t.dove.zoneAiuto : undefined,
          corpo: (
            <>
              {/* Le due scelte si escludono a vicenda. */}
              <div className="pv-pillole pv-pillole--altre" role="radiogroup" aria-label={t.dove.titolo(r.kind)}>
                {(['consigliateci', 'location'] as const).map((m) => (
                  <label className="pv-pillola" key={m}>
                    <input
                      type="checkbox"
                      checked={modoLuogo(r.zoneMode) === m}
                      onChange={(e) => aggiorna({ zoneMode: e.target.checked ? m : '' })}
                    />
                    <span>{m === 'consigliateci' ? t.dove.consigliateci : t.dove.location}</span>
                  </label>
                ))}
              </div>
              {modoLuogo(r.zoneMode) === 'location' && (
                <div className="pv-campo">
                  <label htmlFor="pv-venue">{t.dove.nomeLocation}</label>
                  <input
                    id="pv-venue"
                    type="text"
                    required
                    value={r.venue}
                    onChange={(e) => aggiorna({ venue: e.target.value })}
                    {...campo('venue')}
                  />
                  <Errore chiave="venue" />
                </div>
              )}
              {zone.length > 0 ? (
                <div className="pv-campo">
                  <span className="pv-etichetta" id="pv-zone-titolo">
                    {modoLuogo(r.zoneMode) === 'location'
                      ? t.dove.liberoLocation
                      : modoLuogo(r.zoneMode) === 'consigliateci'
                        ? t.dove.liberoConsiglio
                        : t.dove.libero}
                    {modoLuogo(r.zoneMode) !== '' && (
                      <span className="pv-facoltativo">
                        {' '}
                        ({modoLuogo(r.zoneMode) === 'location' ? t.dove.consigliata : t.facoltativo})
                      </span>
                    )}
                  </span>
                  <div className="pv-pillole" role="group" aria-labelledby="pv-zone-titolo" {...campo('zone')}>
                    {zone.map((z) => (
                      <label key={z.slug} className="pv-pillola">
                        <input
                          type="checkbox"
                          checked={r.zones.includes(z.slug)}
                          onChange={(e) =>
                            aggiorna({ zones: e.target.checked ? [...r.zones, z.slug] : r.zones.filter((s) => s !== z.slug) })
                          }
                        />
                        <span>{z.nome}</span>
                      </label>
                    ))}
                  </div>
                  <Errore chiave="zone" />
                </div>
              ) : (
                <div className="pv-campo">
                  <label htmlFor="pv-zonetext">
                    {modoLuogo(r.zoneMode) === 'location'
                      ? t.dove.liberoLocation
                      : modoLuogo(r.zoneMode) === 'consigliateci'
                        ? t.dove.liberoConsiglio
                        : t.dove.libero}
                    {modoLuogo(r.zoneMode) !== '' && (
                      <span className="pv-facoltativo">
                        {' '}
                        ({modoLuogo(r.zoneMode) === 'location' ? t.dove.consigliata : t.facoltativo})
                      </span>
                    )}
                  </label>
                  <input
                    id="pv-zonetext"
                    type="text"
                    value={r.zoneText}
                    onChange={(e) => aggiorna({ zoneText: e.target.value })}
                    {...campo('zone')}
                  />
                  <Errore chiave="zone" />
                </div>
              )}
              <label className="pv-interruttore">
                <input
                  type="checkbox"
                  role="switch"
                  checked={r.abroad}
                  onChange={(e) => {
                    // «Veniamo dall'estero» preseleziona Destination wedding
                    // (se c'è e se non hanno scelto «Non lo so ancora»); spento,
                    // lo toglie solo se l'aveva aggiunto da solo.
                    const acceso = e.target.checked;
                    const esiste = servizi.some((s) => s.slug === SERVIZIO_DESTINATION);
                    let services = r.services;
                    let destinationAuto = r.destinationAuto;
                    if (acceso && esiste && !services.includes('da_definire') && !services.includes(SERVIZIO_DESTINATION)) {
                      services = [...services, SERVIZIO_DESTINATION];
                      destinationAuto = true;
                    } else if (!acceso && r.destinationAuto) {
                      services = services.filter((x) => x !== SERVIZIO_DESTINATION);
                      destinationAuto = false;
                    }
                    aggiorna({ abroad: acceso, services, destinationAuto });
                  }}
                />
                <span className="pv-interruttore-pista" aria-hidden="true" />
                <span>{t.dove.estero(r.kind)}</span>
              </label>
            </>
          ),
        };

      case 'ospiti':
        return {
          titolo: t.ospiti.titolo(r.kind),
          corpo: (
            <>
              <div className="pv-pillole" role="radiogroup" aria-labelledby="pv-titolo" {...campo('guests')}>
                {FASCE_OSPITI.map((f) => (
                  <label key={f} className="pv-pillola">
                    <input
                      type="radio"
                      name="pv-guests"
                      value={f}
                      checked={!r.guests && r.guestsRange === f}
                      onChange={() => aggiorna({ guestsRange: f, guests: '' })}
                    />
                    <span>{etichettaOspiti(f, lang)}</span>
                  </label>
                ))}
              </div>
              <div className="pv-campo">
                <label htmlFor="pv-guests">{t.ospiti.oppure}</label>
                <input
                  id="pv-guests"
                  type="text"
                  inputMode="numeric"
                  pattern="[0-9]*"
                  autoComplete="off"
                  value={r.guests}
                  onChange={(e) => aggiorna({ guests: e.target.value.replace(/\D+/g, ''), guestsRange: e.target.value ? '' : r.guestsRange })}
                  {...campo('guests')}
                />
              </div>
              <Errore chiave="guests" />
            </>
          ),
        };

      case 'aiuto': {
        const inclusi = inclusiDa(r.services, servizi);
        return {
          titolo: t.aiuto.titolo(r.kind, chi),
          nota: t.aiuto.aiuto,
          corpo: (
            <div className="pv-schede" role="group" aria-labelledby="pv-titolo" {...campo('services')}>
              {servizi.map((s) => {
                const compresoIn = inclusi.get(s.slug);
                const scelto = r.services.includes(s.slug);
                return (
                  <label
                    key={s.slug}
                    className={`pv-scheda pv-scheda--spunta${compresoIn ? ' pv-scheda--incluso' : ''}`}
                  >
                    <input
                      type="checkbox"
                      checked={scelto || !!compresoIn}
                      disabled={!!compresoIn}
                      aria-describedby={compresoIn ? `pv-incluso-${s.slug}` : undefined}
                      onChange={(e) => {
                        const prima = r.services.filter((x) => x !== 'da_definire');
                        if (e.target.checked) {
                          // Chi è compreso nel servizio appena scelto non si conta due volte.
                          const dentro = s.include ?? [];
                          aggiorna({ services: [...prima.filter((x) => !dentro.includes(x)), s.slug] });
                        } else {
                          aggiorna({
                            services: prima.filter((x) => x !== s.slug),
                            destinationAuto: s.slug === SERVIZIO_DESTINATION ? false : r.destinationAuto,
                          });
                        }
                      }}
                    />
                    <span className="pv-spunta" aria-hidden="true" />
                    <span className="pv-scheda-titolo">{s.titolo}</span>
                    {s.sottotitolo && <span className="pv-scheda-nota">{s.sottotitolo}</span>}
                    {compresoIn && (
                      <span className="pv-incluso" id={`pv-incluso-${s.slug}`}>
                        {t.aiuto.inclusoIn(compresoIn)}
                      </span>
                    )}
                  </label>
                );
              })}
              <label className="pv-scheda pv-scheda--spunta pv-scheda--lieve">
                <input
                  type="checkbox"
                  checked={r.services.includes('da_definire')}
                  onChange={(e) => aggiorna({ services: e.target.checked ? ['da_definire'] : [], destinationAuto: false })}
                />
                <span className="pv-spunta" aria-hidden="true" />
                <span className="pv-scheda-titolo">{t.aiuto.nonSo}</span>
              </label>
              <Errore chiave="services" />
            </div>
          ),
        };
      }

      case 'budget':
        return {
          titolo: t.budget.titolo,
          nota: t.budget.nota,
          corpo: (
            <div className="pv-pillole" role="radiogroup" aria-labelledby="pv-titolo">
              {fasceBudget(r.kind).map((f) => (
                <label key={f} className={`pv-pillola${f === 'parlarne' ? ' pv-pillola--lieve' : ''}`}>
                  <input type="radio" name="pv-budget" value={f} checked={r.budget === f} onChange={() => aggiorna({ budget: f })} />
                  <span>{etichettaBudget(f, lang)}</span>
                </label>
              ))}
            </div>
          ),
        };

      case 'contatti':
        return {
          titolo: t.contatti.titolo,
          corpo: (
            <div className="pv-campi">
              <div className="pv-riga">
                <div className="pv-campo">
                  <label htmlFor="pv-cname">{t.contatti.nome}</label>
                  <input
                    id="pv-cname"
                    type="text"
                    autoComplete="name"
                    value={r.name}
                    onChange={(e) => aggiorna({ name: e.target.value })}
                    {...campo('cname')}
                  />
                  <Errore chiave="cname" />
                </div>
                <div className="pv-campo">
                  <label htmlFor="pv-email">{t.contatti.email}</label>
                  <input
                    id="pv-email"
                    type="email"
                    autoComplete="email"
                    inputMode="email"
                    value={r.email}
                    onChange={(e) => aggiorna({ email: e.target.value })}
                    {...campo('email')}
                  />
                  <Errore chiave="email" />
                </div>
              </div>
              <div className="pv-campo">
                <label htmlFor="pv-phone">
                  {t.contatti.telefono}
                  {r.contactPref === 'email' && <span className="pv-facoltativo"> ({t.facoltativo})</span>}
                </label>
                <input
                  id="pv-phone"
                  type="tel"
                  autoComplete="tel"
                  value={r.phone}
                  onChange={(e) => aggiorna({ phone: e.target.value })}
                  {...campo('phone')}
                />
                <Errore chiave="phone" />
              </div>
              <div className="pv-campo">
                <span className="pv-etichetta" id="pv-pref-titolo">
                  {t.contatti.preferenza}
                </span>
                <div className="pv-pillole" role="radiogroup" aria-labelledby="pv-pref-titolo">
                  {(['email', 'whatsapp', 'telefono'] as Contatto[]).map((c) => (
                    <label key={c} className="pv-pillola">
                      <input
                        type="radio"
                        name="pv-pref"
                        value={c}
                        checked={r.contactPref === c}
                        onChange={() => aggiorna({ contactPref: c })}
                      />
                      <span>{t.contatti.pref[c]}</span>
                    </label>
                  ))}
                </div>
              </div>
              <div className="pv-campo pv-campo--corto">
                <label htmlFor="pv-source">
                  {t.contatti.fonte} <span className="pv-facoltativo">({t.facoltativo})</span>
                </label>
                <select id="pv-source" value={r.source} onChange={(e) => aggiorna({ source: e.target.value })}>
                  <option value="">{t.contatti.fonteVuota}</option>
                  {FONTI.map((f) => (
                    <option key={f} value={f}>
                      {t.contatti.fonti[f]}
                    </option>
                  ))}
                </select>
              </div>
              <label className="pv-consenso">
                <input id="pv-privacy" type="checkbox" checked={r.privacy} onChange={(e) => aggiorna({ privacy: e.target.checked })} {...campo('privacy')} />
                <span>
                  {t.contatti.privacyPrima}
                  <a href={props.privacyHref} target="_blank" rel="noopener">
                    {t.contatti.privacyLink}
                  </a>
                  {t.contatti.privacyDopo}
                </span>
              </label>
              <Errore chiave="privacy" />
            </div>
          ),
        };

      case 'riepilogo': {
        const righe = righeRiepilogo(r, lang, zone, servizi);
        return {
          titolo: t.riepilogo.titolo,
          nota: t.riepilogo.nota,
          corpo: (
            <>
              <dl className="pv-riepilogo">
                {righe.map((g) => (
                  <div className="pv-riepilogo-riga" key={g.chiave}>
                    <dt>{g.etichetta}</dt>
                    <dd>
                      <span className="pv-riepilogo-valore">{g.valore}</span>
                      {g.dettagli.map((d) => (
                        <span className="pv-riepilogo-dettaglio" key={d}>
                          {d}
                        </span>
                      ))}
                    </dd>
                    <button
                      type="button"
                      className="pv-link pv-riepilogo-modifica"
                      onClick={() => modifica(g.passaggio)}
                      aria-label={t.riepilogo.modificaVoce(g.etichetta)}
                    >
                      {t.riepilogo.modifica}
                    </button>
                  </div>
                ))}
              </dl>
              <div className="pv-campo">
                <label htmlFor="pv-message">
                  {t.riepilogo.messaggio} <span className="pv-facoltativo">({t.facoltativo})</span>
                </label>
                <textarea
                  id="pv-message"
                  ref={messaggio}
                  className="pv-messaggio"
                  rows={2}
                  value={r.message}
                  onChange={(e) => aggiorna({ message: e.target.value })}
                />
              </div>
              <Errore chiave="invio" />
            </>
          ),
        };
      }

      default:
        return { titolo: '', corpo: null };
    }
  })();

  const fotoAttiva = foto[step] ?? null;
  const waHref = `https://wa.me/${props.whatsapp}?text=${encodeURIComponent(messaggioWhatsApp(r, lang))}`;

  return (
    <div className={`pv${step === 'grazie' ? ' pv--grazie' : ''}`} ref={radice}>
      {/* La colonna c'è sempre nell'HTML (niente spostamenti all'idratazione)
          e il CSS la nasconde sotto i 64rem; le immagini si montano solo su
          desktop, quindi su mobile non si scarica niente. */}
      <div className="pv-foto" data-theme="dark" data-foto aria-hidden="true">
        {desktop &&
          fotoViste.map((k) => {
            const f = foto[k];
            if (!f) return null;
            return <Immagine key={k} foto={f} attiva={k === step} sizes="min(42vw, 640px)" />;
          })}
        {desktop && !fotoAttiva && <div className="pv-foto-vuota" />}
      </div>

      <div className="pv-modulo">
        {/* Fascia foto, solo sotto i 64rem (CSS). */}
        <div className="pv-fascia" aria-hidden="true">
          {fotoViste.map((k) => {
            const f = foto[k];
            if (!f) return null;
            return <Immagine key={k} foto={f} attiva={k === step} sizes="270px" soloMobile />;
          })}
        </div>

        {step === 'grazie' ? (
          <div className="pv-passo pv-grazie">
            <h2 className="pv-titolo" tabIndex={-1} ref={titolo} id="pv-titolo">
              {t.grazie.titolo(chi)}
            </h2>
            <p className="pv-testo">
              {t.grazie.testo(
                props.oreRisposta,
                r.contactPref,
                r.contactPref === 'email' ? r.email.trim() : r.phone.trim(),
                r.kind === 'festa',
              )}
              {r.contactPref === 'whatsapp' && <> {t.grazie.intanto(r.kind === 'festa')}</>}
            </p>
            {r.contactPref === 'whatsapp' ? (
              <div className="pv-azioni pv-azioni--grazie">
                <a className="btn btn--pieno" href={waHref} target="_blank" rel="noopener">
                  {t.grazie.whatsapp}
                </a>
                <a className="link-filo" href={props.homeHref}>
                  {t.grazie.home}
                </a>
              </div>
            ) : (
              <div className="pv-azioni pv-azioni--grazie">
                <a className="btn" href={props.homeHref}>
                  {t.grazie.home}
                </a>
                <a className="pv-link" href={waHref} target="_blank" rel="noopener">
                  {t.grazie.whatsappDiscreto}
                </a>
              </div>
            )}
          </div>
        ) : (
          <form className="pv-form" onSubmit={onSubmit} noValidate>
            <div className="pv-testa">
              <p className="sr-only">
                {t.passaggioDi(indice + 1, PASSAGGI.length)} — {t.passaggi[indice]}
              </p>
              <div className="pv-progresso" aria-hidden="true">
                <span style={{ transform: `scaleX(${(indice + 1) / PASSAGGI.length})` }} />
              </div>
            </div>

            {ripreso && indice > 0 && <p className="pv-ripreso">{t.ripreso}</p>}

            <div className="pv-passo" key={step}>
              <h2 className="pv-titolo" tabIndex={-1} ref={titolo} id="pv-titolo">
                {domanda.titolo}
              </h2>
              {domanda.nota && <p className="pv-nota">{domanda.nota}</p>}
              <div className="pv-corpo">{domanda.corpo}</div>
            </div>

            {/* Campo trappola: invisibile, fuori dal tab, ignorato dai lettori di schermo. */}
            <div className="pv-trappola" aria-hidden="true">
              <label htmlFor="pv-website">Website</label>
              <input id="pv-website" ref={trappola} type="text" name="website" tabIndex={-1} autoComplete="off" defaultValue="" />
            </div>

            <div className="pv-azioni">
              {indice > 0 && (
                <button type="button" className="btn pv-indietro" onClick={indietro} disabled={inviando}>
                  {t.indietro}
                </button>
              )}
              <button type="submit" className="btn btn--pieno pv-avanti" disabled={inviando}>
                {step === 'riepilogo'
                  ? inviando
                    ? t.invio
                    : t.invia
                  : daRiepilogo
                    ? t.tornaRiepilogo
                    : step === 'contatti'
                      ? t.alRiepilogo
                      : t.avanti}
              </button>
              {(ripreso || indice > 0) && (
                <button type="button" className="pv-link pv-ricomincia" onClick={ricomincia} disabled={inviando}>
                  {t.ricomincia}
                </button>
              )}
            </div>

            <p className="sr-only" aria-live="polite" role="status">
              {annuncio}
            </p>
          </form>
        )}
      </div>
    </div>
  );
}

/** Gif trasparente da 1 px: la src della fascia su desktop, dove non serve nulla. */
const VUOTA = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
const MOBILE = '(max-width: 63.98rem)';

function Immagine({
  foto,
  attiva,
  sizes,
  lazy = false,
  soloMobile = false,
}: {
  foto: Foto;
  attiva: boolean;
  sizes: string;
  lazy?: boolean;
  soloMobile?: boolean;
}): ReactNode {
  return (
    <picture className={`pv-immagine${attiva ? ' attiva' : ''}`}>
      {foto.sources.map((s) => (
        <source key={s.type} type={s.type} srcSet={s.srcset} sizes={sizes} media={soloMobile ? MOBILE : undefined} />
      ))}
      <img
        src={soloMobile ? VUOTA : foto.src}
        width={foto.width}
        height={foto.height}
        alt=""
        loading={lazy ? 'lazy' : 'eager'}
        decoding="async"
        style={foto.objectPosition ? { objectPosition: foto.objectPosition } : undefined}
      />
    </picture>
  );
}

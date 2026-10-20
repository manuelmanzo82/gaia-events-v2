/**
 * Il preventivatore: un solo componente bilingue (isola React).
 *
 * Nove passaggi — Evento · Voi · Quando · Dove · Ospiti · Aiuto · Budget ·
 * Contatti · Lettera — poi il ringraziamento. Il riepilogo è una LETTERA
 * generata dalle risposte (testi.ts), modificabile prima dell'invio.
 * Nessun prezzo calcolato, mai.
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
  componiLettera,
  coppia,
  etichettaBudget,
  etichettaOspiti,
  fasceBudget,
  messaggioWhatsApp,
  nomeMese,
  nomi,
  riepilogo,
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
    case 'dove':
      if (r.zoneMode === 'location' && !r.venue.trim()) e.venue = t.dove.erroreLocation;
      else if (
        !r.zoneMode ||
        (r.zoneMode === 'zone' && r.zones.length === 0) ||
        (r.zoneMode === 'libero' && !r.zoneText.trim())
      ) {
        e.zone = t.dove.errore;
      }
      void haZone;
      break;
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
    case 'lettera':
      if (!r.letter.trim()) e.letter = t.lettera.errore;
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

  const inizio = useRef<number | null>(null);
  const titolo = useRef<HTMLHeadingElement>(null);
  const radice = useRef<HTMLDivElement>(null);
  const trappola = useRef<HTMLInputElement>(null);
  const primoRender = useRef(true);

  // Ripresa da localStorage (dopo l'idratazione, per non disallineare l'HTML).
  useEffect(() => {
    try {
      const grezzo = window.localStorage.getItem(CHIAVE);
      if (grezzo) {
        const s = JSON.parse(grezzo) as Salvato;
        if (s && s.r && s.step && s.step !== 'grazie') {
          setR({ ...RISPOSTE_VUOTE, ...s.r });
          setStep(s.step);
          setFotoViste([s.step]);
          inizio.current = s.inizio ?? null;
          if (s.step !== 'evento' || s.r.kind) setRipreso(true);
        }
      }
    } catch {
      /* storage bloccato o dati rovinati: si parte da capo */
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
      const header = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--altezza-header') || '96', 10);
      if (top < header || top > window.innerHeight * 0.5) {
        const y = window.scrollY + top - header - 16;
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
      if ('guestsRange' in parziale) delete resto.guests;
      if ('monthM' in parziale || 'monthY' in parziale) delete resto.month;
      if ('name' in parziale) delete resto.cname;
      return resto;
    });
  };

  const indice = step === 'grazie' ? PASSAGGI.length : PASSAGGI.indexOf(step);
  const chi = nomi(r, lang) || (lang === 'it' ? 'Voi' : 'You');

  const avanti = (): void => {
    const e = valida(step, r, lang, zone.length > 0);
    if (Object.keys(e).length > 0) {
      setErrori(e);
      setAnnuncio(Object.values(e)[0]);
      return;
    }
    setErrori({});
    setAnnuncio('');
    const prossimo = PASSAGGI[indice + 1];
    if (prossimo === 'lettera' && !r.letterEdited) {
      setR((prima) => ({ ...prima, letter: componiLettera(prima, lang, zone, servizi) }));
    }
    if (prossimo) setStep(prossimo);
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
    setR(RISPOSTE_VUOTE);
    setErrori({});
    setAnnuncio('');
    setRipreso(false);
    setStep('evento');
  };

  const invia = async (): Promise<void> => {
    const e = valida('lettera', r, lang, zone.length > 0);
    if (Object.keys(e).length > 0) {
      setErrori(e);
      setAnnuncio(Object.values(e)[0]);
      return;
    }
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
          zoneMode: r.zoneMode,
          zones: r.zoneMode === 'zone' ? r.zones.map((s) => zone.find((z) => z.slug === s)?.nome ?? s) : [],
          zoneText: r.zoneMode === 'libero' ? r.zoneText.trim() : '',
          venue: r.zoneMode === 'location' ? r.venue.trim() : '',
          abroad: r.abroad,
          guestsRange: r.guests ? '' : r.guestsRange,
          guests: r.guests ? Number(r.guests) : null,
          services: r.services,
          budget: r.budget,
          source: r.source,
          message: r.message.trim(),
          letter: r.letter.trim(),
          privacy: r.privacy,
          elapsed: inizio.current ? Math.round((Date.now() - inizio.current) / 1000) : 0,
          website: trappola.current?.value ?? '',
          summary: riepilogo(r, zone, servizi),
        }),
      });
      const dati = (await risposta.json().catch(() => null)) as { ok?: boolean; error?: string } | null;
      if (!risposta.ok || !dati?.ok) {
        throw new Error(dati?.error || t.lettera.erroreInvio);
      }
      try {
        window.localStorage.removeItem(CHIAVE);
      } catch {
        /* niente */
      }
      setAnnuncio('');
      setStep('grazie');
    } catch (errore) {
      const testo = errore instanceof Error && errore.message ? errore.message : t.lettera.erroreInvio;
      setErrori({ invio: testo });
      setAnnuncio(testo);
    } finally {
      setInviando(false);
    }
  };

  const onSubmit = (e: { preventDefault(): void }): void => {
    e.preventDefault();
    if (inviando) return;
    if (step === 'lettera') void invia();
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
              {zone.length > 0 ? (
                <div className="pv-pillole" role="group" aria-labelledby="pv-titolo" {...campo('zone')}>
                  {zone.map((z) => (
                    <label key={z.slug} className="pv-pillola">
                      <input
                        type="checkbox"
                        checked={r.zoneMode === 'zone' && r.zones.includes(z.slug)}
                        onChange={(e) => {
                          const prima = r.zoneMode === 'zone' ? r.zones : [];
                          const zones = e.target.checked ? [...prima, z.slug] : prima.filter((s) => s !== z.slug);
                          aggiorna({ zones, zoneMode: zones.length > 0 ? 'zone' : '' });
                        }}
                      />
                      <span>{z.nome}</span>
                    </label>
                  ))}
                </div>
              ) : (
                <div className="pv-campo">
                  <label htmlFor="pv-zonetext">{t.dove.libero}</label>
                  <input
                    id="pv-zonetext"
                    type="text"
                    value={r.zoneMode === 'libero' ? r.zoneText : ''}
                    onChange={(e) => aggiorna({ zoneText: e.target.value, zoneMode: e.target.value.trim() ? 'libero' : '' })}
                    {...campo('zone')}
                  />
                </div>
              )}
              <div className="pv-pillole pv-pillole--altre">
                <label className="pv-pillola">
                  <input
                    type="checkbox"
                    checked={r.zoneMode === 'consigliateci'}
                    onChange={(e) => aggiorna({ zoneMode: e.target.checked ? 'consigliateci' : '', zones: [] })}
                  />
                  <span>{t.dove.consigliateci}</span>
                </label>
                <label className="pv-pillola">
                  <input
                    type="checkbox"
                    checked={r.zoneMode === 'location'}
                    onChange={(e) => aggiorna({ zoneMode: e.target.checked ? 'location' : '', zones: [] })}
                  />
                  <span>{t.dove.location}</span>
                </label>
              </div>
              <Errore chiave="zone" />
              {r.zoneMode === 'location' && (
                <div className="pv-campo">
                  <label htmlFor="pv-venue">{t.dove.nomeLocation}</label>
                  <input id="pv-venue" type="text" value={r.venue} onChange={(e) => aggiorna({ venue: e.target.value })} {...campo('venue')} />
                  <Errore chiave="venue" />
                </div>
              )}
              <label className="pv-interruttore">
                <input type="checkbox" role="switch" checked={r.abroad} onChange={(e) => aggiorna({ abroad: e.target.checked })} />
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
              <div className="pv-campo pv-campo--corto">
                <label htmlFor="pv-guests">{t.ospiti.oppure}</label>
                <input
                  id="pv-guests"
                  type="number"
                  inputMode="numeric"
                  min={1}
                  max={5000}
                  value={r.guests}
                  onChange={(e) => aggiorna({ guests: e.target.value, guestsRange: e.target.value ? '' : r.guestsRange })}
                  {...campo('guests')}
                />
              </div>
              <Errore chiave="guests" />
            </>
          ),
        };

      case 'aiuto':
        return {
          titolo: t.aiuto.titolo(r.kind, chi),
          nota: t.aiuto.aiuto,
          corpo: (
            <div className="pv-schede" role="group" aria-labelledby="pv-titolo" {...campo('services')}>
              {servizi.map((s) => (
                <label key={s.slug} className="pv-scheda">
                  <input
                    type="checkbox"
                    checked={r.services.includes(s.slug)}
                    onChange={(e) => {
                      const prima = r.services.filter((x) => x !== 'da_definire');
                      aggiorna({ services: e.target.checked ? [...prima, s.slug] : prima.filter((x) => x !== s.slug) });
                    }}
                  />
                  <span className="pv-scheda-titolo">{s.titolo}</span>
                  {s.sottotitolo && <span className="pv-scheda-nota">{s.sottotitolo}</span>}
                </label>
              ))}
              <label className="pv-scheda pv-scheda--lieve">
                <input
                  type="checkbox"
                  checked={r.services.includes('da_definire')}
                  onChange={(e) => aggiorna({ services: e.target.checked ? ['da_definire'] : [] })}
                />
                <span className="pv-scheda-titolo">{t.aiuto.nonSo}</span>
              </label>
              <Errore chiave="services" />
            </div>
          ),
        };

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
              <div className="pv-campo">
                <label htmlFor="pv-message">
                  {t.contatti.messaggio} <span className="pv-facoltativo">({t.facoltativo})</span>
                </label>
                <textarea id="pv-message" rows={4} value={r.message} onChange={(e) => aggiorna({ message: e.target.value })} />
              </div>
              <label className="pv-consenso">
                <input type="checkbox" checked={r.privacy} onChange={(e) => aggiorna({ privacy: e.target.checked })} {...campo('privacy')} />
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

      case 'lettera':
        return {
          titolo: t.lettera.titolo,
          nota: t.lettera.nota,
          corpo: (
            <div className="pv-campo">
              <label htmlFor="pv-letter" className="sr-only">
                {t.lettera.etichetta}
              </label>
              <textarea
                id="pv-letter"
                className="pv-lettera"
                rows={14}
                value={r.letter}
                onChange={(e) => aggiorna({ letter: e.target.value, letterEdited: true })}
                {...campo('letter')}
              />
              <Errore chiave="letter" />
              {r.letterEdited && (
                <button
                  type="button"
                  className="pv-link"
                  onClick={() => setR((prima) => ({ ...prima, letter: componiLettera(prima, lang, zone, servizi), letterEdited: false }))}
                >
                  {t.lettera.riscrivi}
                </button>
              )}
              <Errore chiave="invio" />
            </div>
          ),
        };

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
        {step === 'grazie' ? (
          <div className="pv-passo pv-grazie">
            {foto.grazie && (
              <div className="pv-grazie-foto">
                <Immagine foto={foto.grazie} attiva sizes="100vw" lazy />
              </div>
            )}
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
              <div className="pv-tappe-riga">
                <ol className="pv-tappe" aria-hidden="true">
                  {t.passaggi.map((nome, i) => (
                    <li key={nome} className={i === indice ? 'corrente' : i < indice ? 'fatta' : ''}>
                      {nome}
                    </li>
                  ))}
                </ol>
                <p className="pv-tappa-mobile" aria-hidden="true">
                  {indice + 1} / {PASSAGGI.length} · {t.passaggi[indice]}
                </p>
                {(ripreso || indice > 0) && (
                  <button type="button" className="pv-link pv-ricomincia" onClick={ricomincia}>
                    {t.ricomincia}
                  </button>
                )}
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
                {step === 'lettera' ? (inviando ? t.invio : t.invia) : step === 'contatti' ? t.allaLettera : t.avanti}
              </button>
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

function Immagine({ foto, attiva, sizes, lazy = false }: { foto: Foto; attiva: boolean; sizes: string; lazy?: boolean }): ReactNode {
  return (
    <picture className={`pv-immagine${attiva ? ' attiva' : ''}`}>
      {foto.sources.map((s) => (
        <source key={s.type} type={s.type} srcSet={s.srcset} sizes={sizes} />
      ))}
      <img
        src={foto.src}
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

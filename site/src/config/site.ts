/**
 * Fonte unica di verità per URL e dati di contatto.
 *
 * REGOLA: nessun URL scritto a mano altrove nel progetto. Nella v1 metà del
 * sito puntava a gaia-events-astro.vercel.app e metà a gaiaevents.it.
 *
 * In staging basta esportare SITE_URL prima del build:
 *   SITE_URL=https://gaia.incognito.work npm run build
 */

export const LANGS = ['it', 'en'] as const;
export type Lang = (typeof LANGS)[number];
export const DEFAULT_LANG: Lang = 'it';

const DEFAULT_SITE_URL = 'https://gaiaevents.it';

/** URL del sito, senza slash finale. */
export const SITE_URL: string = (
  import.meta.env?.SITE_URL ??
  process.env.SITE_URL ??
  DEFAULT_SITE_URL
).replace(/\/+$/, '');

/** True quando il build è per lo staging: attiva il noindex. */
export const IS_STAGING: boolean = SITE_URL !== DEFAULT_SITE_URL;

export const BUSINESS = {
  name: 'Gaia Events',
  legalName: 'Gaia Crognale',
  /** Il business è a Roma, non in Toscana (errore della v1). */
  city: 'Roma',
  region: 'Lazio',
  country: 'IT',
  email: 'gaiacrognale@gmail.com',
  /** Formato E.164, per i link tel: e wa.me */
  phone: '+393273855362',
  whatsapp: '393273855362',
  instagram: 'gaiacrognale_events',
  instagramUrl: 'https://www.instagram.com/gaiacrognale_events/',
} as const;

/** Percorso pubblico delle immagini caricate dal back office. */
export const UPLOADS_BASE = '/uploads';

/** Endpoint PHP del back office, stesso dominio: il CSP resta form-action 'self'. */
export const ENDPOINTS = {
  quote: '/admin/api/quote.php',
  contact: '/admin/api/contact.php',
} as const;

/** OG image di fallback. Nella v1 era referenziata ma il file non esisteva. */
export const DEFAULT_OG_IMAGE = '/og-default.jpg';

import { DEFAULT_LANG, LANGS, type Lang } from '../config/site';
import type { RouteKey } from '../i18n/routes';
import {
  availableLangs,
  getDestinationWedding,
  getEvents,
  getFaqs,
  getPage,
  getPosts,
  getReviews,
  getServices,
  isPublishable,
} from './content';
import type { Event, Faq, Post, Review, Service } from './types';

/**
 * In quali lingue esiste davvero una pagina.
 *
 * Fonte unica per sitemap, hreflang, menu e selettore di lingua: se questa
 * logica stesse in più posti, prima o poi una pagina finirebbe in sitemap
 * senza essere generata, o nel menu puntando a un 404.
 *
 * La regola è una sola, applicata a due livelli:
 *
 *  1. una PAGINA esiste in una lingua se i suoi campi essenziali sono tradotti;
 *  2. un ELENCO (servizi, portfolio, recensioni, blog, FAQ) esiste in una
 *     lingua solo se ha almeno un elemento tradotto in quella lingua.
 *
 * Il secondo punto evita le pagine indice vuote: finché nessun articolo ha la
 * versione inglese, /en/blog/ non viene generata. Si riaccende da sola appena
 * arriva la prima traduzione, senza toccare il codice.
 */

/** Campi che devono essere tradotti perché la pagina abbia senso. */
const PAGE_REQUIREMENTS: Partial<Record<RouteKey, string[]>> = {
  about: ['title', 'body'],
  privacy: ['title', 'body'],
  cookie: ['title', 'body'],
  services: ['title'],
  portfolio: ['title'],
  reviews: ['title'],
  blog: ['title'],
  faq: ['title'],
  quote: ['title'],
  contact: ['title'],
};

/**
 * Campi che rendono pubblicabile un singolo contenuto.
 *
 * Dichiarati qui una volta e usati ovunque — elenchi, pagine di dettaglio,
 * sitemap, home. Quando indice e dettaglio usavano criteri diversi, l'indice
 * inglese finiva per linkare una scheda che il build non aveva generato.
 */
export const EVENT_FIELDS: Array<keyof Event> = ['title', 'excerpt', 'description'];
export const POST_FIELDS: Array<keyof Post> = ['title', 'slug', 'body'];
export const SERVICE_FIELDS: Array<keyof Service> = ['title', 'description'];
export const REVIEW_FIELDS: Array<keyof Review> = ['text'];
export const FAQ_FIELDS: Array<keyof Faq> = ['question', 'answer'];

/** Pagine che elencano altri contenuti: senza elementi tradotti non esistono. */
const INDEX_KEYS = ['services', 'portfolio', 'reviews', 'blog', 'faq'] as const;

type IndexKey = (typeof INDEX_KEYS)[number];

function isIndexKey(key: RouteKey): key is IndexKey {
  return (INDEX_KEYS as readonly string[]).includes(key);
}

/** Almeno un elemento dell'elenco è pubblicabile in questa lingua? */
function hasItems(key: IndexKey, lang: Lang): boolean {
  switch (key) {
    case 'services':
      return getServices().some((item) => isPublishable(item, lang, SERVICE_FIELDS));
    case 'portfolio':
      return getEvents().some((item) => isPublishable(item, lang, EVENT_FIELDS));
    case 'reviews':
      return getReviews().some((item) => isPublishable(item, lang, REVIEW_FIELDS));
    case 'blog':
      return getPosts().some((item) => isPublishable(item, lang, POST_FIELDS));
    case 'faq':
      return getFaqs().some((item) => isPublishable(item, lang, FAQ_FIELDS));
  }
}

/**
 * Lingue in cui la pagina viene generata.
 *
 * L'italiano c'è sempre: è la lingua sorgente e le sue rotte sono file
 * statici, quindi dichiarare il contrario renderebbe sitemap e hreflang
 * incoerenti con ciò che il build produce davvero.
 */
export function langsFor(key: RouteKey): Lang[] {
  if (key === 'home') return [...LANGS];

  if (key === 'destinationWedding') {
    return withDefault(availableLangs(getDestinationWedding(), ['title', 'intro']));
  }

  const page = getPage(key);
  const required = PAGE_REQUIREMENTS[key];

  let langs: Lang[] =
    page && required
      ? availableLangs(page, required as Array<keyof typeof page>)
      : [...LANGS];

  if (isIndexKey(key)) {
    langs = langs.filter((lang) => hasItems(key, lang));
  }

  return withDefault(langs);
}

function withDefault(langs: Lang[]): Lang[] {
  return langs.includes(DEFAULT_LANG) ? langs : [DEFAULT_LANG, ...langs];
}

/** Scorciatoia leggibile per le rotte dinamiche. */
export function existsIn(key: RouteKey, lang: Lang): boolean {
  return langsFor(key).includes(lang);
}

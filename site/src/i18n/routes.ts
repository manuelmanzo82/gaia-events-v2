import { LANGS, DEFAULT_LANG, SITE_URL, type Lang } from '../config/site';

/**
 * Mappa degli slug IT ↔ EN. Unica fonte: non scrivere mai un percorso a mano.
 * Aggiungere una pagina = aggiungere una voce qui.
 */
export const ROUTES = {
  home: { it: '', en: '' },
  about: { it: 'chi-sono', en: 'about' },
  services: { it: 'servizi', en: 'services' },
  reviews: { it: 'dicono-di-me', en: 'kind-words' },
  // Giro 11: Blog → Magazine e Storie → I miei eventi (301 dai vecchi
  // percorsi nel .htaccess). Le chiavi restano: sono nomi interni, non URL.
  blog: { it: 'magazine', en: 'magazine' },
  faq: { it: 'faq', en: 'faq' },
  quote: { it: 'preventivo', en: 'wedding-estimate' },
  contact: { it: 'contatti', en: 'contact' },
  destinationWedding: { it: 'destination-wedding', en: 'destination-wedding' },
  privacy: { it: 'privacy', en: 'privacy' },
  cookie: { it: 'cookie', en: 'cookie' },
  places: { it: 'luoghi', en: 'destinations' },
  stories: { it: 'eventi', en: 'events' },
  partners: { it: 'partner', en: 'partners' },
} as const satisfies Record<string, Record<Lang, string>>;

export type RouteKey = keyof typeof ROUTES;

/** Chiavi delle pagine che hanno un dettaglio con slug (`/magazine/:slug`). */
export const COLLECTION_ROUTES = [
  'blog',
  'stories',
  // 'places' ha due livelli: lo slug passato a path() è già composto,
  // es. 'lazio/roma' — vedi slugLuogo() in lib/content.ts.
  'places',
] as const satisfies readonly RouteKey[];

/**
 * Percorso assoluto interno di una pagina, sempre con slash finale.
 *   path('services', 'it')            → '/servizi/'
 *   path('services', 'en')            → '/en/services/'
 *   path('stories', 'it', 'villa')    → '/eventi/villa/'
 *   path('home', 'it')                → '/'
 *
 * Lo slash finale non è un vezzo: il build genera directory con index.html
 * e Apache serve /servizi/ direttamente. Senza slash farebbe un 301 verso
 * la versione con slash, cioè un redirect sull'URL canonico.
 */
export function path(key: RouteKey, lang: Lang, slug?: string): string {
  const segments: string[] = [];
  if (lang !== DEFAULT_LANG) segments.push(lang);

  const base = ROUTES[key][lang];
  if (base) segments.push(base);
  if (slug) segments.push(slug);

  return '/' + (segments.length > 0 ? segments.join('/') + '/' : '');
}

/** URL assoluto, sempre costruito su SITE_URL. */
export function url(key: RouteKey, lang: Lang, slug?: string): string {
  return SITE_URL + path(key, lang, slug);
}

/** URL assoluto da un percorso già noto (es. `Astro.url.pathname`). */
export function absolute(pathname: string): string {
  return SITE_URL + (pathname.startsWith('/') ? pathname : '/' + pathname);
}

/** Lingua di una pagina a partire dal suo percorso. */
export function langFromPath(pathname: string): Lang {
  const first = pathname.replace(/^\/+/, '').split('/')[0];
  return (LANGS as readonly string[]).includes(first) ? (first as Lang) : DEFAULT_LANG;
}

/** La chiave di rotta corrispondente a un percorso, se esiste. */
export function keyFromPath(pathname: string): RouteKey | null {
  const lang = langFromPath(pathname);
  let rest = pathname.replace(/^\/+|\/+$/g, '');
  if (lang !== DEFAULT_LANG) rest = rest.slice(lang.length).replace(/^\/+/, '');
  const slug = rest.split('/')[0] ?? '';

  if (!slug) return 'home';
  const entry = (Object.keys(ROUTES) as RouteKey[]).find((k) => ROUTES[k][lang] === slug);
  return entry ?? null;
}

export type Alternate = { lang: Lang; href: string };

/**
 * Alternate per gli hreflang. `availableLangs` serve perché una pagina EN
 * esiste solo se la traduzione c'è: se l'EN è vuoto non va dichiarato.
 */
export function alternates(
  key: RouteKey,
  slug?: string,
  availableLangs: readonly Lang[] = LANGS,
): Alternate[] {
  return availableLangs.map((lang) => ({ lang, href: url(key, lang, slug) }));
}

/** Percorso equivalente nell'altra lingua, per il selettore di lingua. */
export function switchLang(pathname: string, to: Lang): string | null {
  const key = keyFromPath(pathname);
  if (!key) return null;

  const lang = langFromPath(pathname);
  let rest = pathname.replace(/^\/+|\/+$/g, '');
  if (lang !== DEFAULT_LANG) rest = rest.slice(lang.length).replace(/^\/+/, '');
  const parts = rest.split('/').filter(Boolean);
  const slug = parts.length > 1 ? parts.slice(1).join('/') : undefined;

  return path(key, to, slug);
}

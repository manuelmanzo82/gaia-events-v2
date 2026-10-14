import { DEFAULT_LANG, type Lang } from '../config/site';
import type {
  DestinationWedding,
  Event,
  Faq,
  InstagramItem,
  Localized,
  Page,
  Post,
  Review,
  Service,
  Settings,
} from './types';

/**
 * Carica i contenuti dai JSON in src/content/.
 *
 * I file reali (settings.json, …) li scrive la GitHub Action leggendo
 * admin/api/export.php e sono gitignorati. In repo stanno solo i
 * *.example.json, usati come fallback: così il build locale funziona
 * sempre, anche su un clone appena fatto.
 */
const modules = import.meta.glob<unknown>('../content/*.json', {
  eager: true,
  import: 'default',
});

const store = new Map<string, unknown>();

for (const [filePath, data] of Object.entries(modules)) {
  const file = filePath.split('/').pop() ?? '';
  const isExample = file.endsWith('.example.json');
  const name = file.replace('.example.json', '').replace('.json', '');

  // Il file reale vince sull'esempio, qualunque sia l'ordine del glob.
  if (isExample && store.has(name)) continue;
  if (!isExample || !store.has(name)) store.set(name, data);
}

function load<T>(name: string, fallback: T): T {
  const data = store.get(name);
  if (data === undefined) {
    console.warn(
      `[content] ${name}.json e ${name}.example.json mancanti: uso un valore vuoto.`,
    );
    return fallback;
  }
  return data as T;
}

/* ------------------------------------------------------------------ *
 * Campi bilingui
 * ------------------------------------------------------------------ */

/** Valore nella lingua richiesta, oppure null se la traduzione manca. */
export function pick<T>(field: Localized<T> | undefined | null, lang: Lang): T | null {
  if (!field) return null;
  const value = field[lang];
  if (value === undefined || value === null) return null;
  if (typeof value === 'string' && value.trim() === '') return null;
  if (Array.isArray(value) && value.length === 0) return null;
  return value;
}

/** Come `pick`, ma ripiega sull'italiano. Usalo solo dove un buco romperebbe la pagina. */
export function pickOrDefault<T>(field: Localized<T> | undefined | null, lang: Lang): T | null {
  return pick(field, lang) ?? pick(field, DEFAULT_LANG);
}

/** Come `pick`, ma restituisce stringa vuota invece di null. */
export function text(field: Localized | undefined | null, lang: Lang): string {
  return pick(field, lang) ?? '';
}

/**
 * True se l'entità è tradotta nella lingua richiesta.
 * `required` elenca i campi che devono esistere perché la pagina abbia senso.
 */
export function hasTranslation<T extends Record<string, unknown>>(
  entity: T,
  lang: Lang,
  required: Array<keyof T>,
): boolean {
  if (lang === DEFAULT_LANG) return true;
  return required.every((key) => pick(entity[key] as Localized, lang) !== null);
}

/* ------------------------------------------------------------------ *
 * Accessori
 * ------------------------------------------------------------------ */

const byOrder = <T extends { order: number }>(a: T, b: T) => a.order - b.order;
const byDateDesc = <T extends { date: string }>(a: T, b: T) => b.date.localeCompare(a.date);
const isActive = <T extends { active: boolean }>(item: T) => item.active;

export function getSettings(): Settings {
  return load<Settings>('settings', {
    siteName: 'Gaia Events',
    tagline: {},
    hero: { eyebrow: {}, title: {}, subtitle: {}, image: null, ctaLabel: {} },
    sections: {},
    about: { title: {}, text: {}, image: null },
    contact: { title: {}, text: {}, address: {} },
  });
}

export function getPages(): Page[] {
  return load<Page[]>('pages', []);
}

export function getPage(key: string): Page | null {
  return getPages().find((page) => page.key === key) ?? null;
}

export function getServices(): Service[] {
  return load<Service[]>('services', []).filter(isActive).sort(byOrder);
}

export function getServiceBySlug(slug: string): Service | null {
  return getServices().find((service) => service.slug === slug) ?? null;
}

export function getEvents(): Event[] {
  return load<Event[]>('events', []).filter(isActive).sort(byOrder);
}

export function getFeaturedEvents(limit?: number): Event[] {
  const featured = getEvents().filter((event) => event.featured);
  return limit ? featured.slice(0, limit) : featured;
}

export function getEventBySlug(slug: string): Event | null {
  return getEvents().find((event) => event.slug === slug) ?? null;
}

export function getReviews(): Review[] {
  return load<Review[]>('reviews', []).filter(isActive).sort(byOrder);
}

export function getFeaturedReviews(limit?: number): Review[] {
  const featured = getReviews().filter((review) => review.featured);
  return limit ? featured.slice(0, limit) : featured;
}

export function getPosts(): Post[] {
  return load<Post[]>('posts', []).filter(isActive).sort(byDateDesc);
}

/** Lo slug degli articoli è bilingue: /blog/titolo-it ↔ /en/blog/title-en */
export function getPostBySlug(slug: string, lang: Lang): Post | null {
  return getPosts().find((post) => pick(post.slug, lang) === slug) ?? null;
}

export function getFaqs(): Faq[] {
  return load<Faq[]>('faq', []).filter(isActive).sort(byOrder);
}

export function getDestinationWedding(): DestinationWedding {
  return load<DestinationWedding>('destination-wedding', {
    title: {},
    intro: {},
    hero: null,
    blocks: [],
  });
}

export function getInstagramItems(limit?: number): InstagramItem[] {
  const items = load<InstagramItem[]>('instagram', []);
  return limit ? items.slice(0, limit) : items;
}

export type { Lang };
export * from './types';

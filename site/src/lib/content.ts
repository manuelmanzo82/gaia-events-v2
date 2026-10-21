import { DEFAULT_LANG, LANGS, type Lang } from '../config/site';
import type {
  DestinationWedding,
  Event,
  Faq,
  Highlight,
  HighlightSection,
  InstagramFeed,
  Localized,
  LocationVenue,
  Luogo,
  Page,
  PageHero,
  Partner,
  Post,
  Review,
  Service,
  Settings,
  Story,
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
export function hasTranslation<T extends object>(
  entity: T,
  lang: Lang,
  required: Array<keyof T>,
): boolean {
  if (lang === DEFAULT_LANG) return true;
  return isPublishable(entity, lang, required);
}

/**
 * True se tutti i campi richiesti esistono nella lingua data.
 * Usala in getStaticPaths: a differenza di `hasTranslation` non dà per
 * scontato che l'italiano sia completo.
 */
export function isPublishable<T extends object>(
  entity: T,
  lang: Lang,
  required: Array<keyof T>,
): boolean {
  const fields = entity as Record<keyof T, Localized | undefined>;
  return required.every((key) => pick(fields[key], lang) !== null);
}

/**
 * Lingue in cui l'entità è pubblicabile. Passala a buildMeta e al
 * selettore di lingua: una pagina EN non tradotta non va né negli
 * hreflang né in sitemap.
 */
export function availableLangs<T extends object>(
  entity: T,
  required: Array<keyof T>,
): Lang[] {
  return LANGS.filter((lang) => isPublishable(entity, lang, required));
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
    home: { manifesto: {}, manifestoFirma: {} },
    legal: { piva: {} },
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

/** Lo slug degli articoli è bilingue: /magazine/titolo-it ↔ /en/magazine/title-en */
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

/**
 * Profilo e post Instagram per la home. L'export manda già solo i post
 * visibili, ordinati e tagliati al numero scelto nel back office; qui si
 * scartano quelli senza foto. Formato vecchio (array) = nessun post.
 */
export function getInstagram(): InstagramFeed {
  const vuoto: InstagramFeed = { handle: '', profileUrl: '', posts: [] };
  const dati = load<InstagramFeed | unknown[]>('instagram', vuoto);
  if (Array.isArray(dati)) return vuoto;
  return { ...dati, posts: (dati.posts ?? []).filter((post) => post.image !== null) };
}

/* ------------------------------------------------------------------ *
 * Fase 2: luoghi, location, highlights, partner, storie, hero
 * ------------------------------------------------------------------ */

/** Le regioni pubblicate, con le zone annidate (anch'esse filtrate). */
export function getLuoghi(): Luogo[] {
  return load<Luogo[]>('luoghi', [])
    .filter(isActive)
    .sort(byOrder)
    .map((regione) => ({
      ...regione,
      zone: (regione.zone ?? []).filter(isActive).sort(byOrder),
    }));
}

/** Tutti i luoghi pubblicati, piatti: regioni e zone insieme. */
export function getLuoghiFlat(): Luogo[] {
  return getLuoghi().flatMap((regione) => [regione, ...(regione.zone ?? [])]);
}

export function getLuogoById(id: number | null | undefined): Luogo | null {
  if (!id) return null;
  return getLuoghiFlat().find((luogo) => luogo.id === id) ?? null;
}

/** La regione a cui appartiene una zona (null per le regioni stesse). */
export function getRegioneDi(luogo: Luogo): Luogo | null {
  if (luogo.tipo === 'regione') return null;
  return getLuoghi().find((regione) => (regione.zone ?? []).some((z) => z.id === luogo.id)) ?? null;
}

/**
 * Slug composto per path('places', …): 'lazio' per una regione,
 * 'lazio/roma' per una zona. Una zona senza regione non è linkabile.
 */
export function slugLuogo(luogo: Luogo, lang: Lang): string | null {
  const proprio = pick(luogo.slug, lang);
  if (!proprio) return null;

  if (luogo.tipo === 'regione') return proprio;

  const regione = getRegioneDi(luogo);
  const slugRegione = regione ? pick(regione.slug, lang) : null;
  return slugRegione ? `${slugRegione}/${proprio}` : null;
}

export function getLocations(luogoId?: number): LocationVenue[] {
  const all = load<LocationVenue[]>('location', []).filter(isActive).sort(byOrder);
  return luogoId === undefined ? all : all.filter((location) => location.luogoId === luogoId);
}

/** Gli highlights di una sezione, nell'ordine deciso per quella sezione. */
export function getHighlights(section: HighlightSection, luogoId?: number): Highlight[] {
  return load<Highlight[]>('highlights', [])
    .filter(isActive)
    .map((highlight) => ({
      highlight,
      entry: highlight.sections.find((s) => s.section === section),
    }))
    .filter(({ highlight, entry }) => {
      if (!entry) return false;
      // Nella sezione 'luogo' contano solo le foto del luogo richiesto.
      if (section === 'luogo') return luogoId !== undefined && highlight.luogoId === luogoId;
      return true;
    })
    .sort((a, b) => (a.entry?.order ?? 0) - (b.entry?.order ?? 0))
    .map(({ highlight }) => highlight);
}

export function getPartners(): Partner[] {
  return load<Partner[]>('partner', []).filter(isActive).sort(byOrder);
}

export function getPartnerById(id: number | null | undefined): Partner | null {
  if (!id) return null;
  return getPartners().find((partner) => partner.id === id) ?? null;
}

export function getStories(): Story[] {
  return load<Story[]>('storie', []).filter(isActive).sort(byOrder);
}

/** Lo slug delle storie è bilingue, come i post del blog. */
export function getStoryBySlug(slug: string, lang: Lang): Story | null {
  return getStories().find((story) => pick(story.slug, lang) === slug) ?? null;
}

/** L'hero configurato per una pagina, se c'è. */
export function getHero(pageKey: string): PageHero | null {
  const heroes = load<Record<string, PageHero>>('hero', {});
  return heroes[pageKey] ?? null;
}

export type { Lang };
export * from './types';

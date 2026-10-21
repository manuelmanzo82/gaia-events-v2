import { BUSINESS, SITE_URL, type Lang } from '../config/site';
import { HTML_LANG } from '../i18n';
import { path, url } from '../i18n/routes';
import { text } from './content';
import { imageAbsoluteUrl } from './images';
import type {
  Faq,
  Image,
  LocationVenue,
  Luogo,
  Partner,
  Post,
  Service,
  Story,
} from './types';

/**
 * Helper JSON-LD. Ogni funzione restituisce un oggetto schema.org pronto
 * da passare a <JsonLd>. Gli URL passano sempre da SITE_URL.
 */

type Thing = Record<string, unknown>;

const ORG_ID = `${SITE_URL}/#localbusiness`;
const SITE_ID = `${SITE_URL}/#website`;

/** Riferimento all'attività, da riusare come `publisher` o `provider`. */
export const businessRef = { '@id': ORG_ID };

export function localBusiness(opts: {
  lang: Lang;
  description?: string;
  image?: string | null;
}): Thing {
  return {
    '@context': 'https://schema.org',
    '@type': 'LocalBusiness',
    '@id': ORG_ID,
    additionalType: 'https://schema.org/ProfessionalService',
    name: BUSINESS.name,
    legalName: BUSINESS.legalName,
    url: SITE_URL + path('home', opts.lang),
    email: BUSINESS.email,
    telephone: BUSINESS.phone,
    description: opts.description,
    image: opts.image ?? undefined,
    address: {
      '@type': 'PostalAddress',
      streetAddress: BUSINESS.street,
      postalCode: BUSINESS.postalCode,
      addressLocality: BUSINESS.city,
      addressRegion: BUSINESS.region,
      addressCountry: BUSINESS.country,
    },
    areaServed: [
      { '@type': 'City', name: BUSINESS.city },
      { '@type': 'Country', name: 'Italia' },
    ],
    sameAs: [BUSINESS.instagramUrl, BUSINESS.facebookUrl],
    knowsLanguage: ['it', 'en'],
  };
}

export function webSite(lang: Lang, name: string, description?: string): Thing {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    '@id': SITE_ID,
    url: SITE_URL + path('home', lang),
    name,
    description,
    inLanguage: HTML_LANG[lang],
    publisher: businessRef,
  };
}

export function service(item: Service, lang: Lang): Thing {
  return {
    '@context': 'https://schema.org',
    '@type': 'Service',
    name: text(item.title, lang),
    description: text(item.description, lang),
    serviceType: text(item.subtitle, lang) || undefined,
    provider: businessRef,
    areaServed: { '@type': 'Country', name: 'Italia' },
    url: url('services', lang) + `#${item.slug}`,
    inLanguage: HTML_LANG[lang],
  };
}

export function article(item: Post, lang: Lang, slug: string): Thing {
  const pageUrl = url('blog', lang, slug);
  return {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: text(item.title, lang),
    description: text(item.excerpt, lang),
    datePublished: item.date,
    dateModified: item.date,
    author: { '@type': 'Person', name: BUSINESS.legalName },
    publisher: businessRef,
    image: imageAbsoluteUrl(item.cover, SITE_URL) ?? undefined,
    articleSection: text(item.category, lang) || undefined,
    mainEntityOfPage: { '@type': 'WebPage', '@id': pageUrl },
    url: pageUrl,
    inLanguage: HTML_LANG[lang],
  };
}

export function faqPage(items: Faq[], lang: Lang): Thing | null {
  const entities = items
    .map((item) => ({
      question: text(item.question, lang),
      answer: text(item.answer, lang),
    }))
    .filter((item) => item.question && item.answer);

  if (entities.length === 0) return null;

  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    inLanguage: HTML_LANG[lang],
    mainEntity: entities.map((item) => ({
      '@type': 'Question',
      name: item.question,
      acceptedAnswer: { '@type': 'Answer', text: item.answer },
    })),
  };
}

/*
 * Niente Review / AggregateRating sul LocalBusiness (giro 11): Google li
 * considera autoreferenziali e la pagina Dicono di me li tiene come testo.
 */

export interface Crumb {
  name: string;
  href: string;
}

export function breadcrumbs(crumbs: Crumb[]): Thing | null {
  if (crumbs.length < 2) return null;
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: crumbs.map((crumb, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: crumb.name,
      item: crumb.href.startsWith('http') ? crumb.href : SITE_URL + crumb.href,
    })),
  };
}

export type { Image };

/* ------------------------------------------------------------------ *
 * Fase 2: luoghi (Place + ItemList di location), storie (Article),
 * partner (Organization)
 * ------------------------------------------------------------------ */

/** Tipo schema.org di una location, dal suo tipo nel back office. */
const LOCATION_SCHEMA_TYPE: Record<LocationVenue['tipo'], string> = {
  villa: 'Place',
  castello: 'LandmarksOrHistoricalBuildings',
  chiesa: 'Church',
  terrazza: 'Place',
  tenuta: 'Place',
  hotel: 'Hotel',
  ristorante: 'Restaurant',
  altro: 'Place',
};

/** La pagina di un luogo come Place. `pageUrl` arriva da url('places', …). */
export function luogoPlace(luogo: Luogo, lang: Lang, pageUrl: string): Thing {
  return {
    '@context': 'https://schema.org',
    '@type': 'Place',
    name: text(luogo.nome, lang),
    description: text(luogo.intro, lang) || undefined,
    url: pageUrl,
    photo: imageAbsoluteUrl(luogo.cover, SITE_URL) ?? undefined,
    address: { '@type': 'PostalAddress', addressCountry: 'IT' },
    inLanguage: HTML_LANG[lang],
  };
}

/**
 * Le location di un luogo come elenco leggibile dalle macchine:
 * ItemList di Place/Church/Hotel/Restaurant a seconda del tipo.
 */
export function locationItemList(
  venues: LocationVenue[],
  lang: Lang,
  listName: string,
): Thing | null {
  if (venues.length === 0) return null;

  return {
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    name: listName,
    numberOfItems: venues.length,
    itemListElement: venues.map((venue, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      item: {
        '@type': LOCATION_SCHEMA_TYPE[venue.tipo],
        name: venue.nome,
        description: text(venue.descrizione, lang) || undefined,
        url: venue.website || undefined,
        address: { '@type': 'PostalAddress', addressCountry: 'IT' },
      },
    })),
  };
}

/**
 * Una storia come Article, non Event: è il racconto di un matrimonio
 * privato già avvenuto, non un evento pubblico con date e biglietti.
 */
export function storyArticle(story: Story, lang: Lang, slug: string): Thing {
  return {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: text(story.title, lang),
    datePublished: story.date ?? undefined,
    image: imageAbsoluteUrl(story.cover, SITE_URL) ?? undefined,
    author: businessRef,
    publisher: businessRef,
    about: story.locationName
      ? { '@type': 'Place', name: story.locationName, address: { '@type': 'PostalAddress', addressCountry: 'IT' } }
      : undefined,
    url: url('stories', lang, slug),
    inLanguage: HTML_LANG[lang],
  };
}

export function partnerOrganization(partner: Partner, lang: Lang): Thing {
  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: partner.name,
    description: text(partner.description, lang) || undefined,
    url: partner.website || undefined,
    logo: imageAbsoluteUrl(partner.logo, SITE_URL) ?? undefined,
  };
}

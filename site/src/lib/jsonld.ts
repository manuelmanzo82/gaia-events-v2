import { BUSINESS, SITE_URL, type Lang } from '../config/site';
import { HTML_LANG } from '../i18n';
import { path, url } from '../i18n/routes';
import { text } from './content';
import { imageAbsoluteUrl } from './images';
import type { Event, Faq, Image, Post, Review, Service } from './types';

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

export function event(item: Event, lang: Lang): Thing {
  return {
    '@context': 'https://schema.org',
    '@type': 'Event',
    name: text(item.title, lang),
    description: text(item.excerpt, lang),
    startDate: item.date,
    eventStatus: 'https://schema.org/EventScheduled',
    eventAttendanceMode: 'https://schema.org/OfflineEventAttendanceMode',
    location: {
      '@type': 'Place',
      name: text(item.location, lang),
      address: {
        '@type': 'PostalAddress',
        addressCountry: BUSINESS.country,
      },
    },
    image: imageAbsoluteUrl(item.cover, SITE_URL) ?? undefined,
    organizer: businessRef,
    url: url('portfolio', lang, item.slug),
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

export function reviewSnippets(items: Review[], lang: Lang): Thing | null {
  const reviews = items.filter((item) => text(item.text, lang));
  if (reviews.length === 0) return null;

  const total = reviews.reduce((sum, item) => sum + item.rating, 0);

  return {
    '@context': 'https://schema.org',
    '@type': 'LocalBusiness',
    '@id': ORG_ID,
    name: BUSINESS.name,
    aggregateRating: {
      '@type': 'AggregateRating',
      ratingValue: (total / reviews.length).toFixed(1),
      reviewCount: reviews.length,
      bestRating: 5,
    },
    review: reviews.map((item) => ({
      '@type': 'Review',
      author: { '@type': 'Person', name: item.author },
      datePublished: item.date,
      reviewBody: text(item.text, lang),
      reviewRating: {
        '@type': 'Rating',
        ratingValue: item.rating,
        bestRating: 5,
      },
    })),
  };
}

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

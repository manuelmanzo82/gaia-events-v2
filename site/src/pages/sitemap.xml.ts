import type { APIRoute } from 'astro';
import { DEFAULT_LANG, IS_STAGING, LANGS, type Lang } from '../config/site';
import { HTML_LANG } from '../i18n';
import { url, type RouteKey } from '../i18n/routes';
import {
  availableLangs,
  getDestinationWedding,
  getEvents,
  getPage,
  getPosts,
  isPublishable,
  pick,
} from '../lib/content';

/**
 * Sitemap scritta a mano invece di @astrojs/sitemap.
 *
 * Motivo: l'integrazione accoppia le lingue solo se lo slug è identico,
 * mentre qui gli slug sono tradotti (/servizi ↔ /en/services). Con
 * l'integrazione metà delle pagine restava senza alternate.
 *
 * Qui gli alternate escono dalla stessa mappa di ROUTES usata dalle pagine,
 * e una lingua compare solo se quella pagina è stata davvero generata.
 */

interface Entry {
  key: RouteKey;
  slug?: string;
  langs: Lang[];
  lastmod?: string;
}

/** Campi che devono esistere perché la pagina abbia senso in quella lingua. */
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

function entries(): Entry[] {
  const list: Entry[] = [];

  // La home esiste sempre in entrambe le lingue.
  list.push({ key: 'home', langs: [...LANGS] });

  for (const [key, required] of Object.entries(PAGE_REQUIREMENTS) as Array<[RouteKey, string[]]>) {
    const page = getPage(key);
    if (!page) continue;
    const langs = availableLangs(page, required as Array<keyof typeof page>);
    if (langs.length > 0) list.push({ key, langs, lastmod: page.updatedAt });
  }

  const dw = getDestinationWedding();
  const dwLangs = availableLangs(dw, ['title', 'intro']);
  if (dwLangs.length > 0) list.push({ key: 'destinationWedding', langs: dwLangs });

  for (const event of getEvents()) {
    const langs = availableLangs(event, ['title', 'excerpt', 'description']);
    if (langs.length > 0) {
      list.push({ key: 'portfolio', slug: event.slug, langs, lastmod: event.date });
    }
  }

  for (const post of getPosts()) {
    const langs = availableLangs(post, ['title', 'slug', 'body']);
    for (const lang of langs) {
      // Lo slug cambia da lingua a lingua: ogni URL dichiara gli alternate
      // con lo slug corretto dell'altra lingua.
      const slug = pick(post.slug, lang);
      if (!slug) continue;
      list.push({ key: 'blog', slug, langs: [lang], lastmod: post.date });
    }
  }

  return list;
}

/** Alternate di un articolo: gli slug sono diversi, quindi vanno ricalcolati. */
function postAlternates(slug: string): Array<{ lang: Lang; href: string }> {
  const post = getPosts().find((candidate) =>
    LANGS.some((lang) => pick(candidate.slug, lang) === slug),
  );
  if (!post) return [];

  return LANGS.filter((lang) => isPublishable(post, lang, ['title', 'slug', 'body']))
    .map((lang) => ({ lang, href: url('blog', lang, pick(post.slug, lang)!) }));
}

function escapeXml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

export const GET: APIRoute = () => {
  // In staging il sito è noindex: una sitemap vuota evita indicizzazioni per sbaglio.
  if (IS_STAGING) {
    return new Response(
      '<?xml version="1.0" encoding="UTF-8"?>\n' +
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>\n',
      { headers: { 'Content-Type': 'application/xml; charset=utf-8' } },
    );
  }

  const lines: string[] = [
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"',
    '        xmlns:xhtml="http://www.w3.org/1999/xhtml">',
  ];

  for (const entry of entries()) {
    const alternates =
      entry.key === 'blog' && entry.slug
        ? postAlternates(entry.slug)
        : entry.langs.map((lang) => ({ lang, href: url(entry.key, lang, entry.slug) }));

    for (const lang of entry.langs) {
      const loc = url(entry.key, lang, entry.slug);
      lines.push('  <url>');
      lines.push(`    <loc>${escapeXml(loc)}</loc>`);
      if (entry.lastmod) lines.push(`    <lastmod>${entry.lastmod}</lastmod>`);

      for (const alternate of alternates) {
        lines.push(
          `    <xhtml:link rel="alternate" hreflang="${HTML_LANG[alternate.lang]}" href="${escapeXml(alternate.href)}"/>`,
        );
      }

      const xDefault = alternates.find((alternate) => alternate.lang === DEFAULT_LANG);
      if (xDefault) {
        lines.push(
          `    <xhtml:link rel="alternate" hreflang="x-default" href="${escapeXml(xDefault.href)}"/>`,
        );
      }

      lines.push('  </url>');
    }
  }

  lines.push('</urlset>', '');

  return new Response(lines.join('\n'), {
    headers: { 'Content-Type': 'application/xml; charset=utf-8' },
  });
};

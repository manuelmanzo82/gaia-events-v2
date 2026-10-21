import type { APIRoute } from 'astro';
import { DEFAULT_LANG, IS_STAGING, LANGS, type Lang } from '../config/site';
import { HTML_LANG } from '../i18n';
import { url, type RouteKey } from '../i18n/routes';
import { LUOGO_FIELDS, POST_FIELDS, STORY_FIELDS, langsFor } from '../lib/availability';
import {
  availableLangs,
  getLuoghiFlat,
  getPage,
  getPosts,
  getStories,
  isPublishable,
  pick,
  slugLuogo,
} from '../lib/content';

/**
 * Sitemap scritta a mano invece di @astrojs/sitemap.
 *
 * Motivo: l'integrazione accoppia le lingue solo se lo slug è identico,
 * mentre qui gli slug sono tradotti (/servizi ↔ /en/services). Con
 * l'integrazione metà delle pagine restava senza alternate.
 *
 * In quali lingue esista ogni pagina lo decide lib/availability.ts, lo
 * stesso modulo che usano le pagine e il menu: così la sitemap non può
 * annunciare un URL che il build non genera.
 */

interface Entry {
  key: RouteKey;
  slug?: string;
  langs: Lang[];
  lastmod?: string;
}

/** Pagine senza elenco, dichiarate una volta sola. */
const STATIC_KEYS: RouteKey[] = [
  'home',
  'about',
  'services',
  'reviews',
  'blog',
  'faq',
  'quote',
  'contact',
  'destinationWedding',
  'privacy',
  'cookie',
  'places',
  'stories',
  'partners',
];

function entries(): Entry[] {
  const list: Entry[] = [];

  for (const key of STATIC_KEYS) {
    const langs = langsFor(key);
    if (langs.length === 0) continue;

    list.push({ key, langs, lastmod: getPage(key)?.updatedAt });
  }

  for (const post of getPosts()) {
    for (const lang of availableLangs(post, POST_FIELDS)) {
      // Lo slug cambia da lingua a lingua: ogni URL dichiara gli alternate
      // con lo slug corretto dell'altra lingua.
      const slug = pick(post.slug, lang);
      if (!slug) continue;
      list.push({ key: 'blog', slug, langs: [lang], lastmod: post.date });
    }
  }

  // Storie: slug bilingue come i post, un URL per lingua pubblicabile.
  for (const story of getStories()) {
    for (const lang of availableLangs(story, STORY_FIELDS)) {
      const slug = pick(story.slug, lang);
      if (!slug) continue;
      list.push({ key: 'stories', slug, langs: [lang], lastmod: story.date ?? undefined });
    }
  }

  // Luoghi: slug composto regione/zona, tradotto per lingua.
  for (const luogo of getLuoghiFlat()) {
    for (const lang of availableLangs(luogo, LUOGO_FIELDS)) {
      const slug = slugLuogo(luogo, lang);
      if (!slug) continue;
      list.push({ key: 'places', slug, langs: [lang] });
    }
  }

  return list;
}

/** Alternate di una storia: come i post, gli slug vanno ricalcolati. */
function storyAlternates(slug: string): Array<{ lang: Lang; href: string }> {
  const story = getStories().find((candidate) =>
    LANGS.some((lang) => pick(candidate.slug, lang) === slug),
  );
  if (!story) return [];

  return LANGS.filter((lang) => isPublishable(story, lang, STORY_FIELDS))
    .map((lang) => ({ lang, href: url('stories', lang, pick(story.slug, lang)!) }));
}

/** Alternate di un luogo: lo slug composto va ricalcolato per lingua. */
function luogoAlternates(slug: string): Array<{ lang: Lang; href: string }> {
  const luogo = getLuoghiFlat().find((candidate) =>
    LANGS.some((lang) => slugLuogo(candidate, lang) === slug),
  );
  if (!luogo) return [];

  return LANGS.filter((lang) => isPublishable(luogo, lang, LUOGO_FIELDS))
    .flatMap((lang) => {
      const tradotto = slugLuogo(luogo, lang);
      return tradotto ? [{ lang, href: url('places', lang, tradotto) }] : [];
    });
}

/** Alternate di un articolo: gli slug sono diversi, quindi vanno ricalcolati. */
function postAlternates(slug: string): Array<{ lang: Lang; href: string }> {
  const post = getPosts().find((candidate) =>
    LANGS.some((lang) => pick(candidate.slug, lang) === slug),
  );
  if (!post) return [];

  return LANGS.filter((lang) => isPublishable(post, lang, POST_FIELDS))
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
    let alternates: Array<{ lang: Lang; href: string }>;
    if (entry.key === 'blog' && entry.slug) {
      alternates = postAlternates(entry.slug);
    } else if (entry.key === 'stories' && entry.slug) {
      alternates = storyAlternates(entry.slug);
    } else if (entry.key === 'places' && entry.slug) {
      alternates = luogoAlternates(entry.slug);
    } else {
      alternates = entry.langs.map((lang) => ({ lang, href: url(entry.key, lang, entry.slug) }));
    }

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

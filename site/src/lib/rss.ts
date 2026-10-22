/**
 * Feed RSS 2.0 del Magazine (/magazine/rss.xml, /en/magazine/rss.xml),
 * scritto a mano: nessuna dipendenza. Contenuto: titolo, link, data,
 * categoria, estratto e il corpo completo in HTML (già filtrato dal back
 * office con la lista dei tag consentiti) con i link resi assoluti.
 */
import { BUSINESS, SITE_URL, type Lang } from '../config/site';
import { HTML_LANG, useTranslations } from '../i18n';
import { path, url } from '../i18n/routes';
import { getPage, pick, text } from './content';
import { postsIn } from './magazine';

const esc = (s: string): string =>
  s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const cdata = (s: string): string => `<![CDATA[${s.replace(/]]>/g, ']]]]><![CDATA[>')}]]>`;

/** href e src relativi («/…») diventano assoluti: i lettori RSS li vogliono così. */
const assoluti = (html: string): string => html.replace(/(href|src)="\/(?!\/)/g, `$1="${SITE_URL}/`);

export function feedRss(lang: Lang): Response {
  const t = useTranslations(lang);
  const pagina = getPage('blog');
  const titolo = `${text(pagina?.title, lang) || t('nav.blog')} — ${BUSINESS.legalName}`;
  const descrizione = text(pagina?.intro, lang);
  const indirizzo = url('blog', lang);
  const self = `${SITE_URL}${path('blog', lang)}rss.xml`;
  const posts = postsIn(lang).slice(0, 30);
  const ultimo = posts.map((p) => p.modified || p.date).sort().at(-1);

  const voci = posts
    .map((p) => {
      const link = url('blog', lang, pick(p.slug, lang)!);
      const data = new Date(`${p.date}T09:00:00Z`).toUTCString();
      return [
        '    <item>',
        `      <title>${esc(text(p.title, lang))}</title>`,
        `      <link>${esc(link)}</link>`,
        `      <guid isPermaLink="true">${esc(link)}</guid>`,
        `      <pubDate>${data}</pubDate>`,
        text(p.category, lang) ? `      <category>${esc(text(p.category, lang))}</category>` : '',
        `      <dc:creator>${esc(BUSINESS.legalName)}</dc:creator>`,
        `      <description>${esc(text(p.excerpt, lang))}</description>`,
        `      <content:encoded>${cdata(assoluti(text(p.body, lang)))}</content:encoded>`,
        '    </item>',
      ]
        .filter(Boolean)
        .join('\n');
    })
    .join('\n');

  const xml = [
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/">',
    '  <channel>',
    `    <title>${esc(titolo)}</title>`,
    `    <link>${esc(indirizzo)}</link>`,
    `    <description>${esc(descrizione)}</description>`,
    `    <language>${HTML_LANG[lang]}</language>`,
    ultimo ? `    <lastBuildDate>${new Date(`${ultimo}T09:00:00Z`).toUTCString()}</lastBuildDate>` : '',
    `    <atom:link href="${esc(self)}" rel="self" type="application/rss+xml" />`,
    voci,
    '  </channel>',
    '</rss>',
    '',
  ]
    .filter((r) => r !== '')
    .join('\n');

  return new Response(xml, { headers: { 'Content-Type': 'application/rss+xml; charset=utf-8' } });
}

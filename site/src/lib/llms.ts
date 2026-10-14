import { BUSINESS, DEFAULT_LANG, SITE_URL, type Lang } from '../config/site';
import { url } from '../i18n/routes';
import {
  getDestinationWedding,
  getEvents,
  getFaqs,
  getPage,
  getPosts,
  getReviews,
  getServices,
  getSettings,
  isPublishable,
  pick,
  text,
} from './content';

/**
 * llms.txt e llms-full.txt generati dai contenuti del back office,
 * non scritti a mano: quando Gaia pubblica si aggiornano da soli.
 * Formato: https://llmstxt.org
 */

const lang: Lang = DEFAULT_LANG;

function stripHtml(html: string): string {
  return html
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/\s+/g, ' ')
    .trim();
}

function header(): string[] {
  const settings = getSettings();
  return [
    `# ${settings.siteName}`,
    '',
    `> ${text(settings.tagline, lang)}. ${text(settings.metaDescription, lang)}`,
    '',
    `Wedding & event planner: ${BUSINESS.legalName}, ${BUSINESS.city} (${BUSINESS.region}), Italia.`,
    `Contatti: ${BUSINESS.email} · WhatsApp ${BUSINESS.phone} · Instagram @${BUSINESS.instagram}`,
    `Lingue del sito: italiano (${SITE_URL}/) e inglese (${SITE_URL}/en/).`,
    '',
  ];
}

/** Indice sintetico: solo link e una riga di descrizione. */
export function llmsTxt(): string {
  const lines = [...header()];

  lines.push('## Pagine', '');
  for (const key of ['about', 'services', 'portfolio', 'reviews', 'blog', 'faq', 'quote', 'contact'] as const) {
    const page = getPage(key);
    if (!page) continue;
    const description = text(page.intro, lang) || text(page.metaDescription, lang);
    lines.push(`- [${text(page.title, lang)}](${url(key, lang)})${description ? `: ${description}` : ''}`);
  }
  const dw = getDestinationWedding();
  if (text(dw.title, lang)) {
    lines.push(`- [${text(dw.title, lang)}](${url('destinationWedding', lang)}): ${text(dw.intro, lang)}`);
  }
  lines.push('');

  const services = getServices();
  if (services.length > 0) {
    lines.push('## Servizi', '');
    for (const service of services) {
      lines.push(`- ${text(service.title, lang)}: ${text(service.description, lang)}`);
    }
    lines.push('');
  }

  const events = getEvents();
  if (events.length > 0) {
    lines.push('## Eventi realizzati', '');
    for (const event of events) {
      lines.push(
        `- [${text(event.title, lang)}](${url('portfolio', lang, event.slug)}): ` +
          `${text(event.location, lang)}, ${event.date}`,
      );
    }
    lines.push('');
  }

  const posts = getPosts();
  if (posts.length > 0) {
    lines.push('## Blog', '');
    for (const post of posts) {
      const slug = pick(post.slug, lang);
      if (!slug) continue;
      lines.push(`- [${text(post.title, lang)}](${url('blog', lang, slug)}): ${text(post.excerpt, lang)}`);
    }
    lines.push('');
  }

  return lines.join('\n');
}

/** Versione estesa: testo completo, per chi legge l'intero sito in un file. */
export function llmsFullTxt(): string {
  const lines = [...header()];

  for (const key of ['about', 'services', 'quote', 'contact', 'privacy', 'cookie'] as const) {
    const page = getPage(key);
    if (!page) continue;
    lines.push(`## ${text(page.title, lang)}`, '', `URL: ${url(key, lang)}`, '');
    if (text(page.intro, lang)) lines.push(text(page.intro, lang), '');
    if (text(page.body, lang)) lines.push(stripHtml(text(page.body, lang)), '');
  }

  const services = getServices();
  if (services.length > 0) {
    lines.push('## Servizi in dettaglio', '');
    for (const service of services) {
      lines.push(`### ${text(service.title, lang)}`, '');
      if (text(service.subtitle, lang)) lines.push(text(service.subtitle, lang), '');
      lines.push(text(service.description, lang), '');
      const features = pick(service.features, lang);
      if (features) {
        for (const feature of features) lines.push(`- ${feature}`);
        lines.push('');
      }
    }
  }

  const events = getEvents().filter((event) => isPublishable(event, lang, ['title', 'description']));
  if (events.length > 0) {
    lines.push('## Eventi realizzati', '');
    for (const event of events) {
      lines.push(
        `### ${text(event.title, lang)}`,
        '',
        `URL: ${url('portfolio', lang, event.slug)}`,
        `Location: ${text(event.location, lang)} · Data: ${event.date} · Categoria: ${text(event.category, lang)}`,
        '',
        stripHtml(text(event.description, lang)),
        '',
      );
    }
  }

  const faqs = getFaqs();
  if (faqs.length > 0) {
    lines.push('## Domande frequenti', '');
    for (const faq of faqs) {
      lines.push(`### ${text(faq.question, lang)}`, '', text(faq.answer, lang), '');
    }
  }

  const reviews = getReviews();
  if (reviews.length > 0) {
    lines.push('## Recensioni', '');
    for (const review of reviews) {
      const body = text(review.text, lang);
      if (!body) continue;
      lines.push(`- ${review.author} (${review.rating}/5, ${review.date}): ${body}`);
    }
    lines.push('');
  }

  const posts = getPosts().filter((post) => isPublishable(post, lang, ['title', 'slug', 'body']));
  if (posts.length > 0) {
    lines.push('## Articoli', '');
    for (const post of posts) {
      lines.push(
        `### ${text(post.title, lang)}`,
        '',
        `URL: ${url('blog', lang, pick(post.slug, lang)!)} · ${post.date}`,
        '',
        stripHtml(text(post.body, lang)),
        '',
      );
    }
  }

  return lines.join('\n');
}

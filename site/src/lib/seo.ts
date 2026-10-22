import { DEFAULT_OG_IMAGE, IS_STAGING, LANGS, SITE_URL, type Lang } from '../config/site';
import { HTML_LANG } from '../i18n';
import { url, type RouteKey } from '../i18n/routes';
import { getSettings, text } from './content';
import { imageAbsoluteUrl } from './images';
import type { Image, Localized, SeoFields } from './types';

export interface Alternate {
  lang: Lang;
  hreflang: string;
  href: string;
}

export interface Meta {
  title: string;
  description: string;
  /** Null sulle pagine con noindex forzato (404): non hanno un indirizzo proprio. */
  canonical: string | null;
  ogImage: string;
  ogType: 'website' | 'article';
  htmlLang: string;
  alternates: Alternate[];
  /** True in staging: aggiunge noindex, nofollow. */
  noindex: boolean;
}

interface BuildMetaOptions {
  lang: Lang;
  routeKey: RouteKey;
  slug?: string;
  /** Titolo della pagina; viene composto con il nome del sito. */
  title?: string;
  description?: string;
  /** Campi SEO che arrivano dal back office: hanno la precedenza. */
  seo?: SeoFields;
  image?: Image | null;
  ogType?: 'website' | 'article';
  /** Lingue in cui la pagina esiste davvero: se l'EN manca, non va dichiarato. */
  availableLangs?: readonly Lang[];
  /** Forza il noindex su una pagina specifica (es. 404, ringraziamento). */
  noindex?: boolean;
}

function fromLocalized(field: Localized | undefined, lang: Lang): string {
  return text(field, lang);
}

/**
 * Costruisce i meta di una pagina.
 *
 * Precedenza: campi SEO del back office → valori passati dalla pagina →
 * default dalle impostazioni. Il canonical e gli hreflang passano sempre
 * da SITE_URL: nella v1 metà del sito puntava al dominio sbagliato.
 */
export function buildMeta(options: BuildMetaOptions): Meta {
  const {
    lang,
    routeKey,
    slug,
    seo,
    image,
    ogType = 'website',
    availableLangs = LANGS,
    noindex = false,
  } = options;

  const settings = getSettings();
  const siteName = settings.siteName;

  const seoTitle = fromLocalized(seo?.metaTitle, lang);
  const pageTitle = seoTitle || options.title || '';
  const defaultTitle = fromLocalized(settings.metaTitle, lang) || siteName;

  const title =
    routeKey === 'home' || !pageTitle
      ? defaultTitle
      : `${pageTitle} | ${siteName}`;

  const description =
    fromLocalized(seo?.metaDescription, lang) ||
    options.description ||
    fromLocalized(settings.metaDescription, lang);

  const ogImage =
    imageAbsoluteUrl(seo?.ogImage ?? image ?? settings.ogImage, SITE_URL) ??
    SITE_URL + DEFAULT_OG_IMAGE;

  const alternates: Alternate[] = noindex ? [] : availableLangs.map((alt) => ({
    lang: alt,
    hreflang: HTML_LANG[alt],
    href: url(routeKey, alt, slug),
  }));

  return {
    title,
    description,
    canonical: noindex ? null : url(routeKey, lang, slug),
    ogImage,
    ogType,
    htmlLang: HTML_LANG[lang],
    alternates,
    noindex: noindex || IS_STAGING,
  };
}

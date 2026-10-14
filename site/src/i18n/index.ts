import { DEFAULT_LANG, LANGS, type Lang } from '../config/site';
import { UI, type UIKey } from './ui';

export { LANGS, DEFAULT_LANG, type Lang };
export * from './routes';
export { UI, type UIKey };

/** Traduttore legato a una lingua: `const t = useTranslations(lang)`. */
export function useTranslations(lang: Lang) {
  return function t(key: UIKey): string {
    return UI[key][lang] ?? UI[key][DEFAULT_LANG];
  };
}

/** Codice da mettere in `<html lang>` e negli hreflang. */
export const HTML_LANG: Record<Lang, string> = {
  it: 'it-IT',
  en: 'en',
};

/** Nome della lingua, per il selettore. */
export const LANG_NAME: Record<Lang, string> = {
  it: 'Italiano',
  en: 'English',
};

/** Data formattata nella lingua della pagina. */
export function formatDate(value: string | Date, lang: Lang): string {
  const date = typeof value === 'string' ? new Date(value) : value;
  if (Number.isNaN(date.getTime())) return '';
  return new Intl.DateTimeFormat(HTML_LANG[lang], {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date);
}

import { UPLOADS_BASE } from '../config/site';
import { text } from './content';
import type { Image } from './types';
import type { Lang } from '../config/site';

/**
 * Le immagini non passano mai dal build di Astro: le varianti le genera
 * ImageService nel back office PHP e restano in /uploads/ sull'host.
 *
 * Convenzione dei nomi, accanto all'originale:
 *   eventi/villa-aurelia/01.jpg
 *   eventi/villa-aurelia/01-800.webp
 *   eventi/villa-aurelia/01-800.avif   (solo se il server supporta l'AVIF)
 */

export const DEFAULT_WIDTHS = [480, 800, 1200, 1600, 2400] as const;

/** URL pubblico dell'originale. */
export function uploadUrl(src: string): string {
  return `${UPLOADS_BASE}/${src.replace(/^\/+/, '')}`;
}

/** URL di una variante: `01.jpg` + 800 + 'webp' → `01-800.webp` */
export function variantUrl(src: string, width: number, format: 'webp' | 'avif'): string {
  const clean = src.replace(/^\/+/, '');
  const base = clean.replace(/\.[^./]+$/, '');
  return `${UPLOADS_BASE}/${base}-${width}.${format}`;
}

/** Larghezze utili: mai più grandi dell'originale. */
function usableWidths(image: Image): number[] {
  const widths = image.widths?.length ? image.widths : [...DEFAULT_WIDTHS];
  const usable = widths.filter((w) => w <= image.width).sort((a, b) => a - b);
  return usable.length ? usable : [image.width];
}

function srcset(image: Image, format: 'webp' | 'avif'): string {
  return usableWidths(image)
    .map((w) => `${variantUrl(image.src, w, format)} ${w}w`)
    .join(', ');
}

export interface PictureSource {
  type: string;
  srcset: string;
  sizes: string;
}

export interface PictureData {
  sources: PictureSource[];
  src: string;
  width: number;
  height: number;
  alt: string;
  sizes: string;
  /** Per il crop via CSS quando il contenitore ha un rapporto diverso. */
  objectPosition?: string;
}

/**
 * Dati per un `<picture>`. L'AVIF viene incluso solo se il back office
 * l'ha davvero generato (`formats.avif`), perché su hosting condiviso il
 * supporto non è garantito.
 *
 * width e height sono SEMPRE dichiarati: la v1 aveva 14 immagini senza
 * dimensioni e di conseguenza CLS.
 */
export function picture(
  image: Image | null | undefined,
  lang: Lang,
  sizes = '100vw',
): PictureData | null {
  if (!image?.src) return null;

  const sources: PictureSource[] = [];
  if (image.formats?.avif) {
    sources.push({ type: 'image/avif', srcset: srcset(image, 'avif'), sizes });
  }
  if (image.formats?.webp !== false) {
    sources.push({ type: 'image/webp', srcset: srcset(image, 'webp'), sizes });
  }

  return {
    sources,
    src: uploadUrl(image.src),
    width: image.width,
    height: image.height,
    alt: text(image.alt, lang),
    sizes,
    objectPosition: image.focal
      ? `${(image.focal.x * 100).toFixed(1)}% ${(image.focal.y * 100).toFixed(1)}%`
      : undefined,
  };
}

/** URL assoluto di un'immagine, per OG e JSON-LD. */
export function imageAbsoluteUrl(image: Image | null | undefined, siteUrl: string): string | null {
  if (!image?.src) return null;
  return siteUrl + uploadUrl(image.src);
}

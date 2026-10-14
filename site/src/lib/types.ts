import type { Lang } from '../config/site';

/**
 * Ogni campo di testo è bilingue. Se la versione EN è vuota o assente,
 * la pagina EN non viene generata: vedi `hasTranslation` in content.ts.
 *
 * Nel database corrisponde alla coppia di colonne `campo_it` / `campo_en`.
 */
export type Localized<T = string> = Partial<Record<Lang, T>>;

/** Uso consentito di un'immagine, deciso dalla sua risoluzione. */
export type ImageUsage = 'hero' | 'gallery_large' | 'card' | 'thumbnail';

/** Immagine caricata dal back office. Le varianti sono già su disco in /uploads/. */
export interface Image {
  /** Percorso relativo dentro /uploads/, es. 'eventi/villa-aurelia/01.jpg' */
  src: string;
  width: number;
  height: number;
  /**
   * Larghezza massima in pixel CSS a cui l'immagine resta nitida: la
   * larghezza del file diviso 2, perché su retina servono due pixel di file
   * per ogni pixel CSS. La calcola il back office all'upload.
   *
   * È un limite, non un suggerimento: nessun componente, lightbox inclusa,
   * deve mostrare l'immagine più larga di così. Meglio una foto piccola
   * usata bene che la stessa foto ingrandita e molle.
   */
  maxWidth?: number;
  usage?: ImageUsage;
  alt: Localized;
  /** Varianti generate da ImageService: WebP sempre, AVIF se il server lo supporta. */
  formats?: { webp?: boolean; avif?: boolean };
  /** Larghezze disponibili per il srcset. */
  widths?: number[];
  /** Punto focale per il crop, 0–1. Default centro. */
  focal?: { x: number; y: number };
  caption?: Localized;
}

export interface SeoFields {
  metaTitle?: Localized;
  metaDescription?: Localized;
  ogImage?: Image | null;
}

export interface Settings extends SeoFields {
  siteName: string;
  tagline: Localized;
  /** Testi dell'hero della home. */
  hero: {
    eyebrow: Localized;
    title: Localized;
    subtitle: Localized;
    image: Image | null;
    ctaLabel: Localized;
  };
  /** Introduzione di ogni sezione della home, indicizzata per chiave. */
  sections: Record<string, { title: Localized; text: Localized }>;
  about: { title: Localized; text: Localized; image: Image | null };
  contact: { title: Localized; text: Localized; address: Localized };
}

export interface Service extends SeoFields {
  id: number;
  slug: string;
  title: Localized;
  subtitle: Localized;
  description: Localized;
  features: Localized<string[]>;
  icon?: string;
  image: Image | null;
  active: boolean;
  order: number;
}

export type EventCategory = 'wedding' | 'corporate' | 'celebrations';

export interface Event extends SeoFields {
  id: number;
  slug: string;
  macroCategory: EventCategory;
  title: Localized;
  location: Localized;
  /** ISO 8601, es. '2026-06-14' */
  date: string;
  category: Localized;
  excerpt: Localized;
  description: Localized;
  cover: Image | null;
  hero: Image | null;
  /** URL YouTube o Vimeo. */
  video?: string | null;
  gallery: Image[];
  featured: boolean;
  active: boolean;
  order: number;
}

export interface Review {
  id: number;
  author: string;
  eventName: Localized;
  date: string;
  text: Localized;
  rating: number;
  image: Image | null;
  featured: boolean;
  active: boolean;
  order: number;
}

export interface Post extends SeoFields {
  id: number;
  slug: Localized;
  title: Localized;
  excerpt: Localized;
  category: Localized;
  date: string;
  readingTime: number;
  cover: Image | null;
  /** HTML già sanificato dal back office. */
  body: Localized;
  active: boolean;
}

export interface Faq {
  id: number;
  question: Localized;
  answer: Localized;
  group?: string;
  active: boolean;
  order: number;
}

/** Pagina statica gestita dal back office: chi sono, privacy, cookie, … */
export interface Page extends SeoFields {
  /** Corrisponde a una chiave di ROUTES in i18n/routes.ts. */
  key: string;
  title: Localized;
  intro: Localized;
  /** HTML già sanificato dal back office. */
  body: Localized;
  hero: Image | null;
  /** Blocchi ripetibili, es. percorso e valori in «chi sono». */
  blocks: Block[];
  updatedAt?: string;
}

/**
 * Blocco ripetibile di una pagina: serve alla destination wedding e alla
 * pagina chi sono (percorso e valori).
 */
export interface Block {
  kind:
    | 'intro'
    | 'services'
    | 'destinations'
    | 'process'
    | 'faq'
    | 'testimonial'
    | 'cta'
    | 'timeline'
    | 'values';
  title: Localized;
  text: Localized;
  items?: BlockItem[];
  image?: Image | null;
  order: number;
}

export interface BlockItem {
  /** Etichetta breve non tradotta, es. l'anno in una timeline. */
  label?: string;
  title: Localized;
  text: Localized;
  image?: Image | null;
}

export interface DestinationWedding extends SeoFields {
  title: Localized;
  intro: Localized;
  hero: Image | null;
  blocks: Block[];
}

export interface InstagramItem {
  id: string;
  type: 'image' | 'video';
  image: Image | null;
  caption: Localized;
  permalink: string;
  postedAt: string;
}

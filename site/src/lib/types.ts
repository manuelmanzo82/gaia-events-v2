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
  /** Manifesto della home: una frase grande sotto l'hero, con la firma. */
  home: { manifesto: Localized; manifestoFirma: Localized };
  /** Dati legali per il footer: la P.IVA e' obbligatoria sui siti aziendali italiani. */
  legal: { piva: Localized };
  about: {
    title: Localized;
    text: Localized;
    image: Image | null;
    /** Anno di inizio (riga «dal …» nell'apertura di Chi sono). */
    since?: Localized;
    /** I numeri di Chi sono: valore + etichetta, solo se impostati. */
    stats?: Record<string, { value?: Localized; label?: Localized }>;
    /** Foto di Chi sono: quote, events, services. */
    photos?: Record<string, Image | null>;
  };
  contact: { title: Localized; text: Localized; address: Localized };
  /** Pagina Servizi (impostazioni del gruppo «servizi»). */
  servizi?: {
    compare?: { title?: Localized };
  };
  /** Pagina Preventivo (impostazioni del gruppo «preventivo»). */
  quote?: {
    eyebrow?: Localized;
    title?: Localized;
    line?: Localized;
    signature?: Localized;
    replyHours?: Localized;
    assurance1?: { title?: Localized; text?: Localized };
    assurance2?: { title?: Localized; text?: Localized };
    assurance3?: { title?: Localized; text?: Localized };
    faq1?: { question?: Localized; answer?: Localized };
    faq2?: { question?: Localized; answer?: Localized };
    faq3?: { question?: Localized; answer?: Localized };
    /** Foto per passaggio: event, names, when, where, guests, help, budget, contact, letter, thanks. */
    photos?: Record<string, Image | null>;
    sendCopy?: Localized;
  };
}

export interface Service extends SeoFields {
  id: number;
  slug: string;
  title: Localized;
  subtitle: Localized;
  description: Localized;
  features: Localized<string[]>;
  /** Slug degli altri servizi compresi in questo (back office, «Include»). */
  includes?: string[];
  icon?: string;
  image: Image | null;
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

/** Un post Instagram mostrato in home (caricato a mano o, più avanti, via API). */
export interface InstagramPost {
  id: number;
  image: Image | null;
  /** Link al post; senza, la foto porta al profilo. */
  permalink: string | null;
  takenAt: string;
  tipo: 'foto' | 'reel' | 'carosello';
}

/** instagram.json: profilo e ultimi N post visibili, già ordinati. */
export interface InstagramFeed {
  handle: string;
  profileUrl: string;
  posts: InstagramPost[];
}

/* ------------------------------------------------------------------ *
 * Fase 2: luoghi, location, highlights, partner, storie, hero
 * ------------------------------------------------------------------ */

export type LuogoTipo = 'regione' | 'zona';

/** Luogo: una regione o una città/zona (asse SEO principale). */
export interface Luogo extends SeoFields {
  id: number;
  tipo: LuogoTipo;
  nome: Localized;
  /** Slug tradotti, unici globalmente: /luoghi/lazio/roma ↔ /en/destinations/lazio/rome */
  slug: Localized;
  intro: Localized;
  testo: Localized;
  cover: Image | null;
  gallery: Image[];
  active: boolean;
  order: number;
  /** Solo sulle regioni: le zone annidate. */
  zone?: Luogo[];
}

export type LocationTipo =
  | 'villa'
  | 'castello'
  | 'chiesa'
  | 'terrazza'
  | 'tenuta'
  | 'hotel'
  | 'ristorante'
  | 'altro';

/** Location come testo: niente foto dedicate, leggibile da Google e dalle AI. */
export interface LocationVenue {
  id: number;
  nome: string;
  tipo: LocationTipo;
  luogoId: number;
  descrizione: Localized;
  website?: string | null;
  /** Per il futuro: oggi non genera nessuna pagina. */
  ownPage: boolean;
  active: boolean;
  order: number;
}

export type HighlightSection = 'home' | 'chi-sono' | 'servizi' | 'destination' | 'luogo' | 'cta';

/** Foto singola scelta da Gaia. L'alt vive sull'immagine (media). */
export interface Highlight {
  id: number;
  image: Image | null;
  /** Frase breve sulla scheda impilata della home; vuota = alt accorciato. */
  title?: Localized;
  luogoId: number | null;
  sections: Array<{ section: HighlightSection; order: number }>;
  active: boolean;
}

export type PartnerCategory = 'fotografo' | 'fiori' | 'catering' | 'musica' | 'location' | 'altro';

export interface Partner {
  id: number;
  name: string;
  category: PartnerCategory;
  logo: Image | null;
  description: Localized;
  website?: string | null;
  active: boolean;
  order: number;
}

/** Voce della tabella di confronto dei servizi (pagina Servizi). */
export interface CompareItem {
  id: number;
  label: Localized;
  /** Slug dei servizi che comprendono direttamente la voce. */
  services: string[];
  active: boolean;
  order: number;
}

/** Stampa (giro 11): un articolo o un'intervista che parla di Gaia. */
export interface Press {
  id: number;
  /** Testata, non tradotta. */
  outlet: string;
  title: string;
  date: string | null;
  url: string | null;
  logo: Image | null;
  excerpt: Localized;
  active: boolean;
  order: number;
}

/** Azienda cliente (giro 11): per la fila di loghi «Hanno scelto Gaia». */
export interface Client {
  id: number;
  name: string;
  logo: Image | null;
  sector: string | null;
  website: string | null;
  active: boolean;
  order: number;
}

/** Tipo di evento (giro 11): matrimoni e unioni civili stanno nel gruppo «Matrimoni». */
export type EventType = 'matrimonio' | 'unione_civile' | 'festa' | 'aziendale';

/** Evento raccontato per intero (pagina I miei eventi). */
export interface Story extends SeoFields {
  id: number;
  /** Slug bilingue come i post del blog. */
  slug: Localized;
  title: Localized;
  couple?: string | null;
  type: EventType;
  locationName?: string | null;
  luogoId: number | null;
  date?: string | null;
  cover: Image | null;
  gallery: Image[];
  body: Localized;
  /** Credito del fotografo: un partner, mai una didascalia sotto la foto. */
  photographerId: number | null;
  active: boolean;
  order: number;
}

/** Hero di una pagina. Il poster è l'elemento LCP; il video arriva dopo. */
export interface PageHero {
  title: Localized;
  subtitle: Localized;
  cta: { label: Localized; route?: string | null };
  video: { mp4: string; webm?: string | null; poster: Image | null } | null;
  /** URL esterno (futuro Vimeo): si userà al posto dei file self-hosted. */
  videoUrl?: string | null;
  poster: Image | null;
  images: Image[];
}

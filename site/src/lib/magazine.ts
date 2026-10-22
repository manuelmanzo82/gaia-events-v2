/**
 * Magazine: funzioni condivise tra indice, articolo, RSS e JSON-LD.
 */
import type { Lang } from '../config/site';
import { POST_FIELDS } from './availability';
import { getPosts, isPublishable, pick, text } from './content';
import type { Post, PostBlock, Video } from './types';

/** Articoli pubblicati in questa lingua, dal più recente. */
export function postsIn(lang: Lang): Post[] {
  return getPosts().filter((post) => isPublishable(post, lang, POST_FIELDS));
}

/** Slug della categoria nella lingua (per il filtro ?categoria=). */
export function categoriaSlug(post: Post, lang: Lang): string {
  return pick(post.categorySlug, lang) ?? '';
}

/** Tutti i video di un articolo: copertina e blocchi. */
export function videoDi(post: Post): Video[] {
  const video: Video[] = [];
  if (post.coverVideo) video.push(post.coverVideo);
  for (const b of post.blocks ?? []) if (b.type === 'video') video.push(b.video);
  return video;
}

export const haVideo = (post: Post): boolean => videoDi(post).length > 0;

/** Ancora di un titolo, unica nell'articolo (stessa regola del back office). */
export function ancora(testo: string, usate: Set<string>): string {
  const base =
    testo
      .normalize('NFD')
      .replace(/[̀-ͯ]/g, '')
      .replace(/['’]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '')
      .slice(0, 60) || 'sezione';
  let id = base;
  let n = 2;
  while (usate.has(id)) id = `${base}-${n++}`;
  usate.add(id);
  return id;
}

/** I titoli H2 dell'articolo, con l'ancora, nell'ordine. */
export function titoliH2(blocks: PostBlock[], lang: Lang): Array<{ id: string; testo: string }> {
  const usate = new Set<string>();
  const out: Array<{ id: string; testo: string }> = [];
  for (const b of blocks) {
    if (b.type === 'h2' || b.type === 'h3') {
      const t = text(b.text, lang);
      const id = ancora(t, usate);
      if (b.type === 'h2') out.push({ id, testo: t });
    }
  }
  return out;
}

/** Tre correlati: stessa categoria, poi i più recenti. */
export function correlati(post: Post, lang: Lang, quanti = 3): Post[] {
  const altri = postsIn(lang).filter((p) => p.id !== post.id);
  const stessa = altri.filter((p) => categoriaSlug(p, lang) && categoriaSlug(p, lang) === categoriaSlug(post, lang));
  const resto = altri.filter((p) => !stessa.includes(p));
  return [...stessa, ...resto].slice(0, quanti);
}

/** Il primo in evidenza dal back office, altrimenti l'ultimo pubblicato. */
export function inEvidenza(posts: Post[]): Post | null {
  return posts.find((p) => p.featured) ?? posts[0] ?? null;
}

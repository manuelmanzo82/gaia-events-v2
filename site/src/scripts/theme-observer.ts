/**
 * Il header (logo, menu, EN, icone social) e i flottanti hanno SEMPRE lo
 * stesso colore tra loro (giro 10), deciso dal data-theme della sezione
 * che passa sotto la linea a metà header:
 *
 *   dark  → avorio  (hero, schede, pannelli, destination, griglia
 *                    Instagram: sezioni con foto, che portano anche una
 *                    sfumatura scura nella fascia alta per la leggibilità)
 *   light → inchiostro (fondi chiari pieni)
 *
 * Conta la sezione: un elemento [data-theme] che contiene la linea e che
 * è largo almeno il 60% dello schermo. I blocchi più stretti (la foto di
 * Gaia e quella della CTA, che occupano metà sezione; le tessere della
 * fascia su desktop) non cambiano il colore: altrimenti il menu, sull'altra
 * metà chiara, sarebbe illeggibile. Se più elementi contengono la linea
 * vince il più interno (l'ultimo nell'ordine del documento).
 *
 * Il tema finisce su data-header-theme e data-header-foto del header; i
 * flottanti leggono gli stessi attributi (scripts/flottanti.ts). Niente
 * IntersectionObserver (vedi la storia nel giro 3): qualche rettangolo
 * per frame di scroll costa nulla.
 */

interface Tema {
  tema: 'dark' | 'light';
  foto: '0' | '1';
}

const LARGHEZZA_MINIMA = 0.6;

let elementi: HTMLElement[] = [];
let programma: () => void = () => {};

/** Rimisura il tema (per chi muove la pagina senza evento scroll: il pin orizzontale di GSAP). */
export function aggiornaTema(): void {
  programma();
}

/** La sezione (elemento [data-theme] largo almeno il 60%) più interna che contiene il punto (x, y). */
export function sezioneInPunto(x: number, y: number): HTMLElement | null {
  const minima = window.innerWidth * LARGHEZZA_MINIMA;
  let sotto: HTMLElement | null = null;
  for (const el of elementi) {
    const r = el.getBoundingClientRect();
    // Rettangolo vuoto = elemento nascosto (display: none): non conta.
    if (r.height === 0 || r.width < minima) continue;
    if (r.top <= y && r.bottom > y && r.left <= x && r.right > x) sotto = el;
  }
  return sotto;
}

export function temaDi(el: HTMLElement | null): Tema | null {
  if (!el) return null;
  return {
    tema: el.getAttribute('data-theme') === 'dark' ? 'dark' : 'light',
    foto: el.hasAttribute('data-foto') ? '1' : '0',
  };
}

function imposta(el: HTMLElement, nome: string, valore: string): void {
  if (el.getAttribute(nome) !== valore) el.setAttribute(nome, valore);
}

export function initThemeObserver(header: HTMLElement): void {
  elementi = [...document.querySelectorAll<HTMLElement>('[data-theme]')].filter(
    // Il header stesso o elementi dentro di lui non contano.
    (el) => !header.contains(el),
  );
  if (elementi.length === 0) return;

  let inAttesa = false;

  const aggiorna = (): void => {
    inAttesa = false;

    const box = header.getBoundingClientRect();
    const linea = box.top + box.height / 2;
    const sotto = temaDi(sezioneInPunto(window.innerWidth / 2, linea));
    if (!sotto) return;

    imposta(header, 'data-header-theme', sotto.tema);
    imposta(header, 'data-header-foto', sotto.foto);
  };

  programma = (): void => {
    if (inAttesa) return;
    inAttesa = true;
    requestAnimationFrame(aggiorna);
  };

  // Lenis muove lo scroll nativo, quindi l'evento scroll arriva comunque;
  // resize e load coprono rotazione, barra degli indirizzi mobile e font.
  window.addEventListener('scroll', programma, { passive: true });
  window.addEventListener('resize', programma, { passive: true });
  window.addEventListener('load', programma);
  if ('ResizeObserver' in window) new ResizeObserver(programma).observe(header);

  aggiorna();
}

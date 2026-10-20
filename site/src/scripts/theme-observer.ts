/**
 * Il header (logo, menu, hamburger) e il torna-su cambiano colore in base
 * a quello che hanno sotto: ogni sezione o blocco con foto dichiara
 * data-theme="dark|light" (e data-foto se è una foto), e qui si misura
 * cosa c'è sotto un PUNTO dato.
 *
 * Regola (giro 6): sopra qualsiasi foto il testo è avorio; inchiostro solo
 * sui fondi chiari pieni. Siccome una sezione può essere divisa a metà
 * (CTA finale: foto a sinistra, beige a destra; Gaia: foto a sinistra,
 * avorio a destra), il tema si decide per punto e non per riga: il logo
 * guarda il punto sotto di sé, il menu (o l'hamburger) il punto sotto di
 * sé, il torna-su il proprio centro. Sul header finiscono quattro
 * attributi: data-header-theme / data-header-foto (lato logo) e
 * data-header-theme-menu / data-header-foto-menu (lato menu). In più
 * OGNI voce del menu (link, Preventivo, lingua, hamburger) riceve
 * data-tema e data-foto per il punto sotto il proprio centro: sopra la
 * fascia orizzontale, per esempio, una voce può stare su una foto e
 * quella accanto sullo spazio chiaro tra due foto.
 *
 * Se più elementi [data-theme] contengono il punto — una foto scura
 * dentro una sezione chiara — vince il più interno, cioè l'ultimo
 * nell'ordine del documento. Non è un IntersectionObserver (vedi la
 * storia nel giro 3): leggere qualche decina di rettangoli per frame di
 * scroll costa nulla.
 */

interface Tema {
  tema: 'dark' | 'light';
  foto: '0' | '1';
}

let elementi: HTMLElement[] = [];
let programma: () => void = () => {};

/** Rimisura il tema (per chi muove la pagina senza evento scroll: il pin orizzontale di GSAP). */
export function aggiornaTema(): void {
  programma();
}

/** Tema dell'elemento più interno che contiene il punto (x, y) del viewport. */
export function temaInPunto(x: number, y: number): Tema | null {
  let sotto: HTMLElement | null = null;
  for (const el of elementi) {
    const r = el.getBoundingClientRect();
    // Rettangolo vuoto = elemento nascosto (display: none): non conta.
    if (r.height === 0 || r.width === 0) continue;
    if (r.top <= y && r.bottom > y && r.left <= x && r.right > x) sotto = el;
  }
  if (!sotto) return null;
  return {
    tema: sotto.getAttribute('data-theme') === 'dark' ? 'dark' : 'light',
    foto: sotto.hasAttribute('data-foto') ? '1' : '0',
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

  const brand = header.querySelector<HTMLElement>('.brand');
  const toggle = header.querySelector<HTMLElement>('.menu-toggle');
  const menu = header.querySelector<HTMLElement>('.menu');
  const voci = [
    ...header.querySelectorAll<HTMLElement>('.menu-voci a, .menu-cta, .lang-switcher, .menu-toggle'),
  ];

  let inAttesa = false;

  const aggiorna = (): void => {
    inAttesa = false;

    const box = header.getBoundingClientRect();
    const linea = box.top + box.height / 2;

    // Lato logo: il centro del logo. Lato menu: l'hamburger se visibile,
    // altrimenti il centro del menu in riga.
    const xLogo = brand ? centro(brand, box.left + 80) : box.left + 80;
    const bottone = toggle && toggle.getBoundingClientRect().width > 0 ? toggle : menu;
    const xMenu = bottone ? centro(bottone, box.right - 80) : box.right - 80;

    const lato = temaInPunto(xLogo, linea);
    if (lato) {
      imposta(header, 'data-header-theme', lato.tema);
      imposta(header, 'data-header-foto', lato.foto);
    }
    const latoMenu = temaInPunto(xMenu, linea) ?? lato;
    if (latoMenu) {
      imposta(header, 'data-header-theme-menu', latoMenu.tema);
      imposta(header, 'data-header-foto-menu', latoMenu.foto);
    }

    // Voce per voce: solo quelle visibili (in riga o l'hamburger).
    for (const voce of voci) {
      const r = voce.getBoundingClientRect();
      if (r.width === 0) continue;
      const sotto = temaInPunto(r.left + r.width / 2, linea) ?? latoMenu;
      if (!sotto) continue;
      imposta(voce, 'data-tema', sotto.tema);
      imposta(voce, 'data-foto', sotto.foto);
    }
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

function centro(el: HTMLElement, altrimenti: number): number {
  const r = el.getBoundingClientRect();
  return r.width > 0 ? r.left + r.width / 2 : altrimenti;
}

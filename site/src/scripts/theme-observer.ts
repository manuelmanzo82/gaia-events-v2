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

import { SOGLIA_INCHIOSTRO, luminanzaSotto } from './luminosita';

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

/** L'elemento [data-theme] più interno che contiene il punto, oppure null. */
function piuInterno(x: number, y: number, rett: DOMRect[]): HTMLElement | null {
  let sotto: HTMLElement | null = null;
  for (let i = 0; i < elementi.length; i++) {
    const r = rett[i];
    // Rettangolo vuoto = elemento nascosto (display: none): non conta.
    if (r.height === 0 || r.width === 0) continue;
    if (r.top <= y && r.bottom > y && r.left <= x && r.right > x) sotto = elementi[i];
  }
  return sotto;
}

const lin = (c: number): number => {
  const v = c / 255;
  return v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
};

/** Luminanza del primo fondo pieno risalendo da `el` (avorio se non ce n'è). */
function luminanzaFondo(el: HTMLElement): number {
  for (let n: HTMLElement | null = el; n; n = n.parentElement) {
    const bg = getComputedStyle(n).backgroundColor;
    const m = bg.match(/[\d.]+/g);
    if (!m) continue;
    // color-mix() torna come «color(srgb 0.95 0.94 0.92 / a)», in scala 0..1.
    const scala = bg.startsWith('color(') ? 255 : 1;
    const alfa = m.length > 3 ? parseFloat(m[3]) : 1;
    if (alfa > 0.5) {
      const [r, g, b] = m.slice(0, 3).map((v) => Number(v) * scala);
      return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
    }
  }
  return 0.9;
}

/**
 * Tema per il rettangolo di un elemento (logo, voce del menu, flottante):
 * una griglia di punti su tutto il rettangolo; in ogni punto la luminanza
 * reale — i pixel della foto (con il suo velo) dove c'è una foto
 * dichiarata data-luce, il colore di fondo altrove — poi la media. Sopra
 * la soglia vince l'inchiostro, sotto l'avorio: il colore con il contrasto
 * migliore anche quando l'elemento sta a cavallo tra foto e fondo chiaro.
 */
export function temaInRett(r: { left: number; top: number; width: number; height: number }): Tema | null {
  const rett = elementi.map((e) => e.getBoundingClientRect());
  const colonne = Math.min(5, Math.max(2, Math.round(r.width / 40)));
  const righe = r.height > 60 ? 3 : 2;
  let somma = 0;
  let n = 0;
  let suFoto = false;
  let trovato = false;
  for (let i = 0; i < colonne; i++) {
    for (let j = 0; j < righe; j++) {
      const x = r.left + ((i + 0.5) / colonne) * r.width;
      const y = r.top + ((j + 0.5) / righe) * r.height;
      const el = piuInterno(x, y, rett);
      if (!el) continue;
      trovato = true;
      let l: number | null = null;
      if (el.hasAttribute('data-luce')) {
        l = luminanzaSotto(el, { left: x - 3, right: x + 3, top: y - 3, bottom: y + 3 });
        suFoto = true;
      }
      if (l === null) l = el.getAttribute('data-theme') === 'dark' ? 0.02 : luminanzaFondo(el);
      somma += l;
      n++;
    }
  }
  if (!trovato || n === 0) return null;
  const media = somma / n;
  return media > SOGLIA_INCHIOSTRO ? { tema: 'light', foto: '0' } : { tema: 'dark', foto: suFoto ? '1' : '0' };
}

/** Tema nel punto (x, y): un rettangolo minimo attorno al punto. */
export function temaInPunto(x: number, y: number, largo = 8, alto = 8): Tema | null {
  return temaInRett({ left: x - largo / 2, top: y - alto / 2, width: largo, height: alto });
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

    const rLogo = brand?.getBoundingClientRect();
    const lato = rLogo && rLogo.width > 0 ? temaInRett(rLogo) : temaInPunto(xLogo, linea);
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
      const sotto = temaInRett(r) ?? latoMenu;
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

  // Le foto senza velo vanno misurate: appena una (lazy) arriva, si rimisura.
  for (const el of elementi) {
    if (!el.hasAttribute('data-luce')) continue;
    for (const img of el.querySelectorAll('img')) img.addEventListener('load', programma);
  }

  aggiorna();
}

function centro(el: HTMLElement, altrimenti: number): number {
  const r = el.getBoundingClientRect();
  return r.width > 0 ? r.left + r.width / 2 : altrimenti;
}

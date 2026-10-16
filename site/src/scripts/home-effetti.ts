/**
 * Effetti di scroll della home (GSAP + ScrollTrigger, Lenis già avviato
 * da motion.ts). Tre pattern, reinterpretati da sarahhaywood.com:
 *
 *   [data-impila]       schede che si impilano: ogni .scheda è sticky, e
 *                       quella coperta si riduce e si attenua con lo scrub
 *   [data-orizzontale]  sezione agganciata (pin) in cui lo scroll verticale
 *                       fa scorrere il .binario in orizzontale
 *   [data-split]        testo che scorre a sinistra, foto ferma a destra che
 *                       cambia quando il blocco corrispondente entra
 *
 * Tutti e tre esistono SOLO da 64rem in su: sotto, il CSS fa scroll
 * naturale (colonna, carosello con scroll-snap, foto sopra il testo) e qui
 * non si registra niente. Con prefers-reduced-motion niente del tutto.
 * Nessuno stato iniziale nascosto: le pagine sono complete senza JS.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

import { riduciMotion } from './motion';

const DESKTOP = '(min-width: 64rem)';

function schedeImpilate(): void {
  for (const pila of document.querySelectorAll<HTMLElement>('[data-impila]')) {
    const schede = [...pila.querySelectorAll<HTMLElement>('.scheda')];

    schede.forEach((scheda, i) => {
      const successiva = schede[i + 1];
      if (!successiva) return;

      // Mentre la successiva sale a coprirla, la scheda si ritira: un po'
      // più piccola, un po' più scura. Lo scrub la lega allo scroll.
      gsap.to(scheda, {
        scale: 0.94,
        yPercent: -4,
        opacity: 0.55,
        ease: 'none',
        scrollTrigger: {
          trigger: successiva,
          start: 'top bottom',
          end: 'top top',
          scrub: true,
        },
      });
    });
  }
}

function scorrimentoOrizzontale(): void {
  for (const sezione of document.querySelectorAll<HTMLElement>('[data-orizzontale]')) {
    const binario = sezione.querySelector<HTMLElement>('.binario');
    if (!binario) continue;

    const distanza = (): number => Math.max(binario.scrollWidth - sezione.clientWidth, 0);
    if (distanza() === 0) continue;

    gsap.to(binario, {
      x: () => -distanza(),
      ease: 'none',
      scrollTrigger: {
        trigger: sezione,
        start: 'top top',
        end: () => `+=${distanza()}`,
        pin: true,
        scrub: 0.6,
        anticipatePin: 1,
        invalidateOnRefresh: true,
      },
    });
  }
}

function testoCheScorre(): void {
  for (const split of document.querySelectorAll<HTMLElement>('[data-split]')) {
    const blocchi = [...split.querySelectorAll<HTMLElement>('.split-blocco')];
    const foto = [...split.querySelectorAll<HTMLElement>('.split-foto [data-foto]')];
    if (blocchi.length === 0 || foto.length === 0) continue;

    const attiva = (indice: number): void => {
      for (const f of foto) f.classList.toggle('attiva', f.dataset.foto === String(indice));
    };

    blocchi.forEach((blocco, i) => {
      ScrollTrigger.create({
        trigger: blocco,
        start: 'top 55%',
        end: 'bottom 55%',
        onEnter: () => attiva(i),
        onEnterBack: () => attiva(i),
      });
    });

    attiva(0);
  }
}

/** Da chiamare una volta, dopo initMotion(). */
export function initHomeEffetti(): void {
  if (riduciMotion()) return;

  gsap.registerPlugin(ScrollTrigger);

  const mm = gsap.matchMedia();
  mm.add(DESKTOP, () => {
    schedeImpilate();
    scorrimentoOrizzontale();
    testoCheScorre();
  });

  // Le foto arrivano con width/height dichiarati, ma il pin orizzontale
  // misura il binario: un refresh dopo il load mette tutto a registro.
  window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
}

/**
 * Effetti di scroll della home (GSAP + ScrollTrigger, Lenis già avviato
 * da motion.ts). Due pattern, reinterpretati da sarahhaywood.com:
 *
 *   [data-impila]       schede che si impilano: ogni .scheda è sticky, e
 *                       quella coperta si riduce e si attenua con lo scrub
 *   [data-orizzontale]  sezione agganciata (pin) in cui lo scroll verticale
 *                       fa scorrere il .binario in orizzontale
 *
 * Esistono SOLO da 64rem in su: sotto, il CSS fa scroll naturale
 * (colonna, pila sticky, carosello con scroll-snap). Sotto i 64rem c'è
 * invece (giro 5) una parallasse leggera, solo transform:
 *
 *   [data-parallasse="da a"]  l'elemento scorre da yPercent `da` a `a`
 *                             mentre la sua sezione attraversa lo schermo
 *
 * Con prefers-reduced-motion niente del tutto. Nessuno stato iniziale
 * nascosto: le pagine sono complete senza JS.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

import { riduciMotion } from './motion-leggero';
import { aggiornaTema } from './theme-observer';

const DESKTOP = '(min-width: 64rem)';
const MOBILE = '(max-width: 63.98rem)';

function schedeImpilate(): void {
  for (const pila of document.querySelectorAll<HTMLElement>('[data-impila]')) {
    const schede = [...pila.querySelectorAll<HTMLElement>('.scheda')];

    schede.forEach((scheda, i) => {
      const successiva = schede[i + 1];
      if (!successiva) return;

      // Mentre la successiva le scorre sopra, la scheda si ritira: un po'
      // più piccola e più scura (il velo .scheda-ombra), ma resta visibile
      // nella fascia in alto perché ogni scheda è sticky un gradino più
      // giù della precedente. Lo scrub lega tutto allo scroll.
      const ombra = scheda.querySelector<HTMLElement>('.scheda-ombra');
      const tl = gsap.timeline({
        scrollTrigger: {
          trigger: successiva,
          start: 'top bottom',
          end: 'top top',
          scrub: true,
        },
      });
      tl.to(scheda, { scale: 0.95, ease: 'none' }, 0);
      if (ombra) tl.to(ombra, { opacity: 0.55, ease: 'none' }, 0);
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
      // Il binario scorre sotto il header senza eventi di scroll (scrub
      // con ritardo): il tema di logo e voci va rimisurato a ogni frame.
      onUpdate: aggiornaTema,
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

function parallasse(): void {
  for (const el of document.querySelectorAll<HTMLElement>('[data-parallasse]')) {
    const [da, a] = (el.dataset.parallasse ?? '0 10').split(/\s+/).map(Number);
    const sezione = el.closest<HTMLElement>('section') ?? el;
    // L'hero parte già in cima: il suo tratto è solo l'uscita verso l'alto.
    const inCima = sezione.getBoundingClientRect().top + window.scrollY < 1;
    gsap.fromTo(
      el,
      { yPercent: da },
      {
        yPercent: a,
        ease: 'none',
        scrollTrigger: {
          trigger: sezione,
          start: inCima ? 'top top' : 'top bottom',
          end: 'bottom top',
          scrub: true,
        },
      },
    );
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
  });
  mm.add(MOBILE, () => {
    parallasse();
  });

  // Le foto arrivano con width/height dichiarati, ma il pin orizzontale
  // misura il binario: un refresh dopo il load mette tutto a registro.
  window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
}

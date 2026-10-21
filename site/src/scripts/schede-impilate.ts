/**
 * Schede impilate ([data-impila], SchedeImpilate.astro), usate in home
 * (menu visivo) e in Chi sono (valori). Da chiamare solo da 64rem in su
 * e senza reduced-motion: sotto, la pila è solo sticky in CSS.
 *
 * Mentre la successiva le scorre sopra, ogni scheda si ritira: un po' più
 * piccola e più scura (il velo .scheda-ombra), ma resta visibile nella
 * fascia in alto perché ogni scheda è sticky un gradino più giù della
 * precedente. Lo scrub lega tutto allo scroll.
 */
import { gsap } from 'gsap';

export function schedeImpilate(): void {
  for (const pila of document.querySelectorAll<HTMLElement>('[data-impila]')) {
    const schede = [...pila.querySelectorAll<HTMLElement>('.scheda')];

    schede.forEach((scheda, i) => {
      const successiva = schede[i + 1];
      if (!successiva) return;

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

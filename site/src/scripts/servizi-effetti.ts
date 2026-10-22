/**
 * Effetti di scroll della pagina Servizi (GSAP + ScrollTrigger, Lenis già
 * avviato da motion.ts). Ogni sezione dichiara il suo pattern nel markup:
 *
 *   [data-parallasse="da a"]  l'elemento scorre da yPercent `da` a `a`
 *                             mentre la sua sezione attraversa lo schermo
 *   [data-apri-foto]          foto che entra con una maschera che si apre
 *                             (modulo condiviso maschere-foto.ts)
 *   [data-filo]               filetto oro verticale che si riempie mentre
 *                             i passi ([data-filo-traccia]) attraversano
 *                             lo schermo
 *
 * Con prefers-reduced-motion niente del tutto: le pagine sono complete
 * senza JS (nessuno stato iniziale nascosto nel CSS: gli stati di partenza
 * li imposta GSAP qui).
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

import { maschereFoto } from './maschere-foto';
import { parallasse } from './parallasse';
import { riduciMotion } from './motion-leggero';

function fili(): void {
  for (const filo of document.querySelectorAll<HTMLElement>('[data-filo]')) {
    const sezione = filo.closest<HTMLElement>('[data-filo-traccia]') ?? filo.closest<HTMLElement>('section') ?? filo;
    gsap.fromTo(
      filo,
      { scaleY: 0 },
      {
        scaleY: 1,
        ease: 'none',
        scrollTrigger: { trigger: sezione, start: 'top 60%', end: 'bottom 60%', scrub: true },
      },
    );
  }
}

/** Da chiamare una volta, dopo initMotion(). */
export function initServiziEffetti(): void {
  if (riduciMotion()) return;
  gsap.registerPlugin(ScrollTrigger);
  parallasse();
  maschereFoto();
  fili();
  window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
}

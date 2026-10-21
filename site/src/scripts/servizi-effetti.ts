/**
 * Effetti di scroll della pagina Servizi (GSAP + ScrollTrigger, Lenis già
 * avviato da motion.ts). Ogni sezione dichiara il suo pattern nel markup:
 *
 *   [data-parallasse="da a"]  l'elemento scorre da yPercent `da` a `a`
 *                             mentre la sua sezione attraversa lo schermo
 *   [data-apri]               foto che entra con una maschera che si apre
 *                             (clip-path dal centro verso i bordi) mentre
 *                             la foto interna torna in scala; scrub legato
 *                             allo scroll. "piccola" = un po' più tardi
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

import { riduciMotion } from './motion-leggero';

function parallasse(): void {
  for (const el of document.querySelectorAll<HTMLElement>('[data-parallasse]')) {
    const [da, a] = (el.dataset.parallasse ?? '0 10').split(/\s+/).map(Number);
    const sezione = el.closest<HTMLElement>('section') ?? el;
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

function maschere(): void {
  for (const el of document.querySelectorAll<HTMLElement>('[data-apri]')) {
    const piccola = el.dataset.apri === 'piccola';
    const img = el.querySelector<HTMLElement>('img');
    const tl = gsap.timeline({
      scrollTrigger: {
        trigger: el,
        start: piccola ? 'top 80%' : 'top 92%',
        end: piccola ? 'top 45%' : 'top 50%',
        scrub: 0.8,
      },
    });
    tl.fromTo(
      el,
      { clipPath: 'inset(18% 22% 18% 22% round 16px)' },
      { clipPath: 'inset(0% 0% 0% 0% round 16px)', ease: 'power2.out' },
      0,
    );
    if (img) tl.fromTo(img, { scale: 1.18 }, { scale: 1, ease: 'power2.out' }, 0);
  }
}

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
  maschere();
  fili();
  window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
}

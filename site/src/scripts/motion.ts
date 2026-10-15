/**
 * Motion di Gaia Events: Lenis per lo scroll morbido, GSAP + ScrollTrigger
 * per i reveal. Lento ed elegante, mai invadente.
 *
 * Uso nei template, via data attribute:
 *   data-reveal="righe"     testo che sale riga per riga (SplitText)
 *   data-reveal="maschera"  immagine o blocco scoperto dal basso (clip-path)
 *   data-reveal="alto"      fade + leggera salita dell'intero elemento
 *   data-reveal-gruppo      contenitore: i figli [data-reveal] partono in stagger
 *
 * Con prefers-reduced-motion TUTTO è spento: niente Lenis, niente reveal,
 * i contenuti sono subito visibili (nessuno stato iniziale nascosto: gli
 * stati di partenza li imposta GSAP qui, non il CSS).
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import Lenis from 'lenis';

const DURATA = 1.1;
const EASE = 'power3.out';

export function riduciMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/** Avvia Lenis e lo aggancia al ticker di GSAP. */
export function initLenis(): Lenis | null {
  if (riduciMotion()) return null;

  const lenis = new Lenis({ duration: 1.15 });

  lenis.on('scroll', ScrollTrigger.update);
  gsap.ticker.add((time) => {
    lenis.raf(time * 1000);
  });
  gsap.ticker.lagSmoothing(0);

  return lenis;
}

/** Registra i reveal su tutti gli elementi [data-reveal] della pagina. */
export function initReveals(): void {
  if (riduciMotion()) return;

  gsap.registerPlugin(ScrollTrigger, SplitText);

  for (const el of document.querySelectorAll<HTMLElement>('[data-reveal="righe"]')) {
    const split = SplitText.create(el, { type: 'lines', mask: 'lines' });
    gsap.from(split.lines, {
      yPercent: 110,
      opacity: 0,
      duration: DURATA,
      ease: EASE,
      stagger: 0.09,
      scrollTrigger: { trigger: el, start: 'top 85%', once: true },
    });
  }

  for (const el of document.querySelectorAll<HTMLElement>('[data-reveal="maschera"]')) {
    gsap.fromTo(
      el,
      { clipPath: 'inset(100% 0 0 0)' },
      {
        clipPath: 'inset(0% 0 0 0)',
        duration: DURATA + 0.2,
        ease: EASE,
        scrollTrigger: { trigger: el, start: 'top 85%', once: true },
      },
    );
  }

  for (const el of document.querySelectorAll<HTMLElement>('[data-reveal="alto"]')) {
    gsap.from(el, {
      y: 36,
      opacity: 0,
      duration: DURATA,
      ease: EASE,
      scrollTrigger: { trigger: el, start: 'top 88%', once: true },
    });
  }
}

/** Avvia tutto: da chiamare una volta per pagina. */
export function initMotion(): void {
  initLenis();
  initReveals();
}

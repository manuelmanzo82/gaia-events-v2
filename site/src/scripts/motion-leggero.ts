/**
 * La parte leggera del motion, senza GSAP né Lenis: chi ne ha bisogno
 * subito (torna-su, fascia, flottanti) la importa da qui, così il pacchetto
 * delle animazioni (motion.ts, GSAP, Lenis) si carica solo dopo il primo
 * paint (avviaMotionDopoIlPaint).
 */

export function riduciMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/** Quel che serve di Lenis a chi scorre la pagina (torna-su). */
interface Scorrevole {
  scrollTo(destinazione: number, opzioni?: { duration?: number }): void;
}

let lenisAttivo: Scorrevole | null = null;

export function impostaLenis(lenis: Scorrevole | null): void {
  lenisAttivo = lenis;
}

/** L'istanza di Lenis della pagina, se lo scroll morbido è già acceso. */
export function getLenis(): Scorrevole | null {
  return lenisAttivo;
}

/**
 * Carica e avvia motion.ts dopo il load, quando il browser è libero: GSAP,
 * Lenis e i reveal non competono con il primo paint né con l'LCP. A fine
 * avvio parte l'evento `gaia:motion-pronta` (anche con reduced-motion,
 * così chi aspetta non resta appeso).
 */
export function avviaMotionDopoIlPaint(): void {
  const avvia = (): void => {
    void import('./motion').then(({ initMotion }) => {
      initMotion();
      document.dispatchEvent(new CustomEvent('gaia:motion-pronta'));
    });
  };
  const quandoLibero = (): void => {
    // Safari non ha requestIdleCallback: lì basta un giro di event loop.
    if (typeof window.requestIdleCallback === 'function') {
      window.requestIdleCallback(avvia, { timeout: 800 });
    } else {
      setTimeout(avvia, 1);
    }
  };
  if (document.readyState === 'complete') quandoLibero();
  else window.addEventListener('load', quandoLibero, { once: true });
}

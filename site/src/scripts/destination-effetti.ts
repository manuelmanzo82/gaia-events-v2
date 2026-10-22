/**
 * Effetti di scroll della pagina Destination wedding (GSAP + ScrollTrigger,
 * Lenis già avviato da motion.ts). Riusa la parallasse condivisa; gli
 * effetti nuovi della pagina (mappa, conto alla rovescia, striscia degli
 * ospiti) vivono nei loro componenti e funzionano anche senza GSAP.
 *
 * Con prefers-reduced-motion niente del tutto: la pagina è completa senza
 * JS (nessuno stato iniziale nascosto nel CSS).
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

import { riduciMotion } from './motion-leggero';
import { parallasse } from './parallasse';

/** Da chiamare una volta, dopo initMotion(). */
export function initDestinationEffetti(): void {
  if (riduciMotion()) return;
  gsap.registerPlugin(ScrollTrigger);
  parallasse();
  window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
}

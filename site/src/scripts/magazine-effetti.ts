/**
 * Effetti del Magazine (indice e articoli), dopo il motion di base.
 * Riusa i moduli condivisi: maschera delle foto e parallasse. Gli effetti
 * nuovi (barra di lettura, indice dei capitoli, video) vivono nei loro
 * componenti e funzionano anche senza GSAP.
 * Con prefers-reduced-motion niente del tutto.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

import { maschereFoto } from './maschere-foto';
import { riduciMotion } from './motion-leggero';
import { parallasse } from './parallasse';

export function initMagazineEffetti(): void {
  if (riduciMotion()) return;
  gsap.registerPlugin(ScrollTrigger);
  maschereFoto();
  parallasse();
  window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
}

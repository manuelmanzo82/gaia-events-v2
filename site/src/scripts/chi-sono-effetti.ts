/**
 * Effetti di scroll della pagina Chi sono (GSAP + ScrollTrigger, Lenis
 * già avviato da motion.ts). Ogni sezione ha il suo pattern, dichiarato
 * nel markup con un data attribute:
 *
 *   [data-parallasse="da a"]  la foto scorre da yPercent `da` a `a`
 *                             mentre la sua sezione attraversa lo schermo
 *   [data-parole]             testo rivelato parola per parola allo scroll
 *                             (opacità 0,15 → 1, SplitText + scrub)
 *
 * Pin e scrub esistono solo da 64rem in su; sotto, scroll naturale.
 * Con prefers-reduced-motion niente del tutto: le pagine sono complete
 * senza JS (nessuno stato iniziale nascosto nel CSS).
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';

import { riduciMotion } from './motion-leggero';

const DESKTOP = '(min-width: 64rem)';

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

function parolePerParola(): void {
  for (const el of document.querySelectorAll<HTMLElement>('[data-parole]')) {
    // aria: 'none' come in motion.ts (niente aria-label sui <p>).
    const split = SplitText.create(el, { type: 'words', wordsClass: 'parola', aria: 'none' });
    gsap.fromTo(
      split.words,
      { opacity: 0.15 },
      {
        opacity: 1,
        ease: 'none',
        stagger: 0.04,
        scrollTrigger: {
          trigger: el,
          start: 'top 78%',
          end: 'bottom 45%',
          scrub: true,
        },
      },
    );
  }
}

/** Da chiamare una volta, dopo initMotion(). */
export function initChiSonoEffetti(): void {
  if (riduciMotion()) return;

  gsap.registerPlugin(ScrollTrigger, SplitText);

  parallasse();
  parolePerParola();

  const mm = gsap.matchMedia();
  mm.add(DESKTOP, () => {
    /* pin e scrub delle sezioni successive */
  });

  window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
}

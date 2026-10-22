/**
 * Parallasse di scroll condivisa (Servizi, Destination wedding):
 *
 *   [data-parallasse="da a"]  l'elemento scorre da yPercent `da` a `a`
 *                             mentre la sua sezione attraversa lo schermo
 *                             (dalla cima, se la sezione apre la pagina)
 *
 * Nessuno stato iniziale nel CSS. ScrollTrigger va registrato da chi chiama.
 */
import { gsap } from 'gsap';

export function parallasse(): void {
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

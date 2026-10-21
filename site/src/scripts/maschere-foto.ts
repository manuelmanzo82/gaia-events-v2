/**
 * Entrata delle foto con una maschera che si apre (Servizi: capitoli e
 * «Non solo matrimoni»; Chi sono: la coppia di foto della voce).
 *
 *   [data-apri-foto]            la cornice parte ritagliata al centro
 *                               (clip-path inset) e si apre fino ai bordi
 *                               mentre la foto interna torna da 1,18 a 1;
 *                               scrub legato allo scroll
 *   [data-apri-foto="piccola"]  stesso effetto, un po' più tardi: la
 *                               piccola si apre dopo la grande
 *
 * L'arrotondamento della maschera è quello della cornice (border-radius
 * letto dal CSS). Nessuno stato iniziale nel CSS: senza JS o con
 * reduced-motion (le pagine non chiamano gli effetti) le foto sono già
 * aperte. ScrollTrigger va registrato da chi chiama.
 */
import { gsap } from 'gsap';

export function maschereFoto(): void {
  for (const el of document.querySelectorAll<HTMLElement>('[data-apri-foto]')) {
    const piccola = el.dataset.apriFoto === 'piccola';
    const img = el.querySelector<HTMLElement>('img');
    const raggio = getComputedStyle(el).borderTopLeftRadius || '0px';
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
      { clipPath: `inset(18% 22% 18% 22% round ${raggio})` },
      { clipPath: `inset(0% 0% 0% 0% round ${raggio})`, ease: 'power2.out' },
      0,
    );
    if (img) tl.fromTo(img, { scale: 1.18 }, { scale: 1, ease: 'power2.out' }, 0);
  }
}

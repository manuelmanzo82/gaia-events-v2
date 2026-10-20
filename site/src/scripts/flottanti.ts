/**
 * Bottoni flottanti in basso a destra (torna-su, WhatsApp): la parte in
 * comune. Il colore lo decide ciò che il bottone ha sotto il proprio
 * rettangolo (temaInRett di theme-observer.ts): avorio sopra le foto e le
 * sezioni scure, inchiostro sui fondi chiari.
 *
 * Visibilità, per opzione:
 *  - dopoHero: compare solo dopo una schermata di scroll (torna-su);
 *    altrimenti è visibile subito (WhatsApp);
 *  - viaSulFooter: classe `coperto` mentre il footer è in vista, per non
 *    finire sopra i suoi bottoni (torna-su);
 *  - dopoIntro: se l'intro a velo è attiva, compare solo alla sua fine
 *    (evento gaia:intro-fine, con una rete di sicurezza a 9 s).
 */
import { temaInRett } from './theme-observer';

interface Opzioni {
  dopoHero?: boolean;
  viaSulFooter?: boolean;
  dopoIntro?: boolean;
  /** Dove misurare il colore (default: il bottone intero). */
  punto?: HTMLElement | null;
}

export function initFlottante(el: HTMLElement, opzioni: Opzioni = {}): void {
  const header = document.querySelector<HTMLElement>('.site-header');

  let introFinita = true;
  if (opzioni.dopoIntro) {
    introFinita = !(
      document.documentElement.classList.contains('con-intro') &&
      document.getElementById('intro-velo')
    );
  }

  const aggiorna = (): void => {
    el.hidden = false;
    const dopoHero = !opzioni.dopoHero || window.scrollY > window.innerHeight;
    el.classList.toggle('visibile', introFinita && dopoHero);
    const r = (opzioni.punto ?? el).getBoundingClientRect();
    const sotto = temaInRett(r);
    el.dataset.tema = sotto?.tema ?? header?.dataset.headerTheme ?? 'light';
  };

  if (!introFinita) {
    const fine = (): void => {
      if (introFinita) return;
      introFinita = true;
      aggiorna();
    };
    document.addEventListener('gaia:intro-fine', fine, { once: true });
    window.setTimeout(fine, 9000);
  }

  window.addEventListener('scroll', aggiorna, { passive: true });
  window.addEventListener('resize', aggiorna, { passive: true });
  window.addEventListener('load', aggiorna);
  aggiorna();

  const footer = document.querySelector('footer');
  if (opzioni.viaSulFooter && footer && 'IntersectionObserver' in window) {
    new IntersectionObserver(
      ([voce]) => el.classList.toggle('coperto', !!voce?.isIntersecting),
      { threshold: 0 },
    ).observe(footer);
  }
}

/**
 * Bottoni flottanti in basso a destra (torna-su, WhatsApp): la parte in
 * comune. Compaiono dopo una schermata di scroll (cioè dopo l'hero), si
 * tolgono di mezzo mentre il footer è in vista (classe `coperto`: mai
 * sopra un bottone) e prendono il colore da ciò che hanno sotto il
 * proprio centro (temaInPunto di theme-observer.ts): avorio sopra le foto
 * e le sezioni scure, inchiostro sui fondi chiari.
 */
import { temaInPunto } from './theme-observer';

export function initFlottante(el: HTMLElement): void {
  const header = document.querySelector<HTMLElement>('.site-header');

  const aggiorna = (): void => {
    el.hidden = false;
    el.classList.toggle('visibile', window.scrollY > window.innerHeight);
    const r = el.getBoundingClientRect();
    const sotto = temaInPunto(r.left + r.width / 2, r.top + r.height / 2);
    el.dataset.tema = sotto?.tema ?? header?.dataset.headerTheme ?? 'light';
  };

  window.addEventListener('scroll', aggiorna, { passive: true });
  window.addEventListener('resize', aggiorna, { passive: true });
  window.addEventListener('load', aggiorna);
  aggiorna();

  const footer = document.querySelector('footer');
  if (footer && 'IntersectionObserver' in window) {
    new IntersectionObserver(
      ([voce]) => el.classList.toggle('coperto', !!voce?.isIntersecting),
      { threshold: 0 },
    ).observe(footer);
  }
}

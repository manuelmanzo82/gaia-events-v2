/**
 * Bottoni flottanti in basso a destra (torna-su, WhatsApp): la parte in
 * comune. Il colore segue la stessa regola del header (theme-observer.ts:
 * il data-theme della sezione), misurata però sotto il bottone stesso, che
 * sta in fondo allo schermo: avorio sopra le sezioni con foto e sul
 * footer, inchiostro sui fondi chiari. Quando sotto il bottone non c'è
 * nessuna sezione (margini) vale il colore del header.
 *
 * Visibilità, per opzione:
 *  - dopoHero: compare solo dopo una schermata di scroll (torna-su);
 *    altrimenti è visibile subito (WhatsApp);
 *  - dopoIntro: se l'intro a velo è attiva, compare solo alla sua fine
 *    (evento gaia:intro-fine, con una rete di sicurezza a 9 s).
 */
import { sezioneInPunto, temaDi } from './theme-observer';

interface Opzioni {
  dopoHero?: boolean;
  dopoIntro?: boolean;
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

    const r = el.getBoundingClientRect();
    const sotto = temaDi(sezioneInPunto(r.left + r.width / 2, r.top + r.height / 2));
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
  if (header) {
    new MutationObserver(aggiorna).observe(header, { attributes: true, attributeFilter: ['data-header-theme'] });
  }
  aggiorna();
}

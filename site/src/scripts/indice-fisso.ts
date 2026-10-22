/**
 * Indice fisso con la voce corrente evidenziata (Servizi: capitoli;
 * Magazine: titoli H2 dell'articolo).
 *
 *   [data-indice]     il <nav> dell'indice
 *   [data-voce=x]     i link dell'indice
 *   [data-capitolo=x] le sezioni (o i titoli) seguite
 *
 * La voce corrente è l'ultima sezione il cui inizio ha superato la soglia
 * (frazione dell'altezza dello schermo dall'alto): funziona anche dopo un
 * salto (ancora, tasto Fine, ricarica a metà pagina). Prima della prima
 * sezione nessuna voce è accesa. aria-current="true" sulla voce.
 */
export function indiceFisso(indice: HTMLElement, sezioni: HTMLElement[], soglia = 0.5): void {
  if (!sezioni.length) return;
  const voci = new Map(
    [...indice.querySelectorAll<HTMLAnchorElement>('[data-voce]')].map((a) => [a.dataset.voce ?? '', a]),
  );
  let corrente: string | null = null;
  let inAttesa = false;

  const aggiorna = (): void => {
    inAttesa = false;
    const limite = window.innerHeight * soglia;
    let scelta: string | null = null;
    for (const s of sezioni) {
      if (s.getBoundingClientRect().top <= limite) scelta = s.dataset.capitolo ?? null;
      else break;
    }
    if (scelta === corrente) return;
    corrente = scelta;
    voci.forEach((a, k) => {
      if (k === scelta) a.setAttribute('aria-current', 'true');
      else a.removeAttribute('aria-current');
    });
  };

  const chiedi = (): void => {
    if (!inAttesa) {
      inAttesa = true;
      requestAnimationFrame(aggiorna);
    }
  };
  window.addEventListener('scroll', chiedi, { passive: true });
  window.addEventListener('resize', chiedi);
  aggiorna();
}

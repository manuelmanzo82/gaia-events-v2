/**
 * Indice fisso con la voce corrente evidenziata (Servizi: capitoli;
 * Magazine: titoli H2 dell'articolo).
 *
 *   [data-indice]    il <nav> dell'indice
 *   [data-voce=x]    i link dell'indice
 *   [data-capitolo=x] le sezioni (o i titoli) seguite
 *
 * La voce corrente è quella della sezione che attraversa la fascia
 * indicata da rootMargin (di default il centro dello schermo); resta
 * accesa finché non ne arriva un'altra. aria-current="true" sulla voce.
 */
export function indiceFisso(indice: HTMLElement, sezioni: HTMLElement[], rootMargin = '-45% 0px -50% 0px'): void {
  if (!sezioni.length || !('IntersectionObserver' in window)) return;
  const voci = new Map(
    [...indice.querySelectorAll<HTMLAnchorElement>('[data-voce]')].map((a) => [a.dataset.voce ?? '', a]),
  );
  const io = new IntersectionObserver(
    (righe) => {
      for (const r of righe) {
        if (!r.isIntersecting) continue;
        const chiave = (r.target as HTMLElement).dataset.capitolo ?? '';
        voci.forEach((a, k) => {
          if (k === chiave) a.setAttribute('aria-current', 'true');
          else a.removeAttribute('aria-current');
        });
      }
    },
    { rootMargin, threshold: 0 },
  );
  for (const s of sezioni) io.observe(s);
}

/**
 * Il header (e quindi il logo, che usa currentColor) cambia colore in base
 * alla sezione che ha sotto: ogni sezione dichiara data-theme="dark|light"
 * e qui si aggiorna data-header-theme sul <header>.
 *
 * Il CSS fa il resto:
 *   [data-header-theme='dark']  → testo avorio (sta sopra una sezione scura)
 *   [data-header-theme='light'] → testo inchiostro
 *
 * Regola: decide la sezione che passa sotto la LINEA a metà altezza del
 * header, misurata ogni volta (il header si riduce dopo lo scroll). Se più
 * sezioni la attraversano — una scheda scura dentro una sezione chiara —
 * vince la più interna, cioè l'ultima nell'ordine del documento.
 *
 * Non è un IntersectionObserver: la striscia di un IO va fissata alla
 * creazione, quindi restava alta quanto il header grande anche col logo
 * ridotto, e la regola «chi la tocca vince» faceva scattare il tema scuro
 * appena un pixel dell'hero compariva in cima, risalendo, con il logo
 * avorio sopra una sezione avorio. Con le schede annidate, poi, l'uscita
 * della scheda non riportava il tema della sezione che la contiene.
 * Leggere una dozzina di rettangoli per frame di scroll costa nulla.
 */
export function initThemeObserver(header: HTMLElement): void {
  const sezioni = [...document.querySelectorAll<HTMLElement>('[data-theme]')].filter(
    // Il header stesso o elementi dentro di lui non contano.
    (sezione) => !header.contains(sezione),
  );
  // Il torna-su (e chiunque altro) legge lo stesso tema dal header.
  if (sezioni.length === 0) return;

  let inAttesa = false;

  const aggiorna = (): void => {
    inAttesa = false;

    const box = header.getBoundingClientRect();
    const linea = box.top + box.height / 2;

    let sotto: HTMLElement | null = null;
    for (const sezione of sezioni) {
      const r = sezione.getBoundingClientRect();
      // Rettangolo vuoto = elemento nascosto (display: none): non conta.
      if (r.height === 0) continue;
      if (r.top <= linea && r.bottom > linea) sotto = sezione;
    }
    if (!sotto) return;

    const tema = sotto.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    // Sopra una foto (hero, schede, destination) il header aggiunge
    // un'ombra testuale morbidissima: la sezione lo dichiara con data-foto.
    const foto = sotto.hasAttribute('data-foto') ? '1' : '0';

    if (header.getAttribute('data-header-theme') !== tema) {
      header.setAttribute('data-header-theme', tema);
    }
    if (header.getAttribute('data-header-foto') !== foto) {
      header.setAttribute('data-header-foto', foto);
    }
  };

  const programma = (): void => {
    if (inAttesa) return;
    inAttesa = true;
    requestAnimationFrame(aggiorna);
  };

  // Lenis muove lo scroll nativo, quindi l'evento scroll arriva comunque;
  // resize e load coprono rotazione, barra degli indirizzi mobile e font.
  window.addEventListener('scroll', programma, { passive: true });
  window.addEventListener('resize', programma, { passive: true });
  window.addEventListener('load', programma);
  // Il header cambia altezza (logo ridotto): la linea va rimisurata.
  if ('ResizeObserver' in window) new ResizeObserver(programma).observe(header);

  aggiorna();
}

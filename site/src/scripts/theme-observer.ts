/**
 * Il header (e quindi il logo, che usa currentColor) cambia colore in base
 * alla sezione che ha sotto: ogni sezione dichiara data-theme="dark|light"
 * e un IntersectionObserver aggiorna data-header-theme sul <header>.
 *
 * Il CSS fa il resto:
 *   [data-header-theme='dark']  → testo avorio (sta sopra una sezione scura)
 *   [data-header-theme='light'] → testo inchiostro
 */
export function initThemeObserver(header: HTMLElement): void {
  const sezioni = document.querySelectorAll<HTMLElement>('[data-theme]');
  if (sezioni.length === 0) return;

  const altezza = () => header.offsetHeight || 80;

  const osservatore = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        const tema = entry.target.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        header.setAttribute('data-header-theme', tema);
      }
    },
    {
      // Una striscia alta quanto il header, in cima alla viewport: la
      // sezione che la tocca decide il tema.
      rootMargin: `0px 0px -${Math.max(window.innerHeight - altezza(), 0)}px 0px`,
      threshold: 0,
    },
  );

  for (const sezione of sezioni) osservatore.observe(sezione);
}

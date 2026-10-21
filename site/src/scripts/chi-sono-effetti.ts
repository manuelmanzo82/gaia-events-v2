/**
 * Effetti di scroll della pagina Chi sono (GSAP + ScrollTrigger, Lenis
 * già avviato da motion.ts). Ogni sezione dichiara il suo pattern nel
 * markup con un data attribute:
 *
 *   [data-parallasse="da a"]  l'elemento scorre da yPercent `da` a `a`
 *                             mentre la sua sezione attraversa lo schermo
 *   [data-apri-foto]          foto della voce che entrano con una maschera
 *                             che si apre (maschere-foto.ts, come Servizi)
 *   [data-contatore="150+"]   numero che conta da 0 al valore al primo
 *                             ingresso (prefisso/suffisso restano)
 *   [data-timeline]           il percorso: da 64rem sezione agganciata con
 *                             il .binario in orizzontale, filetto oro che
 *                             si disegna, anno attivo pieno, foto in
 *                             parallasse; sotto, la linea verticale si
 *                             riempie con lo scroll e le tappe si accendono
 *   [data-impila]             i valori come schede impilate, stesso scrub
 *                             del menu visivo della home (schede-impilate.ts)
 *
 * Pin e scrub esistono solo da 64rem in su; sotto, scroll naturale. Con
 * prefers-reduced-motion niente del tutto: le pagine sono complete senza
 * JS (nessuno stato iniziale nascosto nel CSS).
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

import { maschereFoto } from './maschere-foto';
import { riduciMotion } from './motion-leggero';
import { schedeImpilate } from './schede-impilate';
import { aggiornaTema } from './theme-observer';

const DESKTOP = '(min-width: 64rem)';
const MOBILE = '(max-width: 63.98rem)';

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

function contatori(): void {
  for (const el of document.querySelectorAll<HTMLElement>('[data-contatore]')) {
    const testo = el.dataset.contatore ?? el.textContent ?? '';
    const m = testo.match(/^([^\d]*)(\d[\d.,]*)(.*)$/);
    if (!m) continue;
    const [, prefisso, cifre, suffisso] = m;
    const separatore = cifre.includes('.') ? '.' : '';
    const valore = Number(cifre.replace(/[.,]/g, ''));
    if (!Number.isFinite(valore)) continue;
    const stato = { n: 0 };
    gsap.to(stato, {
      n: valore,
      duration: 1.6,
      ease: 'power2.out',
      onUpdate: () => {
        const intero = Math.round(stato.n);
        const resa = separatore ? intero.toLocaleString('it-IT') : String(intero);
        el.textContent = `${prefisso}${resa}${suffisso}`;
      },
      scrollTrigger: { trigger: el, start: 'top 85%', once: true },
    });
  }
}

/** Accende la tappa i (anno pieno, punto pieno) e spegne le altre. */
function accendiTappa(tappe: HTMLElement[], i: number): void {
  tappe.forEach((t, k) => t.classList.toggle('attiva', k === i));
}

function timelineDesktop(): void {
  for (const sezione of document.querySelectorAll<HTMLElement>('[data-timeline]')) {
    const binario = sezione.querySelector<HTMLElement>('.binario');
    const filo = sezione.querySelector<HTMLElement>('.percorso-filo');
    const pieno = sezione.querySelector<HTMLElement>('.percorso-filo-pieno');
    const tappe = [...sezione.querySelectorAll<HTMLElement>('.tappa')];
    if (!binario || tappe.length === 0) continue;

    const distanza = (): number => Math.max(binario.scrollWidth - sezione.clientWidth, 0);
    if (distanza() === 0) continue;

    // Il filetto è largo quanto il binario e si muove con lui.
    if (filo) filo.style.width = `${binario.scrollWidth}px`;

    const scorrimento = gsap.to([binario, filo].filter(Boolean), {
      x: () => -distanza(),
      ease: 'none',
      scrollTrigger: {
        trigger: sezione,
        start: 'top top',
        // Durata del pin proporzionale alla lunghezza del binario (un po'
        // più del tratto percorso, così si legge ogni tappa); scrub con
        // una leggera inerzia.
        end: () => `+=${Math.round(distanza() * 1.25)}`,
        pin: true,
        scrub: 1.2,
        anticipatePin: 1,
        invalidateOnRefresh: true,
        onUpdate: (self) => {
          aggiornaTema();
          // Filetto che si disegna e anno attivo: entrambi dal progresso.
          if (pieno) pieno.style.transform = `scaleX(${Math.min(1, 0.12 + self.progress * 0.92)})`;
          accendiTappa(tappe, Math.min(tappe.length - 1, Math.round(self.progress * (tappe.length - 1))));
        },
      },
    });

    // Parallasse orizzontale di ogni foto dentro la sua cornice, legata
    // allo scorrimento del binario (containerAnimation).
    for (const tappa of tappe) {
      const img = tappa.querySelector<HTMLElement>('.tappa-img');
      if (!img) continue;
      gsap.fromTo(
        img,
        { xPercent: -9 },
        {
          xPercent: 9,
          ease: 'none',
          scrollTrigger: {
            trigger: tappa,
            containerAnimation: scorrimento,
            start: 'left right',
            end: 'right left',
            scrub: true,
          },
        },
      );
    }
  }
}

function timelineMobile(): void {
  for (const sezione of document.querySelectorAll<HTMLElement>('[data-timeline]')) {
    const pieno = sezione.querySelector<HTMLElement>('.percorso-filo-pieno');
    const tappe = [...sezione.querySelectorAll<HTMLElement>('.tappa')];
    if (pieno) {
      gsap.fromTo(
        pieno,
        { scaleY: 0 },
        {
          scaleY: 1,
          ease: 'none',
          scrollTrigger: { trigger: sezione, start: 'top 70%', end: 'bottom 70%', scrub: true },
        },
      );
    }
    // La tappa che passa il centro dello schermo si accende.
    for (const [i, tappa] of tappe.entries()) {
      ScrollTrigger.create({
        trigger: tappa,
        start: 'top 60%',
        end: 'bottom 40%',
        onEnter: () => accendiTappa(tappe, i),
        onEnterBack: () => accendiTappa(tappe, i),
      });
      const img = tappa.querySelector<HTMLElement>('.tappa-img');
      if (img) {
        gsap.fromTo(
          img,
          { yPercent: -5 },
          {
            yPercent: 5,
            ease: 'none',
            scrollTrigger: { trigger: tappa, start: 'top bottom', end: 'bottom top', scrub: true },
          },
        );
      }
    }
  }
}

/** Da chiamare una volta, dopo initMotion(). */
export function initChiSonoEffetti(): void {
  if (riduciMotion()) return;

  gsap.registerPlugin(ScrollTrigger);

  // La classe dice al CSS che pin e scrub ci sono: senza (JS spento,
  // reduced-motion) la striscia del percorso scorre a mano.
  document.documentElement.classList.add('motion-attivo');

  parallasse();
  maschereFoto();
  contatori();

  const mm = gsap.matchMedia();
  mm.add(DESKTOP, () => {
    timelineDesktop();
    schedeImpilate();
  });
  mm.add(MOBILE, () => {
    timelineMobile();
  });

  window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
}

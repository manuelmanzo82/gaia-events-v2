/**
 * Costanti della home condivise fra HomePage e i componenti di sezione.
 */

/** Quante schede nel menu visivo: le altre foto vanno nella fascia orizzontale. */
export const SCHEDE_IMPILATE = 5;

/**
 * Altezza di riferimento (px CSS) della fascia orizzontale su desktop:
 * 65vh di uno schermo da 1080, e anche il tetto in CSS (min(65svh, …)),
 * così una foto che regge questa altezza — larghezza resa = altezza ×
 * rapporto ≤ maxWidth — la regge su qualunque schermo.
 */
export const ALTEZZA_FASCIA = 702;

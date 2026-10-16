/**
 * Costanti della home condivise fra HomePage e i componenti di sezione.
 */

/**
 * Larghezza massima (px CSS) di una scheda impilata degli highlights.
 * Nelle schede entrano solo foto orizzontali con maxWidth >= questo valore,
 * cosi' l'object-fit: cover non ingrandisce mai una foto oltre il suo limite.
 */
export const LIMITE_SCHEDA = 992;

/** Quante schede impilate: le altre foto vanno nella fascia orizzontale. */
export const SCHEDE_IMPILATE = 5;

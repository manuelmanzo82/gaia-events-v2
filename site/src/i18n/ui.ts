import type { Lang } from '../config/site';

/**
 * Stringhe di interfaccia (etichette, navigazione, bottoni).
 * I CONTENUTI editoriali non stanno qui: arrivano dal back office via JSON.
 */
export const UI = {
  'nav.home': { it: 'Home', en: 'Home' },
  'nav.about': { it: 'Chi sono', en: 'About' },
  'nav.services': { it: 'Servizi', en: 'Services' },
  'nav.portfolio': { it: 'Portfolio', en: 'Portfolio' },
  'nav.reviews': { it: 'Dicono di me', en: 'Reviews' },
  'nav.blog': { it: 'Blog', en: 'Blog' },
  'nav.faq': { it: 'FAQ', en: 'FAQ' },
  'nav.quote': { it: 'Preventivo', en: 'Get a quote' },
  'nav.contact': { it: 'Contatti', en: 'Contact' },
  'nav.destinationWedding': { it: 'Destination wedding', en: 'Destination wedding' },
  'nav.menu': { it: 'Menu', en: 'Menu' },
  'nav.close': { it: 'Chiudi', en: 'Close' },
  'nav.skipToContent': { it: 'Vai al contenuto', en: 'Skip to content' },

  'lang.switch': { it: 'English', en: 'Italiano' },
  'lang.switchLabel': { it: 'Cambia lingua', en: 'Change language' },

  'cta.quote': { it: 'Richiedi un preventivo', en: 'Request a quote' },
  'cta.contact': { it: 'Contattami', en: 'Get in touch' },
  'cta.whatsapp': { it: 'Scrivimi su WhatsApp', en: 'Message me on WhatsApp' },
  'cta.discover': { it: 'Scopri di più', en: 'Discover more' },
  'cta.allEvents': { it: 'Tutti gli eventi', en: 'All events' },
  'cta.readMore': { it: 'Leggi l’articolo', en: 'Read the article' },
  'cta.back': { it: 'Torna indietro', en: 'Go back' },
  'cta.home': { it: 'Torna alla home', en: 'Back to home' },

  'common.breadcrumb': { it: 'Percorso di navigazione', en: 'Breadcrumb' },
  'common.readingTime': { it: 'min di lettura', en: 'min read' },
  'common.publishedOn': { it: 'Pubblicato il', en: 'Published on' },
  'common.location': { it: 'Location', en: 'Location' },
  'common.date': { it: 'Data', en: 'Date' },
  'common.category': { it: 'Categoria', en: 'Category' },
  'common.gallery': { it: 'Galleria', en: 'Gallery' },
  'common.empty': { it: 'Nessun contenuto disponibile.', en: 'No content available.' },
  'common.placeholder': {
    it: 'Sezione segnaposto: il design arriva nella fase successiva.',
    en: 'Placeholder section: the design comes in a later phase.',
  },

  'form.name': { it: 'Nome e cognome', en: 'Full name' },
  'form.email': { it: 'Email', en: 'Email' },
  'form.phone': { it: 'Telefono', en: 'Phone' },
  'form.message': { it: 'Messaggio', en: 'Message' },
  'form.eventDate': { it: 'Data dell’evento', en: 'Event date' },
  'form.eventType': { it: 'Tipo di evento', en: 'Event type' },
  'form.guests': { it: 'Numero di invitati', en: 'Number of guests' },
  'form.privacy': {
    it: 'Ho letto e accetto l’informativa privacy',
    en: 'I have read and accept the privacy policy',
  },
  'form.submit': { it: 'Invia richiesta', en: 'Send request' },
  'form.sending': { it: 'Invio in corso…', en: 'Sending…' },
  'form.success': {
    it: 'Grazie! Ho ricevuto la tua richiesta e ti risponderò presto.',
    en: 'Thank you! I received your request and will reply soon.',
  },
  'form.error': {
    it: 'Qualcosa è andato storto. Riprova o scrivimi su WhatsApp.',
    en: 'Something went wrong. Please try again or message me on WhatsApp.',
  },
  'form.required': { it: 'Campo obbligatorio', en: 'Required field' },
  'form.invalidEmail': { it: 'Indirizzo email non valido', en: 'Invalid email address' },

  'footer.rights': { it: 'Tutti i diritti riservati', en: 'All rights reserved' },
  'footer.privacy': { it: 'Privacy policy', en: 'Privacy policy' },
  'footer.cookie': { it: 'Cookie policy', en: 'Cookie policy' },
  'footer.follow': { it: 'Seguimi', en: 'Follow me' },

  'error.404.title': { it: 'Pagina non trovata', en: 'Page not found' },
  'error.404.text': {
    it: 'La pagina che cerchi non esiste o è stata spostata.',
    en: 'The page you are looking for does not exist or has been moved.',
  },
} as const satisfies Record<string, Record<Lang, string>>;

export type UIKey = keyof typeof UI;

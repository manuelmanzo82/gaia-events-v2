-- ---------------------------------------------------------------------
-- Privacy e cookie (26/10/2026): data di revisione a mano per «Ultimo
-- aggiornamento» e testi riscritti su ciò che il sito fa davvero (bozza:
-- revisione legale in DA-CHIEDERE n. 18). Via Google Analytics,
-- Web3Forms, Sanity, Vercel e il banner. Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

ALTER TABLE `pages`
  ADD COLUMN IF NOT EXISTS `revised_on` DATE DEFAULT NULL AFTER `og_media_id`;

UPDATE `pages` SET `title_it` = 'Privacy policy', `title_en` = 'Privacy policy', `intro_it` = 'Come tratto i dati di chi mi scrive dal sito: quali, perché, dove finiscono e per quanto tempo.', `intro_en` = 'How I handle the data of people who write to me through the site: what, why, where it goes and for how long.', `body_it` = '<h2>Chi tratta i dati</h2>
<p>Il titolare del trattamento è Gaia Crognale, che lavora con il nome Gaia Events, in Viale Vaticano 53, 00165 Roma. Per qualsiasi domanda sui vostri dati potete scrivermi a <a href="mailto:gaiacrognale@gmail.com">gaiacrognale@gmail.com</a>.</p>
<h2>Quali dati raccolgo</h2>
<p>Solo quelli che mi date voi, in due modi.</p>
<ul>
<li><strong>Modulo di contatto:</strong> nome, email, telefono se lo indicate, messaggio e lingua della pagina.</li>
<li><strong>Conosciamoci</strong>, il questionario per raccontarmi il vostro evento: oltre ai dati di contatto, tipo di evento, nomi, data, luogo, numero di ospiti, servizi che vi interessano, budget indicativo, come preferite essere ricontattati, il messaggio che scrivete e il momento in cui accettate questa informativa.</li>
</ul>
<p>Con ogni richiesta il server registra anche l’indirizzo IP e il tipo di browser: servono solo a riconoscere invii automatici e abusi. Non c’è una newsletter e il sito non usa strumenti di statistica o di profilazione.</p>
<h2>Perché li uso</h2>
<p>Per rispondervi, preparare una proposta e, se decidiamo di lavorare insieme, organizzare l’evento. Non uso i vostri dati per pubblicità e non li vendo né li cedo a nessuno.</p>
<h2>Su quale base</h2>
<ul>
<li>Rispondere alle vostre richieste e preparare la proposta: misure precontrattuali chieste da voi, articolo 6.1.b del GDPR.</li>
<li>Conservare IP e browser per poco tempo contro gli abusi: legittimo interesse a proteggere il sito, articolo 6.1.f.</li>
<li>Se diventate clienti, gli obblighi fiscali e contabili: obbligo di legge, articolo 6.1.c.</li>
</ul>
<h2>Dove finiscono i dati</h2>
<ul>
<li><strong>Server del sito:</strong> le richieste sono salvate in un database su server di IONOS, nell’Unione europea. IONOS tiene anche i registri di accesso del server, per motivi tecnici e di sicurezza.</li>
<li><strong>Email di avviso:</strong> a ogni richiesta parte un’email con i dati della richiesta, spedita tramite il servizio di posta di Aruba. Arriva nella mia casella Gmail, gestita da Google, che può trattare i dati anche fuori dall’Unione europea: per gli Stati Uniti il trasferimento si basa sul Data Privacy Framework UE-USA e sulle clausole contrattuali standard adottate da Google.</li>
<li><strong>Fornitori dell’evento:</strong> se lavoriamo insieme, a fotografi, location e agli altri fornitori passo solo i dati che servono a loro, come nomi, data e luogo.</li>
</ul>
<h2>Per quanto tempo</h2>
<ul>
<li>Le richieste restano nel pannello del sito per 24 mesi, poi si cancellano da sole.</li>
<li>IP e browser si cancellano dopo 30 giorni.</li>
<li>Le email di avviso restano nella mia casella finché non le elimino: se me lo chiedete, le cancello.</li>
<li>Se diventate clienti, i documenti fiscali si conservano per il tempo previsto dalla legge.</li>
<li>Il salvataggio del questionario nel vostro browser scade dopo 30 giorni: lo spiego nella <a href="/cookie/">cookie policy</a>.</li>
</ul>
<h2>I vostri diritti</h2>
<p>Potete chiedermi in qualsiasi momento di vedere i vostri dati, correggerli, cancellarli, limitarne l’uso, opporvi al trattamento basato sul legittimo interesse o riceverli in un formato leggibile: basta scrivere a <a href="mailto:gaiacrognale@gmail.com">gaiacrognale@gmail.com</a>. Rispondo entro un mese.</p>
<p>Se pensate che i vostri dati non siano trattati correttamente potete presentare un reclamo al <a href="https://www.garanteprivacy.it/" rel="noopener">Garante per la protezione dei dati personali</a>.</p>
<h2>Modifiche</h2>
<p>Se cambia qualcosa nel modo in cui tratto i dati, aggiorno questa pagina e la data in alto.</p>
', `body_en` = '<h2>Who handles your data</h2>
<p>The data controller is Gaia Crognale, trading as Gaia Events, Viale Vaticano 53, 00165 Rome, Italy. For any question about your data you can write to me at <a href="mailto:gaiacrognale@gmail.com">gaiacrognale@gmail.com</a>.</p>
<h2>What data I collect</h2>
<p>Only what you give me, in two ways.</p>
<ul>
<li><strong>Contact form:</strong> name, email, phone number if you add it, message and the language of the page.</li>
<li><strong>Let’s talk</strong>, the questionnaire for telling me about your event: besides your contact details, type of event, names, date, place, number of guests, the services you’re interested in, an indicative budget, how you’d like me to get back to you, the message you write and the moment you accept this notice.</li>
</ul>
<p>With every request the server also records your IP address and browser type: they are used only to recognise automated submissions and abuse. There is no newsletter and the site uses no analytics or profiling tools.</p>
<h2>Why I use it</h2>
<p>To reply to you, prepare a proposal and, if we decide to work together, plan your event. I don’t use your data for advertising and I never sell it or pass it on.</p>
<h2>Legal basis</h2>
<ul>
<li>Replying to your requests and preparing the proposal: steps taken at your request before a contract, Article 6(1)(b) GDPR.</li>
<li>Keeping IP and browser for a short time against abuse: legitimate interest in protecting the site, Article 6(1)(f).</li>
<li>If you become a client, tax and accounting duties: legal obligation, Article 6(1)(c).</li>
</ul>
<h2>Where the data goes</h2>
<ul>
<li><strong>The site’s server:</strong> requests are stored in a database on IONOS servers in the European Union. IONOS also keeps the server access logs, for technical and security reasons.</li>
<li><strong>Notification email:</strong> each request triggers an email containing its details, sent through Aruba’s mail service. It arrives in my Gmail inbox, run by Google, which may process data outside the European Union: for the United States the transfer relies on the EU-US Data Privacy Framework and the standard contractual clauses adopted by Google.</li>
<li><strong>Event suppliers:</strong> if we work together, I share with photographers, venues and other suppliers only the details they need, such as names, date and place.</li>
</ul>
<h2>How long I keep it</h2>
<ul>
<li>Requests stay in the site’s back office for 24 months, then they are deleted automatically.</li>
<li>IP and browser details are deleted after 30 days.</li>
<li>Notification emails stay in my inbox until I delete them: if you ask me to, I will.</li>
<li>If you become a client, tax records are kept for as long as the law requires.</li>
<li>The questionnaire saved in your browser expires after 30 days: see the <a href="/en/cookie/">cookie policy</a>.</li>
</ul>
<h2>Your rights</h2>
<p>At any time you can ask me to access, correct or delete your data, restrict its use, object to processing based on legitimate interest or receive it in a portable format: just write to <a href="mailto:gaiacrognale@gmail.com">gaiacrognale@gmail.com</a>. I reply within one month.</p>
<p>If you believe your data isn’t being handled properly, you can lodge a complaint with the Italian data protection authority, the <a href="https://www.garanteprivacy.it/" rel="noopener">Garante per la protezione dei dati personali</a>, or with the authority where you live.</p>
<h2>Changes</h2>
<p>If anything changes in the way I handle data, I’ll update this page and the date at the top.</p>
', `meta_title_it` = 'Privacy policy', `meta_title_en` = 'Privacy policy', `meta_description_it` = 'Quali dati raccoglie il sito di Gaia Crognale, wedding planner a Roma, perché, dove vengono conservati e per quanto tempo. I vostri diritti.', `meta_description_en` = 'What data the website of Gaia Crognale, wedding planner in Rome, collects, why, where it is stored and for how long. Your rights.', `revised_on` = '2026-10-22' WHERE `page_key` = 'privacy';
UPDATE `pages` SET `title_it` = 'Cookie policy', `title_en` = 'Cookie policy', `intro_it` = 'Sul sito non ci sono cookie di profilazione né di statistica: ecco cosa resta nel vostro browser e perché.', `intro_en` = 'There are no profiling or analytics cookies on this site: here is what stays in your browser, and why.', `body_it` = '<h2>Nessun cookie sul sito</h2>
<p>Il sito non usa cookie: niente statistiche, pixel pubblicitari o strumenti di tracciamento. Font e immagini stanno sul server del sito, Instagram e WhatsApp sono semplici link. L’unico cookie è quello di sessione del pannello riservato a Gaia, che chi visita il sito non riceve.</p>
<h2>Il salvataggio di Conosciamoci</h2>
<p>Mentre compilate Conosciamoci, le risposte si salvano nella memoria locale del vostro browser con il nome <code>gaia-preventivo-v1</code>: se chiudete la pagina potete riprendere da dove eravate. Contiene le risposte, il passo raggiunto e l’ora di inizio, e resta solo sul vostro dispositivo finché non inviate.</p>
<p>Si cancella da solo quando inviate la richiesta o premete «Ricomincia», e comunque dopo 30 giorni.</p>
<h2>Due indicatori di sessione</h2>
<p>Nella memoria di sessione del browser due indicatori tecnici, <code>gaia-intro-vista</code> e <code>gaia-fascia-spinta</code>, evitano di ripetervi l’animazione d’apertura e il suggerimento della fascia in home. Spariscono quando chiudete la scheda.</p>
<h2>Video di YouTube e Vimeo</h2>
<p>Alcune pagine contengono video ospitati su YouTube o su Vimeo. Finché non premete play vedete solo un’immagine dal server del sito e da quei servizi non arriva niente. Avviando il video si carica il lettore di YouTube, nella modalità a privacy avanzata, o quello di Vimeo, con la richiesta di non tracciare: da quel momento valgono le regole di <a href="https://policies.google.com/privacy?hl=it" rel="noopener">Google</a> e di <a href="https://vimeo.com/privacy" rel="noopener">Vimeo</a>, che possono salvare dati nel vostro browser.</p>
<h2>Perché non vedete un banner</h2>
<p>Le linee guida del Garante del 2021 non chiedono il consenso per gli strumenti tecnici necessari a un servizio richiesto da chi visita, come il salvataggio del questionario. Niente di terzi si carica prima di un vostro clic: per questo il sito non mostra un banner sui cookie.</p>
<h2>Come gestire il browser</h2>
<p>Potete vedere e cancellare i dati che i siti salvano nel vostro browser dalle sue impostazioni: ecco le guide di <a href="https://support.google.com/chrome/answer/95647?hl=it" rel="noopener">Chrome</a>, <a href="https://support.mozilla.org/it/kb/eliminare-cookie-dati-siti-firefox" rel="noopener">Firefox</a>, <a href="https://support.apple.com/it-it/guide/safari/sfri11471/mac" rel="noopener">Safari</a> e <a href="https://support.microsoft.com/it-it/microsoft-edge/eliminare-i-cookie-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" rel="noopener">Edge</a>.</p>
<p>Per tutto il resto trovate la <a href="/privacy/">privacy policy</a>, oppure scrivetemi a <a href="mailto:gaiacrognale@gmail.com">gaiacrognale@gmail.com</a>.</p>
', `body_en` = '<h2>No cookies on the site</h2>
<p>The site uses no cookies: no analytics, advertising pixels or tracking tools. Fonts and images are served from the site’s own server, and Instagram and WhatsApp are plain links. The only cookie is the session cookie of Gaia’s private back office, which visitors never receive.</p>
<h2>Saving your answers in Let’s talk</h2>
<p>While you fill in Let’s talk, your answers are saved in your browser’s local storage under the name <code>gaia-preventivo-v1</code>, so if you close the page you can pick up where you left off. It holds your answers, the step you reached and the start time, and it stays only on your device until you send it.</p>
<p>It is deleted automatically when you send your request or press “Start over”, and in any case after 30 days.</p>
<h2>Two session flags</h2>
<p>Two technical flags in your browser’s session storage, <code>gaia-intro-vista</code> and <code>gaia-fascia-spinta</code>, stop the opening animation and the hint on the home page strip from repeating. They disappear when you close the tab.</p>
<h2>YouTube and Vimeo videos</h2>
<p>Some pages contain videos hosted on YouTube or Vimeo. Until you press play you only see an image from the site’s own server, and nothing comes from those services. Playing the video loads the YouTube player, in privacy-enhanced mode, or the Vimeo player, with the do-not-track option: from then on the rules of <a href="https://policies.google.com/privacy?hl=en" rel="noopener">Google</a> and <a href="https://vimeo.com/privacy" rel="noopener">Vimeo</a> apply, and they may store data in your browser.</p>
<h2>Why there’s no banner</h2>
<p>The Italian data protection authority’s 2021 guidelines don’t require consent for technical tools needed to provide a service the visitor asks for, such as saving the questionnaire. Nothing from third parties loads before you click: that’s why the site shows no cookie banner.</p>
<h2>Managing your browser</h2>
<p>You can see and delete the data sites store in your browser from its settings: here are the guides for <a href="https://support.google.com/chrome/answer/95647?hl=en" rel="noopener">Chrome</a>, <a href="https://support.mozilla.org/en-US/kb/clear-cookies-and-site-data-firefox" rel="noopener">Firefox</a>, <a href="https://support.apple.com/en-gb/guide/safari/sfri11471/mac" rel="noopener">Safari</a> and <a href="https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" rel="noopener">Edge</a>.</p>
<p>For everything else see the <a href="/en/privacy/">privacy policy</a>, or write to me at <a href="mailto:gaiacrognale@gmail.com">gaiacrognale@gmail.com</a>.</p>
', `meta_title_it` = 'Cookie policy', `meta_title_en` = 'Cookie policy', `meta_description_it` = 'Il sito di Gaia Crognale non usa cookie: cosa salva nel browser il questionario Conosciamoci e come funzionano i video di YouTube e Vimeo.', `meta_description_en` = 'The website of Gaia Crognale uses no cookies: what the Let’s talk questionnaire saves in your browser and how YouTube and Vimeo videos work.', `revised_on` = '2026-10-22' WHERE `page_key` = 'cookie';

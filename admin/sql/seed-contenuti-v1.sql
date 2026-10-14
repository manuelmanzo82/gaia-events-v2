-- ---------------------------------------------------------------------
-- Contenuti reali importati dal sito v1.
--
-- Fonte: manuelmanzo82/gaia-events-astro, dove questi testi stavano nel
-- CODICE e non su Sanity:
--   src/data/blog-articles.ts  -> 8 articoli
--   src/pages/faq.astro        -> 12 domande in 4 gruppi
--   src/pages/privacy.astro    -> informativa privacy
--   src/pages/cookie.astro     -> cookie policy
--
-- Solo italiano: le colonne _en restano vuote, quindi le pagine inglesi
-- di questi contenuti non vengono generate finche' non le traduciamo.
--
-- Nelle policy l'email e' stata corretta da info@gaiaevents.it a
-- gaiacrognale@gmail.com: la v1 ne usava due diverse.
--
-- Caricamento, dopo schema.sql:
--   mysql -u UTENTE -p NOME_DB < seed-contenuti-v1.sql
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

-- Articoli del blog

INSERT INTO `posts` (`slug_it`, `title_it`, `excerpt_it`, `category_it`, `post_date`, `reading_time`, `body_it`, `meta_description_it`, `active`)
VALUES ('come-scegliere-location-matrimonio-roma', 'Come Scegliere la Location Perfetta per il Matrimonio a Roma', 'Roma offre centinaia di location per matrimoni, dalle ville storiche ai palazzi nobiliari. Ecco i criteri fondamentali per scegliere quella giusta per voi, evitando errori comuni e sorprese dell''ultimo minuto.', 'Location', '2026-01-15', 8, '<h2>Perché Roma è la Città Ideale per il Vostro Matrimonio</h2>
<p>Roma non è solo la Città Eterna: è un palcoscenico naturale per l''amore. Con i suoi palazzi rinascimentali, le ville patrizie immerse nel verde e i panorami che abbracciano la storia, ogni angolo racconta una storia perfetta per il vostro giorno speciale.</p>
<p>Ma con così tante opzioni, come orientarsi? In oltre dieci anni di esperienza come wedding planner a Roma, ho imparato che la scelta della location è la decisione più importante — quella che dà il tono a tutto il matrimonio.</p>
<h2>I Criteri Fondamentali per la Scelta</h2>
<h3>1. Capienza e Logistica</h3>
<p>Il primo passo è definire il numero di ospiti. Una villa che accoglie 80 persone con eleganza può risultare soffocante con 150. Verificate sempre la capienza sia per la cerimonia che per il ricevimento, considerando anche gli spazi per il cocktail e la pista da ballo.</p>
<h3>2. Stile e Atmosfera</h3>
<p>Ogni location ha una personalità. Un palazzo barocco nel centro storico comunica opulenza e tradizione. Una villa con giardino all''italiana evoca romanticismo senza tempo. Un casale ristrutturato nella campagna romana racconta una storia di autenticità. Scegliete una location che parli di voi.</p>
<h3>3. Periodo dell''Anno e Disponibilità</h3>
<p>Le location più richieste a Roma vengono prenotate con 12-18 mesi di anticipo, soprattutto per i mesi di maggio, giugno, settembre e ottobre. Se avete una data flessibile, considerate marzo o novembre: il clima romano è ancora mite e avrete più scelta.</p>
<h2>Le Migliori Zone di Roma per il Matrimonio</h2>
<p>L''Appia Antica offre ville con giardini secolari a pochi minuti dal centro. I Castelli Romani — Frascati, Castel Gandolfo — regalano panorami sul lago e vigneti. La zona di Trastevere e dell''Aventino sorprende con terrazze panoramiche nascoste. Ogni zona ha il suo carattere unico.</p>
<p>La scelta della location perfetta richiede tempo, ricerca e spesso l''occhio esperto di chi conosce ogni angolo della città. Se volete esplorare insieme le opzioni migliori per il vostro matrimonio a Roma, <a href="/preventivo">raccontatemi la vostra visione</a>.</p>', 'Guida completa per scegliere la location ideale per il matrimonio a Roma: ville storiche, palazzi nobiliari, giardini segreti. Consigli dal wedding planner.', 1)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `body_it` = VALUES(`body_it`);

INSERT INTO `posts` (`slug_it`, `title_it`, `excerpt_it`, `category_it`, `post_date`, `reading_time`, `body_it`, `meta_description_it`, `active`)
VALUES ('matrimonio-primavera-guida-fiori-stagione', 'Matrimonio in Primavera: Guida Completa ai Fiori di Stagione', 'Peonie, ranuncoli, rose garden e lisianthus: scoprite quali fiori scegliere per un matrimonio primaverile e come creare composizioni che incantino i vostri ospiti.', 'Ispirazioni', '2026-01-08', 6, '<h2>La Primavera: Quando la Natura Diventa la Vostra Alleata</h2>
<p>Non c''è stagione più generosa della primavera quando si parla di fiori per matrimoni. Da marzo a giugno, la natura offre una palette incredibile di colori, profumi e texture che possono trasformare qualsiasi spazio in un giardino incantato.</p>
<p>Ma attenzione: non tutti i fiori sono disponibili in tutti i mesi primaverili, e la scelta deve tener conto non solo dell''estetica, ma anche della resistenza al calore e della durata durante la giornata.</p>
<h2>I Fiori Protagonisti della Primavera</h2>
<h3>Peonie: La Regina Indiscussa</h3>
<p>Le peonie sono il fiore più richiesto per i matrimoni primaverili, e per buona ragione. Con i loro petali morbidi e voluminosi, sono perfette per bouquet romantici e centrotavola opulenti. La loro stagione è breve — da fine aprile a inizio giugno — quindi pianificate con anticipo.</p>
<h3>Ranuncoli: Eleganza Delicata</h3>
<p>I ranuncoli, con i loro strati di petali sottilissimi, aggiungono una nota di raffinatezza a qualsiasi composizione. Disponibili in una gamma vastissima di colori — dal bianco puro al rosa cipria, dal pesca al bordeaux — sono estremamente versatili.</p>
<h3>Rose Garden: Il Classico Reinventato</h3>
<p>Le rose da giardino, con la loro forma a coppa e il profumo inebriante, sono diverse dalle rose tradizionali. Varietà come la Juliet, la Keira e la Patience hanno rivoluzionato il floral design matrimoniale, portando un senso di giardino inglese in ogni composizione.</p>
<h2>Come Creare la Palette Floreale Perfetta</h2>
<p>Il segreto di una composizione floreale riuscita non è usare i fiori più costosi, ma creare armonia tra forme, colori e texture. Mixate fiori protagonisti (peonie, rose garden) con fiori di riempimento (lisianthus, astilbe) e verde strutturale (eucalipto, ruscus) per un effetto naturale e sofisticato.</p>
<p>Volete creare l''atmosfera floreale perfetta per il vostro matrimonio primaverile? <a href="/preventivo">Parliamone insieme</a> — collaboro con i migliori fioristi di Roma e d''Italia.</p>', 'Quali fiori scegliere per un matrimonio in primavera? Peonie, ranuncoli, rose garden: guida completa con consigli del wedding planner per composizioni perfette.', 1)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `body_it` = VALUES(`body_it`);

INSERT INTO `posts` (`slug_it`, `title_it`, `excerpt_it`, `category_it`, `post_date`, `reading_time`, `body_it`, `meta_description_it`, `active`)
VALUES ('destination-wedding-toscana-guida-completa', 'Destination Wedding in Toscana: Tutto Quello che Dovete Sapere', 'Dalla scelta della villa alle pratiche burocratiche, dal catering alla logistica degli ospiti internazionali: la guida definitiva per un matrimonio da sogno tra le colline toscane.', 'Destination', '2025-12-20', 10, '<h2>Perché la Toscana è la Meta N.1 per i Destination Wedding</h2>
<p>La Toscana incarna tutto ciò che il mondo ama dell''Italia: paesaggi da cartolina, cucina straordinaria, vini leggendari e un''atmosfera che sembra sospesa nel tempo. Non sorprende che sia la destinazione preferita dalle coppie internazionali per il loro matrimonio.</p>
<p>Ma organizzare un destination wedding in Toscana richiede una pianificazione accurata e una conoscenza profonda del territorio. Ecco tutto quello che dovete sapere.</p>
<h2>Le Zone Più Belle per Sposarsi in Toscana</h2>
<h3>Val d''Orcia: Il Paesaggio Iconico</h3>
<p>Patrimonio UNESCO, la Val d''Orcia offre lo sfondo più fotografato della Toscana: colline ondulate, cipressi allineati, borghi medievali. Le ville e gli agriturismi della zona garantiscono privacy assoluta e tramonti indimenticabili.</p>
<h3>Chianti: Tra Vigneti e Castelli</h3>
<p>Il Chianti è perfetto per chi sogna un matrimonio tra vigneti. Castelli medievali trasformati in resort, cantine storiche, ville rinascimentali: ogni location racconta secoli di storia toscana.</p>
<h3>Firenze e Dintorni: Eleganza Rinascimentale</h3>
<p>Per chi desidera la vicinanza alla città, le ville medicee intorno a Firenze offrono il perfetto equilibrio tra grandiosità storica e comodità logistica per gli ospiti internazionali.</p>
<h2>Logistica e Aspetti Pratici</h2>
<p>Il destination wedding richiede attenzione alla logistica degli ospiti: trasferimenti dall''aeroporto, alloggi, attività nei giorni precedenti e successivi. Un buon wedding planner coordina tutto questo, trasformando il matrimonio in un''esperienza completa di più giorni.</p>
<p>State sognando un matrimonio in Toscana? <a href="/preventivo">Raccontatemi il vostro progetto</a> — organizzo destination wedding toscani da oltre otto anni.</p>', 'Organizzare un destination wedding in Toscana: ville, borghi, logistica, costi e consigli pratici. La guida completa del wedding planner per sposarsi tra le colline toscane.', 1)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `body_it` = VALUES(`body_it`);

INSERT INTO `posts` (`slug_it`, `title_it`, `excerpt_it`, `category_it`, `post_date`, `reading_time`, `body_it`, `meta_description_it`, `active`)
VALUES ('budget-matrimonio-2026-pianificare-senza-sorprese', 'Budget Matrimonio 2026: Come Pianificare Senza Sorprese', 'Quanto costa davvero un matrimonio in Italia nel 2026? Analizziamo ogni voce di spesa, dalle più ovvie alle più sottovalutate, con consigli pratici per ottimizzare il budget.', 'Organizzazione', '2025-12-10', 9, '<h2>Il Vero Costo di un Matrimonio nel 2026</h2>
<p>Parliamo di numeri reali. In Italia, il costo medio di un matrimonio nel 2026 si aggira tra i €25.000 e i €45.000, ma la forbice può variare enormemente in base alla location, al numero di ospiti e al livello di personalizzazione desiderato.</p>
<p>La chiave per evitare brutte sorprese? Conoscere tutte le voci di spesa <em>prima</em> di iniziare a pianificare.</p>
<h2>Le Voci di Spesa Principali</h2>
<h3>Location e Catering (40-50% del budget)</h3>
<p>La location e il catering rappresentano quasi la metà del budget totale. Per un matrimonio con 100 ospiti, aspettatevi di investire tra €10.000 e €25.000 per questa voce, a seconda della prestigiosità della location e del tipo di servizio (buffet, servito, family style).</p>
<h3>Fotografia e Video (8-12%)</h3>
<p>Un buon fotografo di matrimonio a Roma costa tra €2.500 e €5.000 per un servizio completo. Il video aggiunge altri €2.000-€4.000. Sono investimenti che valgono ogni centesimo: le foto e il video sono l''unica cosa che vi rimarrà del giorno.</p>
<h3>Fiori e Allestimenti (8-15%)</h3>
<p>Dalle composizioni per la cerimonia ai centrotavola, dal bouquet alle decorazioni: il floral design può variare da €2.000 a €8.000+ a seconda della complessità e della stagionalità dei fiori scelti.</p>
<h2>Come Ottimizzare il Budget</h2>
<p>Il segreto non è tagliare, ma allocare con intelligenza. Investite di più in ciò che vi emoziona davvero (la musica? i fiori? il cibo?) e risparmiate dove potete senza che si noti. Un wedding planner esperto vi aiuta esattamente in questo.</p>
<p>Volete capire quanto costerà il vostro matrimonio? <a href="/preventivo">Provate il nostro calcolatore gratuito</a> per avere una stima personalizzata in pochi minuti.</p>', 'Quanto costa un matrimonio in Italia nel 2026? Guida completa al budget: voci di spesa, costi medi, consigli per risparmiare senza rinunciare alla qualità.', 1)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `body_it` = VALUES(`body_it`);

INSERT INTO `posts` (`slug_it`, `title_it`, `excerpt_it`, `category_it`, `post_date`, `reading_time`, `body_it`, `meta_description_it`, `active`)
VALUES ('tendenze-matrimonio-2026-stili', 'Tendenze Matrimonio 2026: Gli Stili che Faranno Tendenza', 'Dal quiet luxury al ritorno del romanticismo classico, passando per il minimalismo botanico: le tendenze che definiranno i matrimoni del 2026.', 'Tendenze', '2025-11-28', 7, '<h2>Il 2026 Segna un Nuovo Capitolo nel Wedding Design</h2>
<p>Dopo anni di minimalismo estremo e palette neutre, il 2026 porta una ventata di novità. Le coppie cercano autenticità, personalizzazione e un ritorno a un''eleganza più calda e avvolgente. Ecco le tendenze che stanno definendo l''anno.</p>
<h2>Le Tendenze da Conoscere</h2>
<h3>1. Quiet Luxury</h3>
<p>Ispirato alla moda, il quiet luxury nel matrimonio si traduce in materiali pregiati, dettagli raffinati e un''eleganza che non ha bisogno di gridare. Pensate a lino belga per le tovaglie, candele in cera d''api, stoviglie in ceramica artigianale. Meno è più, ma quel "meno" è di qualità straordinaria.</p>
<h3>2. Romanticismo Contemporaneo</h3>
<p>Il romanticismo torna, ma in chiave moderna. Via i drappeggi eccessivi, benvenuti i fiori a cascata, i tessuti morbidi, i toni rosati e ambrati. L''obiettivo è creare un''atmosfera intima e avvolgente, come un abbraccio.</p>
<h3>3. Il Colore dell''Anno: Borgogna e Terracotta</h3>
<p>Le palette neutre non scompaiono, ma si arricchiscono di accenti caldi. Il borgogna, la terracotta, l''ambra e il verde salvia sono i colori protagonisti del 2026, perfetti per matrimoni autunnali ma sorprendentemente versatili tutto l''anno.</p>
<h3>4. La Cena come Esperienza</h3>
<p>Il banchetto non è più solo cibo: è un''esperienza sensoriale completa. Tavoli imperiali con centrotavola scenografici, menu degustazione con abbinamento vini, show cooking. Il cibo diventa protagonista del design dell''evento.</p>
<p>Volete un matrimonio che rifletta le tendenze del 2026 senza perdere la vostra unicità? <a href="/preventivo">Creiamo insieme il vostro stile</a>.</p>', 'Le tendenze matrimonio 2026: quiet luxury, minimalismo botanico, ritorno del romanticismo. Scopri gli stili, i colori e le idee che definiranno i matrimoni dell''anno.', 1)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `body_it` = VALUES(`body_it`);

INSERT INTO `posts` (`slug_it`, `title_it`, `excerpt_it`, `category_it`, `post_date`, `reading_time`, `body_it`, `meta_description_it`, `active`)
VALUES ('come-scegliere-wedding-planner-domande', 'Come Scegliere il Wedding Planner Giusto: 10 Domande da Fare', 'Non tutti i wedding planner sono uguali. Ecco le domande fondamentali da porre durante il primo incontro per capire se avete trovato il professionista giusto per voi.', 'Organizzazione', '2025-11-15', 6, '<h2>Perché la Scelta del Wedding Planner è Cruciale</h2>
<p>Il wedding planner non è un semplice fornitore: è la persona che vi guiderà in un percorso di mesi, che gestirà le vostre ansie e che il giorno del matrimonio sarà la vostra ombra silenziosa, assicurandosi che tutto sia perfetto.</p>
<p>Scegliere quello giusto può fare la differenza tra un matrimonio stressante e uno indimenticabile. Ecco le domande che dovreste fare a ogni primo incontro.</p>
<h2>Le 10 Domande Fondamentali</h2>
<h3>1. Quanti matrimoni gestisci contemporaneamente?</h3>
<p>Un wedding planner che segue 30 matrimoni all''anno non potrà dedicarvi la stessa attenzione di uno che ne segue 12-15. Chiedete sempre quanti eventi gestisce nel vostro stesso periodo.</p>
<h3>2. Qual è il tuo approccio alla pianificazione?</h3>
<p>Ogni planner ha il suo metodo. Alcuni lavorano con timeline rigide, altri con un approccio più fluido. L''importante è che il suo stile di lavoro sia compatibile con il vostro.</p>
<h3>3. Posso vedere matrimoni reali che hai organizzato?</h3>
<p>Le foto di portfolio sono importanti, ma chiedete di vedere matrimoni completi, non solo gli scatti migliori. Guardate i dettagli, l''atmosfera generale, la coerenza dello stile.</p>
<h3>4. Come gestisci gli imprevisti?</h3>
<p>Il vero valore di un wedding planner si vede quando qualcosa va storto. Chiedete esempi concreti di problemi risolti: è il modo migliore per valutare esperienza e sangue freddo.</p>
<h3>5. Quali fornitori consigli e perché?</h3>
<p>Un buon planner ha una rete di fornitori consolidata e trasparente. Diffidate di chi insiste su fornitori specifici senza spiegare il perché o di chi riceve commissioni non dichiarate.</p>
<p>Se cercate un wedding planner a Roma che risponda a tutte queste domande con trasparenza, <a href="/preventivo">iniziamo a conoscerci</a>.</p>', 'Come scegliere il wedding planner perfetto per il vostro matrimonio? Le 10 domande fondamentali da fare al primo incontro per non sbagliare.', 1)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `body_it` = VALUES(`body_it`);

INSERT INTO `posts` (`slug_it`, `title_it`, `excerpt_it`, `category_it`, `post_date`, `reading_time`, `body_it`, `meta_description_it`, `active`)
VALUES ('matrimonio-costiera-amalfitana-guida-definitiva', 'Matrimonio in Costiera Amalfitana: La Guida Definitiva', 'Ravello, Positano, Amalfi: ogni borgo ha il suo fascino unico. Scoprite come organizzare un matrimonio indimenticabile tra limoni, mare e panorami mozzafiato.', 'Destination', '2025-11-01', 11, '<h2>La Costiera Amalfitana: Un Sogno ad Occhi Aperti</h2>
<p>C''è un motivo per cui la Costiera Amalfitana è Patrimonio UNESCO e una delle destinazioni wedding più desiderate al mondo. Il mare cristallino, i borghi arroccati sulle scogliere, i giardini di limoni e le terrazze panoramiche creano uno scenario che nessun allestimento potrà mai superare.</p>
<p>Ma organizzare un matrimonio in Costiera richiede competenza specifica: la logistica è complessa, le location hanno regole precise e la stagionalità gioca un ruolo fondamentale.</p>
<h2>Le Location Iconiche</h2>
<h3>Ravello: L''Eleganza in Quota</h3>
<p>Arroccata a 350 metri sul mare, Ravello offre panorami che tolgono il fiato. Villa Rufolo e Villa Cimbrone sono le location più celebri, con i loro giardini terrazzati affacciati sull''infinito. Perfetta per matrimoni intimi e raffinati.</p>
<h3>Positano: Il Fascino Bohémien</h3>
<p>Con le sue case color pastello che scendono verso il mare, Positano è la quintessenza del glamour mediterraneo. Le location qui sono più intime e informali, perfette per chi cerca un''atmosfera rilassata ma sofisticata.</p>
<h3>Amalfi: La Grandiosità Storica</h3>
<p>Il Duomo di Amalfi è uno degli sfondi più spettacolari per una cerimonia. La cittadina offre hotel storici con terrazze sul mare e una vivacità che contagia ogni evento.</p>
<h2>Aspetti Pratici da Considerare</h2>
<p>Le strade della Costiera sono strette e tortuose: pianificate i trasferimenti con anticipo e considerate barche private per gli spostamenti tra i borghi. Il periodo migliore va da maggio a ottobre, con giugno e settembre come mesi ideali per il clima e la luce.</p>
<p>Sognate un matrimonio con il mare della Costiera come sfondo? <a href="/preventivo">Parliamone</a> — conosco ogni angolo di questa costa meravigliosa.</p>', 'Organizzare un matrimonio in Costiera Amalfitana: Ravello, Positano, Amalfi. Location, costi, logistica e consigli del wedding planner per un evento perfetto.', 1)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `body_it` = VALUES(`body_it`);

INSERT INTO `posts` (`slug_it`, `title_it`, `excerpt_it`, `category_it`, `post_date`, `reading_time`, `body_it`, `meta_description_it`, `active`)
VALUES ('tableau-de-mariage-idee-creative', 'Tableau de Mariage: 20 Idee Creative per Stupire i Vostri Ospiti', 'Dal classico elegante al creativo e originale: idee per un tableau de mariage che rispecchi il vostro stile e lasci i vostri ospiti a bocca aperta.', 'Ispirazioni', '2025-10-18', 5, '<h2>Il Tableau de Mariage: Molto Più di una Lista di Nomi</h2>
<p>Il tableau de mariage è il primo elemento che i vostri ospiti vedranno entrando al ricevimento. È un''opportunità per dare il tono alla serata, sorprendere e far capire subito lo stile del vostro matrimonio.</p>
<p>Dimenticate il classico cartoncino con i nomi stampati: oggi il tableau è un elemento di design a tutti gli effetti.</p>
<h2>Idee per Ogni Stile</h2>
<h3>Elegante e Classico</h3>
<p>Uno specchio antico con scritte in calligrafia dorata. Un pannello di velluto con bigliettini in carta cotone. Un mobile vintage con piccoli vasi e i nomi scritti su tag floreali. L''eleganza classica non passa mai di moda.</p>
<h3>Botanico e Naturale</h3>
<p>Un arco di rami con bigliettini appesi come foglie. Una parete di muschio con lettere in legno. Piccole piante grasse come segnaposto, ognuna con il nome dell''ospite. La natura è il tableau più bello.</p>
<h3>Moderno e Minimal</h3>
<p>Un pannello di plexiglass trasparente con scritte in vinile. Una struttura in ferro battuto con card geometric. Un display digitale interattivo. Per chi ama la pulizia delle linee e il design contemporaneo.</p>
<h3>Creativo e Originale</h3>
<p>Una mappa del mondo per un matrimonio travel-themed. Una libreria con libri che nascondono i nomi dei tavoli. Una composizione di cornici dorate di diverse dimensioni. Un muro di chiavi vintage per un tema "la chiave del cuore".</p>
<p>Cercate l''idea perfetta per il vostro tableau? <a href="/preventivo">Parliamone insieme</a> — creare dettagli unici è la mia passione.</p>', 'Tableau de mariage originale: 20 idee creative dal wedding planner per un tableau che rispecchi il vostro stile. Dal classico elegante al moderno e creativo.', 1)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `body_it` = VALUES(`body_it`);

-- FAQ

-- Le quattro FAQ segnaposto di seed.example.sql coprono gli stessi
-- argomenti di quelle vere: se entrambi i file sono stati caricati,
-- restano dei quasi-doppioni. Vanno via.
DELETE FROM `faqs` WHERE `question_it` IN (
  'Quanto tempo prima vi devo contattare?',
  'Lavorate solo a Roma?',
  'Posso affidarvi solo una parte dell''organizzazione?',
  'Come si costruisce il preventivo?'
);

-- Rende il file ricaricabile senza duplicare le FAQ importate.
DELETE FROM `faqs` WHERE `question_it` IN (
  'Come funziona la consulenza iniziale?',
  'Con quanto anticipo devo contattare un wedding planner?',
  'Organizzi solo matrimoni o anche altri eventi?',
  'Quali servizi offri?',
  'Posso scegliere solo alcuni servizi?',
  'Ti occupi anche della scelta dei fornitori?',
  'Quanto costa un wedding planner?',
  'La consulenza iniziale è a pagamento?',
  'Come funziona il pagamento?',
  'In quali zone d''Italia lavori?',
  'Organizzi matrimoni all''estero?',
  'Puoi consigliare le location migliori?'
);

INSERT INTO `faqs` (`question_it`, `answer_it`, `group_name`, `active`, `sort_order`) VALUES ('Come funziona la consulenza iniziale?', 'Ci prendiamo un caffè — di persona a Roma o in videochiamata — e mi raccontate come immaginate il vostro giorno. Nessun impegno, nessun costo. È semplicemente il modo migliore per capire se siamo sulla stessa lunghezza d''onda.', 'Organizzazione', 1, 1);
INSERT INTO `faqs` (`question_it`, `answer_it`, `group_name`, `active`, `sort_order`) VALUES ('Con quanto anticipo devo contattare un wedding planner?', 'Prima è, meglio è: con 12–18 mesi di anticipo avete la scelta più ampia su location e fornitori. Per un destination wedding, meglio 18–24 mesi. Ma se avete meno tempo, non preoccupatevi — ho organizzato matrimoni bellissimi anche in sei mesi.', 'Organizzazione', 1, 2);
INSERT INTO `faqs` (`question_it`, `answer_it`, `group_name`, `active`, `sort_order`) VALUES ('Organizzi solo matrimoni o anche altri eventi?', 'Non solo matrimoni. Ho organizzato feste private, anniversari e anche eventi aziendali per brand come Calzedonia, BNL e Zurich. Se c''è qualcosa da festeggiare, sono la persona giusta.', 'Organizzazione', 1, 3);
INSERT INTO `faqs` (`question_it`, `answer_it`, `group_name`, `active`, `sort_order`) VALUES ('Quali servizi offri?', 'Quattro strade diverse, tutte su misura. Il Wedding Planning Completo se volete che mi occupi di tutto dall''inizio alla fine. Il Coordinamento del Giorno se avete già organizzato ma volete dormire sonni tranquilli. Design & Styling se cercate qualcuno che curi l''estetica nei minimi dettagli. E Destination Wedding per chi sogna di sposarsi in Italia venendo dall''estero.', 'Servizi', 1, 4);
INSERT INTO `faqs` (`question_it`, `answer_it`, `group_name`, `active`, `sort_order`) VALUES ('Posso scegliere solo alcuni servizi?', 'Certo, non esiste un pacchetto uguale per tutti. Ci sediamo insieme e capiamo cosa vi serve davvero. Magari avete bisogno solo di una mano con i fornitori, o solo del coordinamento il giorno stesso. Lo decidiamo insieme.', 'Servizi', 1, 5);
INSERT INTO `faqs` (`question_it`, `answer_it`, `group_name`, `active`, `sort_order`) VALUES ('Ti occupi anche della scelta dei fornitori?', 'Sì, e vi confesso che è una delle parti che preferisco. Negli anni ho costruito una rete di fotografi, fioristi, catering e musicisti che conosco personalmente. Vi presento quelli che si sposano meglio con il vostro stile — e con il vostro budget.', 'Servizi', 1, 6);
INSERT INTO `faqs` (`question_it`, `answer_it`, `group_name`, `active`, `sort_order`) VALUES ('Quanto costa un wedding planner?', 'Dipende da tante cose: il tipo di servizio, quanti ospiti, la location. Non ho un listino fisso perché ogni matrimonio è diverso. Potete farvi un''idea con il preventivo online qui sul sito, oppure scrivetemi e ne parliamo senza impegno.', 'Costi', 1, 7);
INSERT INTO `faqs` (`question_it`, `answer_it`, `group_name`, `active`, `sort_order`) VALUES ('La consulenza iniziale è a pagamento?', 'No, è gratuita. Ci conosciamo, mi raccontate il vostro progetto, e poi decidete con calma se volete lavorare insieme.', 'Costi', 1, 8);
INSERT INTO `faqs` (`question_it`, `answer_it`, `group_name`, `active`, `sort_order`) VALUES ('Come funziona il pagamento?', 'Lo dividiamo in step, in modo che sia comodo per voi. Definiamo le fasi insieme all''inizio, così sapete sempre cosa aspettarvi e gestite il budget senza sorprese.', 'Costi', 1, 9);
INSERT INTO `faqs` (`question_it`, `answer_it`, `group_name`, `active`, `sort_order`) VALUES ('In quali zone d''Italia lavori?', 'La mia base è Roma, ma mi muovo in tutta Italia. Toscana, Costiera Amalfitana, Puglia, Sicilia, Lago di Como, Sardegna, Umbria — ogni regione ha le sue perle e conosco le migliori.', 'Location e Destinazioni', 1, 10);
INSERT INTO `faqs` (`question_it`, `answer_it`, `group_name`, `active`, `sort_order`) VALUES ('Organizzi matrimoni all''estero?', 'Sì, mi è capitato diverse volte. Parlo inglese e lavoro spesso con coppie internazionali, quindi gestire la logistica oltre confine fa parte del mio lavoro.', 'Location e Destinazioni', 1, 11);
INSERT INTO `faqs` (`question_it`, `answer_it`, `group_name`, `active`, `sort_order`) VALUES ('Puoi consigliare le location migliori?', 'È la domanda che mi fa più piacere. Ho visitato personalmente ville, castelli, masserie e resort in tutta Italia. Vi propongo solo posti che conosco davvero e che so essere all''altezza.', 'Location e Destinazioni', 1, 12);

-- Privacy e cookie policy

INSERT INTO `pages` (`page_key`, `title_it`, `body_it`, `updated_at`)
VALUES ('privacy', 'Privacy policy', '<h2>1. Titolare del trattamento</h2>
<p>
Il titolare del trattamento dei dati personali è <strong>Gaia Events di Gaia Crognale</strong>, con sede a Roma, Italia.<br />
Email di contatto: <a href="mailto:gaiacrognale@gmail.com">gaiacrognale@gmail.com</a>
</p>
<h2>2. Tipi di dati raccolti</h2>
<p>Il sito <strong>gaiaevents.it</strong> raccoglie le seguenti categorie di dati:</p>
<ul>
<li><strong>Dati di navigazione:</strong> indirizzo IP (anonimizzato), tipo di browser, sistema operativo, pagine visitate, orari di accesso e altre informazioni trasmesse automaticamente dal browser.</li>
<li><strong>Cookie:</strong> cookie tecnici necessari al funzionamento del sito e, previo consenso, cookie analitici di terze parti (Google Analytics). Per maggiori dettagli consulta la <a href="/cookie">Cookie Policy</a>.</li>
<li><strong>Dati forniti volontariamente:</strong> nome, email, numero di telefono e altre informazioni inserite dall''utente nei form di contatto e preventivo.</li>
</ul>
<h2>3. Finalità del trattamento</h2>
<p>I dati personali sono trattati per le seguenti finalità:</p>
<ul>
<li><strong>Funzionamento del sito:</strong> garantire la corretta navigazione, la sicurezza e le funzionalità tecniche del sito.</li>
<li><strong>Analisi statistiche anonime:</strong> comprendere come gli utenti utilizzano il sito per migliorarne contenuti e prestazioni (solo con consenso).</li>
<li><strong>Risposta a richieste:</strong> gestire richieste di preventivo, informazioni e contatto inviate tramite i form del sito.</li>
</ul>
<h2>4. Base giuridica del trattamento</h2>
<ul>
<li><strong>Consenso</strong> (art. 6.1.a GDPR): per l''installazione di cookie analitici (Google Analytics).</li>
<li><strong>Legittimo interesse</strong> (art. 6.1.f GDPR): per i cookie tecnici necessari al funzionamento del sito.</li>
<li><strong>Esecuzione di misure precontrattuali</strong> (art. 6.1.b GDPR): per la gestione delle richieste di preventivo e contatto.</li>
</ul>
<h2>5. Servizi di terze parti</h2>
<p>Il sito si avvale dei seguenti servizi esterni che possono trattare dati personali:</p>
<h3>Google Analytics (Google LLC)</h3>
<p>
Servizio di analisi web che utilizza cookie per raccogliere dati statistici anonimi sull''utilizzo del sito.
I dati sono trattati con IP anonimizzato. Attivato solo previo consenso dell''utente.<br />
<a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Privacy Policy di Google</a>
</p>
<h3>Web3Forms</h3>
<p>
Servizio utilizzato per la gestione dei form di contatto e preventivo. I dati inseriti nei form (nome, email, telefono, dettagli richiesta) vengono inviati tramite Web3Forms ai nostri recapiti email.<br />
<a href="https://web3forms.com/privacy" target="_blank" rel="noopener noreferrer">Privacy Policy di Web3Forms</a>
</p>
<h3>Sanity CMS</h3>
<p>
Sistema di gestione contenuti (Content Management System) utilizzato per gestire i contenuti del sito. Sanity non raccoglie dati personali dei visitatori del sito.<br />
<a href="https://www.sanity.io/legal/privacy" target="_blank" rel="noopener noreferrer">Privacy Policy di Sanity</a>
</p>
<h3>Vercel</h3>
<p>
Piattaforma di hosting utilizzata per pubblicare il sito. Vercel può raccogliere dati tecnici di navigazione (log del server).<br />
<a href="https://vercel.com/legal/privacy-policy" target="_blank" rel="noopener noreferrer">Privacy Policy di Vercel</a>
</p>
<h2>6. Conservazione dei dati</h2>
<p>
I dati personali forniti tramite i form sono conservati per il tempo necessario a gestire la richiesta e, successivamente, per il periodo previsto dagli obblighi di legge. I dati di navigazione e i cookie analitici sono conservati in forma anonima per un massimo di 26 mesi.
</p>
<h2>7. Trasferimento dei dati</h2>
<p>
Alcuni servizi di terze parti (Google Analytics, Vercel) possono comportare il trasferimento di dati verso paesi extra-UE (Stati Uniti). Tali trasferimenti avvengono sulla base di garanzie adeguate, incluse le Clausole Contrattuali Standard approvate dalla Commissione Europea.
</p>
<h2>8. Diritti dell''utente</h2>
<p>Ai sensi degli articoli 15-22 del GDPR, l''utente ha diritto di:</p>
<ul>
<li><strong>Accesso:</strong> ottenere conferma dell''esistenza di un trattamento e accedere ai propri dati.</li>
<li><strong>Rettifica:</strong> richiedere la correzione di dati inesatti o incompleti.</li>
<li><strong>Cancellazione:</strong> richiedere la cancellazione dei propri dati personali.</li>
<li><strong>Limitazione:</strong> richiedere la limitazione del trattamento.</li>
<li><strong>Portabilità:</strong> ricevere i propri dati in un formato strutturato e leggibile.</li>
<li><strong>Opposizione:</strong> opporsi al trattamento per motivi legittimi.</li>
<li><strong>Revoca del consenso:</strong> revocare in qualsiasi momento il consenso prestato per i cookie analitici tramite il pannello di gestione cookie del sito.</li>
</ul>
<p>
Per esercitare i propri diritti, l''utente può contattare il titolare all''indirizzo email <a href="mailto:gaiacrognale@gmail.com">gaiacrognale@gmail.com</a>.
</p>
<p>
L''utente ha inoltre il diritto di proporre reclamo al <strong>Garante per la protezione dei dati personali</strong> (<a href="https://www.garanteprivacy.it" target="_blank" rel="noopener noreferrer">www.garanteprivacy.it</a>).
</p>
<h2>9. Modifiche alla presente informativa</h2>
<p>
Il titolare si riserva il diritto di apportare modifiche alla presente informativa in qualsiasi momento. Le modifiche saranno pubblicate su questa pagina con indicazione della data di ultimo aggiornamento.
</p>', '2026-02-01 00:00:00')
ON DUPLICATE KEY UPDATE `body_it` = VALUES(`body_it`), `updated_at` = VALUES(`updated_at`);

INSERT INTO `pages` (`page_key`, `title_it`, `body_it`, `updated_at`)
VALUES ('cookie', 'Cookie policy', '<h2>1. Cosa sono i cookie</h2>
<p>
I cookie sono piccoli file di testo che i siti web visitati inviano al browser dell''utente, dove vengono memorizzati per essere ritrasmessi agli stessi siti alla visita successiva. I cookie sono utilizzati per diverse finalità, come permettere la navigazione efficiente tra le pagine, ricordare le preferenze dell''utente e, in generale, migliorare l''esperienza di navigazione.
</p>
<h2>2. Cookie tecnici (necessari)</h2>
<p>
Questi cookie sono indispensabili per il corretto funzionamento del sito e non possono essere disattivati. Non raccolgono informazioni personali identificabili.
</p>
<div>
<table>
<thead>
<tr>
<th>Cookie</th>
<th>Finalità</th>
<th>Durata</th>
</tr>
</thead>
<tbody>
<tr>
<td><code>gaia-cookie-consent</code></td>
<td>Memorizza le preferenze cookie dell''utente</td>
<td>12 mesi</td>
</tr>
</tbody>
</table>
</div>
<h2>3. Cookie analitici (previo consenso)</h2>
<p>
Questi cookie sono utilizzati per raccogliere informazioni statistiche anonime sull''utilizzo del sito, come il numero di visitatori, le pagine più visitate e la durata delle visite. Vengono installati solo dopo aver ottenuto il consenso esplicito dell''utente tramite il banner cookie.
</p>
<h3>Google Analytics</h3>
<p>
Il sito utilizza Google Analytics, un servizio di analisi web fornito da Google LLC. Google Analytics utilizza i seguenti cookie:
</p>
<div>
<table>
<thead>
<tr>
<th>Cookie</th>
<th>Finalità</th>
<th>Durata</th>
</tr>
</thead>
<tbody>
<tr>
<td><code>_ga</code></td>
<td>Distingue gli utenti unici assegnando un ID generato casualmente. Utilizzato per calcolare dati su visitatori, sessioni e campagne.</td>
<td>24 mesi</td>
</tr>
<tr>
<td><code>_ga_*</code></td>
<td>Utilizzato da Google Analytics 4 per mantenere lo stato della sessione.</td>
<td>24 mesi</td>
</tr>
<tr>
<td><code>_gid</code></td>
<td>Distingue gli utenti. Utilizzato per dati statistici sulle visite giornaliere.</td>
<td>24 ore</td>
</tr>
</tbody>
</table>
</div>
<p>
I dati sono raccolti con indirizzo IP anonimizzato e trattati in forma aggregata. Per maggiori informazioni su come Google utilizza i dati, consulta la <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Privacy Policy di Google</a>.
</p>
<h2>4. Come gestire i cookie</h2>
<h3>Tramite il nostro sito</h3>
<p>
Puoi gestire le tue preferenze cookie in qualsiasi momento cliccando sull''icona <strong>impostazioni cookie</strong> presente in basso a sinistra su ogni pagina del sito, oppure modificando le impostazioni al primo accesso tramite il banner cookie.
</p>
<h3>Tramite il browser</h3>
<p>
Puoi anche gestire i cookie direttamente dalle impostazioni del tuo browser. Ecco i link alle guide dei principali browser:
</p>
<ul>
<li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener noreferrer">Google Chrome</a></li>
<li><a href="https://support.mozilla.org/it/kb/protezione-antitracciamento-avanzata-firefox-desktop" target="_blank" rel="noopener noreferrer">Mozilla Firefox</a></li>
<li><a href="https://support.apple.com/it-it/guide/safari/sfri11471/mac" target="_blank" rel="noopener noreferrer">Apple Safari</a></li>
<li><a href="https://support.microsoft.com/it-it/microsoft-edge/eliminare-i-cookie-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener noreferrer">Microsoft Edge</a></li>
</ul>
<p>
<strong>Nota:</strong> la disattivazione dei cookie tecnici potrebbe compromettere alcune funzionalità del sito.
</p>
<h2>5. Aggiornamenti</h2>
<p>
La presente Cookie Policy può essere aggiornata periodicamente. Ti consigliamo di consultare questa pagina regolarmente per essere informato su eventuali modifiche.
</p>
<h2>6. Riferimenti</h2>
<p>
Per informazioni complete sul trattamento dei dati personali, consulta la nostra <a href="/privacy">Privacy Policy</a>.<br />
Per qualsiasi domanda, contattaci all''indirizzo <a href="mailto:gaiacrognale@gmail.com">gaiacrognale@gmail.com</a>.
</p>', '2026-02-01 00:00:00')
ON DUPLICATE KEY UPDATE `body_it` = VALUES(`body_it`), `updated_at` = VALUES(`updated_at`);

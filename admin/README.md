# Back office

PHP 8.2+ e MySQL, nessuna dipendenza esterna (niente Composer).
Sostituisce Sanity: qui Gaia gestisce testi, eventi, servizi, recensioni,
blog, FAQ, immagini e richieste di preventivo.

## Installazione sul server

```bash
# 1. carica la cartella admin/ in public_html/admin/
# 2. configurazione
cp config.example.php config.php
# compila: database, site_url, export_token, token GitHub
chmod 640 config.php

# 3. database
mysql -u UTENTE -p NOME_DB < sql/schema.sql
mysql -u UTENTE -p NOME_DB < sql/seed.example.sql   # opzionale, testi segnaposto

# 4. cartella degli upload, accanto al sito (non dentro admin/)
mkdir -p ../uploads && chmod 755 ../uploads

# 5. primo utente (nessuna password di default)
php bin/create-user.php --email=tua@email.it --name="Nome Cognome" --role=admin
php bin/create-user.php --email=gaia@email.it --name="Gaia Crognale" --role=editor
```

Il token di `export_token` si genera con:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Lo stesso valore va nel secret GitHub `EXPORT_TOKEN`.

## Come è fatto

| | |
|---|---|
| `index.php` | front controller, tutte le rotte |
| `src/` | classi: `Db`, `Auth`, `Csrf`, `Router`, `View`, `Validator`, `ImageService`, `Exporter`, `Publisher`, `Inquiry` |
| `src/Controllers/` | un controller per sezione; `ResourceController` è il CRUD generico |
| `views/` | template PHP, nessun design |
| `api/export.php` | export dei contenuti per il build (bearer token) |
| `api/quote.php`, `api/contact.php` | ricevono i form del sito |
| `bin/export-content.php` | export da riga di comando |
| `bin/create-user.php` | crea o aggiorna un utente |
| `sql/` | `schema.sql` e `seed.example.sql` |

## Sicurezza

- Password con `password_hash`, throttling a 5 tentativi per 15 minuti su
  email **e** IP, ID di sessione rigenerato al login.
- Token CSRF su ogni form che modifica dati.
- Query sempre con parametri: nessuna concatenazione di SQL.
- `.htaccess` nega l'accesso diretto a `config.php`, `src/`, `views/`,
  `bin/`, `sql/` e `storage/`.
- Il back office manda sempre `X-Robots-Tag: noindex`.
- `config.php` e `storage/` sono gitignorati: la repo è pubblica.

## Cosa manca

- Editor HTML vero per i campi `body` (ora sono textarea).
- Interfaccia di crop col mouse: il ritaglio si inserisce in pixel.
- Gestione dei blocchi della destination wedding dal form (lo schema c'è).
- Sincronizzazione Instagram (tabella e campi pronti).

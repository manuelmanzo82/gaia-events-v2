# Gaia Events — sito v2

Sito di **Gaia Crognale**, wedding & event planner a Roma.
Astro 5 statico bilingue IT/EN + back office PHP/MySQL su hosting condiviso.

- `site/` — frontend Astro 5 (`output: 'static'`), React 19 solo come islands, Tailwind
- `admin/` — back office PHP 8.2 + MySQL, sostituisce Sanity
- `docs/` — recon del sito v1 e documentazione
- `.github/workflows/` — build e deploy SFTP

## Avvio rapido

```bash
cd site
npm install
npm run content:seed   # copia i contenuti di esempio (solo la prima volta)
npm run dev            # http://localhost:4321
```

## Come va online

Gaia preme **Pubblica** nel back office → `repository_dispatch` → la GitHub Action
scarica i contenuti dall'endpoint protetto `admin/api/export.php`, fa il build di
Astro e carica `site/dist/` via SFTP. `admin/` e `uploads/` non vengono mai toccati.

Dettagli di architettura, decisioni e comandi: **[CLAUDE.md](CLAUDE.md)**.

## Stato

Scheletro del progetto. Home, gallery e preventivatore sono segnaposto:
il design arriva in una fase successiva.

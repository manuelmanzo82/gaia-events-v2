#!/usr/bin/env bash
# ---------------------------------------------------------------------
# Scarica i contenuti dal back office (admin/api/export.php) e li divide
# nei file di site/src/content/. Usato dal workflow deploy.yml.
#
# Robusto agli intoppi di IONOS (21/10/2026: export in timeout a 60 s):
#  - ogni chiamata ha fino a EXPORT_TIMEOUT secondi (default 180), di cui
#    30 per aprire la connessione;
#  - se non riesce, riprova dopo ogni attesa di EXPORT_ATTESE (default
#    "10 30 60"): 1 chiamata + 3 nuovi tentativi;
#  - non riprova sugli errori di configurazione (401, 403, 404: token o
#    indirizzo sbagliati), che non si sistemano da soli;
#  - se fallisce comunque, esce con un messaggio chiaro (annotazione
#    ::error e riepilogo della run, che il back office mostra a Gaia).
#
# Variabili: EXPORT_URL, EXPORT_TOKEN (segreti), EXPORT_TIMEOUT,
# EXPORT_ATTESE, CONTENT_DIR (default site/src/content).
# ---------------------------------------------------------------------
set -euo pipefail

: "${EXPORT_URL:?Manca EXPORT_URL}"
: "${EXPORT_TOKEN:?Manca EXPORT_TOKEN}"
TIMEOUT="${EXPORT_TIMEOUT:-180}"
read -r -a ATTESE <<< "${EXPORT_ATTESE:-10 30 60}"
CONTENT_DIR="${CONTENT_DIR:-site/src/content}"
MAX=$(( ${#ATTESE[@]} + 1 ))
PAYLOAD="$(mktemp)"
trap 'rm -f "$PAYLOAD"' EXIT

riepilogo() {
  if [ -n "${GITHUB_STEP_SUMMARY:-}" ]; then
    printf '%s\n' "$@" >> "$GITHUB_STEP_SUMMARY"
  fi
}

fallisci() {
  local motivo="$1"
  echo "::error title=Pubblicazione non riuscita::${motivo}"
  riepilogo "### Pubblicazione non riuscita" "" "${motivo}"
  exit 1
}

motivo=''
for (( tentativo = 1; tentativo <= MAX; tentativo++ )); do
  : > "$PAYLOAD"
  inizio=$(date +%s)
  stato=$(curl -sS --connect-timeout 30 --max-time "$TIMEOUT" \
    -H "Authorization: Bearer ${EXPORT_TOKEN}" \
    -o "$PAYLOAD" -w '%{http_code}' \
    "$EXPORT_URL" 2> curl-errore.txt) && codice=0 || codice=$?
  durata=$(( $(date +%s) - inizio ))
  errore_curl="$(tr -d '\n' < curl-errore.txt 2> /dev/null || true)"
  rm -f curl-errore.txt

  if [ "$codice" -eq 0 ] && [ "$stato" = "200" ] && jq -e '.content | type == "object"' "$PAYLOAD" > /dev/null 2>&1; then
    echo "Export riuscito al tentativo ${tentativo}/${MAX} (${durata} s)."
    break
  fi

  if [ "$codice" -ne 0 ]; then
    motivo="nessuna risposta in ${durata} s (curl ${codice}: ${errore_curl:-errore di rete})"
  elif [ "$stato" != "200" ]; then
    motivo="HTTP ${stato}: $(head -c 200 "$PAYLOAD" | tr -d '\n')"
  else
    motivo="risposta non valida: $(head -c 200 "$PAYLOAD" | tr -d '\n')"
  fi

  case "$stato" in
    401|403)
      fallisci "Il back office ha rifiutato il token di export (HTTP ${stato}). Va controllato EXPORT_TOKEN: riprovare non serve." ;;
    404)
      fallisci "L'indirizzo dell'export non esiste (HTTP 404). Va controllato EXPORT_URL: riprovare non serve." ;;
  esac

  if (( tentativo < MAX )); then
    attesa="${ATTESE[$(( tentativo - 1 ))]}"
    echo "::warning::Export, tentativo ${tentativo}/${MAX} non riuscito: ${motivo}. Riprovo tra ${attesa} s."
    sleep "$attesa"
  else
    fallisci "Il back office su IONOS non ha consegnato i contenuti dopo ${MAX} tentativi (ultimo: ${motivo}). Il sito online non è cambiato: riprova tra qualche minuto."
  fi
done

# Una chiave del JSON = un file in site/src/content/.
for chiave in $(jq -r '.content | keys[]' "$PAYLOAD"); do
  jq --arg k "$chiave" '.content[$k]' "$PAYLOAD" > "${CONTENT_DIR}/${chiave}.json"
  echo "scritto ${chiave}.json ($(wc -c < "${CONTENT_DIR}/${chiave}.json") byte)"
done

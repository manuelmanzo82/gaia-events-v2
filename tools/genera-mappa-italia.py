"""Da GeoJSON (regioni d'Italia, Natural Earth) a site/src/lib/mappa-italia.ts.

Uso (una volta sola, fuori dal repo, in una cartella vuota):
  curl -sSLO https://naciscdn.org/naturalearth/10m/cultural/ne_10m_admin_1_states_provinces.zip
  unzip ne_10m_admin_1_states_provinces.zip
  npx -y mapshaper@0.6.102 -i ne_10m_admin_1_states_provinces.shp     -filter 'adm0_a3 === "ITA"' -dissolve region copy-fields=region     -simplify 10% keep-shapes -filter-slivers min-area=30km2     -o italia-regioni.json format=geojson precision=0.001
  python tools/genera-mappa-italia.py italia-regioni.json site/src/lib/mappa-italia.ts anteprima.svg

Proiezione equirettangolare con fattore cos(42°) sulla longitudine: per
l'Italia la deformazione è trascurabile e la stessa formula sta nel modulo
TS (proietta), così i punti lat/lng del database cadono nel posto giusto.
"""
import json
import math
import sys

SRC, OUT, PREVIEW = sys.argv[1], sys.argv[2], sys.argv[3]
CODICI = {
    "Valle d'Aosta": 'valle-d-aosta', 'Piemonte': 'piemonte', 'Lombardia': 'lombardia',
    'Trentino-Alto Adige': 'trentino-alto-adige', 'Liguria': 'liguria', 'Emilia-Romagna': 'emilia-romagna',
    'Marche': 'marche', 'Veneto': 'veneto', 'Friuli-Venezia Giulia': 'friuli-venezia-giulia',
    'Abruzzo': 'abruzzo', 'Molise': 'molise', 'Apulia': 'puglia', 'Basilicata': 'basilicata',
    'Calabria': 'calabria', 'Campania': 'campania', 'Lazio': 'lazio', 'Toscana': 'toscana',
    'Sicily': 'sicilia', 'Sardegna': 'sardegna', 'Umbria': 'umbria',
}
K = math.cos(math.radians(42))
LNG0, LAT0 = 6.5, 47.15
SCALA = 40  # unità SVG per grado di latitudine

d = json.load(open(SRC, encoding='utf8'))
regioni = {}
xs, ys = [], []


def proietta(lng, lat):
    return ((lng - LNG0) * K * SCALA, (LAT0 - lat) * SCALA)


for f in d['features']:
    codice = CODICI[f['properties']['region']]
    g = f['geometry']
    poligoni = g['coordinates'] if g['type'] == 'MultiPolygon' else [g['coordinates']]
    parti = []
    for poligono in poligoni:
        for anello in poligono:
            punti = [proietta(*p) for p in anello]
            for x, y in punti:
                xs.append(x); ys.append(y)
            out, prev = [], None
            for x, y in punti:
                c = (round(x, 1), round(y, 1))
                if c != prev:
                    out.append(c); prev = c
            if len(out) < 4:
                continue
            parti.append('M' + 'L'.join(f'{x:g} {y:g}' for x, y in out[:-1]) + 'Z')
    regioni[codice] = ''.join(parti)

larghezza = math.ceil(max(xs)) + 2
altezza = math.ceil(max(ys)) + 2
minx, miny = math.floor(min(xs)) - 2, math.floor(min(ys)) - 2

ts = [
    '/**',
    " * Mappa dell'Italia per regioni (pagina Destination wedding), generata",
    ' * da Natural Earth 1:10m admin-1 (pubblico dominio): province unite per',
    ' * regione, semplificate al 10% con mapshaper, proiezione equirettangolare',
    ' * con cos(42°) sulla longitudine. Nessuna richiesta esterna: i tracciati',
    ' * stanno qui. Rigenerare con tools/genera-mappa-italia.py.',
    ' */',
    '',
    f"export const VIEWBOX = '{minx} {miny} {larghezza - minx} {altezza - miny}';",
    '',
    '/** Da latitudine/longitudine alle coordinate della mappa. */',
    'export function proietta(lat: number, lng: number): { x: number; y: number } {',
    f'  const k = Math.cos((42 * Math.PI) / 180);',
    f'  return {{ x: (lng - {LNG0}) * k * {SCALA}, y: ({LAT0} - lat) * {SCALA} }};',
    '}',
    '',
    '/** Tracciato SVG per codice regione (gli stessi codici del back office). */',
    'export const REGIONI: Record<string, string> = {',
]
for codice in sorted(regioni):
    ts.append(f"  '{codice}':")
    ts.append(f"    '{regioni[codice]}',")
ts.append('};')
open(OUT, 'w', encoding='utf8', newline='\n').write('\n'.join(ts) + '\n')

svg = [f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{minx} {miny} {larghezza - minx} {altezza - miny}" width="600">']
for codice, p in regioni.items():
    fill = '#c9a962' if codice in ('lazio', 'toscana', 'campania', 'puglia') else 'none'
    svg.append(f'<path d="{p}" fill="{fill}" fill-opacity="0.35" stroke="#a68745" stroke-width="0.6"/>')
svg.append('</svg>')
open(PREVIEW, 'w', encoding='utf8').write(''.join(svg))
print('ok', sum(len(v) for v in regioni.values()), 'byte di tracciati', minx, miny, larghezza, altezza)

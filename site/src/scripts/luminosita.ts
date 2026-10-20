/**
 * Luminosità reale di una foto sotto un rettangolo dello schermo (giro 9).
 *
 * Serve al tema di logo, menu e flottanti sopra QUALSIASI foto (le
 * dichiara data-luce): sopra un cielo o una parete chiara l'avorio non si
 * legge, quindi si misura la luminanza dei pixel e si sceglie il colore
 * con il contrasto migliore. Se sopra la foto c'è un velo scuro (un
 * elemento *-velo con gradienti lineari, come quello dell'hero o delle
 * schede), se ne legge il CSS calcolato e si calcola quanto lascia
 * passare in quel punto (gradienti lineari e radiali ellittici): la
 * luminanza della foto viene moltiplicata per
 * quella trasmissione (il velo è quasi nero).
 *
 * Ogni immagine viene ridotta una volta sola in una tela da 64 px di lato
 * (stesso dominio, quindi la tela non è «sporca»); poi ogni misura legge
 * pochi pixel. object-fit: cover e object-position sono rispettati.
 */

const LATO = 64;
const cache = new WeakMap<HTMLImageElement, { src: string; dati: Uint8ClampedArray; w: number; h: number } | null>();

/** Luminanza relativa WCAG di un canale sRGB 0..255. */
const lin = (c: number): number => {
  const v = c / 255;
  return v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
};

function ridotta(img: HTMLImageElement): { dati: Uint8ClampedArray; w: number; h: number } | null {
  if (!img.complete || img.naturalWidth === 0) return null;
  const src = img.currentSrc || img.src;
  const c = cache.get(img);
  if (c !== undefined && (c === null || c.src === src)) return c;
  try {
    const scala = LATO / Math.max(img.naturalWidth, img.naturalHeight);
    const w = Math.max(1, Math.round(img.naturalWidth * scala));
    const h = Math.max(1, Math.round(img.naturalHeight * scala));
    const tela = document.createElement('canvas');
    tela.width = w;
    tela.height = h;
    const ctx = tela.getContext('2d', { willReadFrequently: true });
    if (!ctx) return null;
    ctx.drawImage(img, 0, 0, w, h);
    const voce = { src, dati: ctx.getImageData(0, 0, w, h).data, w, h };
    cache.set(img, voce);
    return voce;
  } catch {
    cache.set(img, null);
    return null;
  }
}

function posizione(valore: string): [number, number] {
  const parti = valore.split(/\s+/);
  const p = (s: string | undefined): number => {
    if (!s) return 0.5;
    if (s.endsWith('%')) return parseFloat(s) / 100;
    return s === 'left' || s === 'top' ? 0 : s === 'right' || s === 'bottom' ? 1 : 0.5;
  };
  return [p(parti[0]), p(parti[1])];
}

/**
 * Luminanza media (0..1) della foto dentro `contenitore` sotto il
 * rettangolo dato, oppure null se non c'è una foto lì o non è leggibile.
 */
export function luminanzaSotto(
  contenitore: HTMLElement,
  rett: { left: number; top: number; right: number; bottom: number },
): number | null {
  const cx = (rett.left + rett.right) / 2;
  const cy = (rett.top + rett.bottom) / 2;
  let img: HTMLImageElement | null = null;
  for (const candidata of contenitore.querySelectorAll<HTMLImageElement>('img')) {
    const r = candidata.getBoundingClientRect();
    if (r.width > 0 && r.left <= cx && r.right > cx && r.top <= cy && r.bottom > cy) {
      // Nelle dissolvenze conta l'immagine visibile.
      if (getComputedStyle(candidata.closest('picture') ?? candidata).opacity === '0') continue;
      img = candidata;
    }
  }
  if (!img) return null;
  const rid = ridotta(img);
  if (!rid) return null;

  const r = img.getBoundingClientRect();
  const nw = img.naturalWidth;
  const nh = img.naturalHeight;
  const cover = getComputedStyle(img).objectFit === 'cover';
  const scala = cover ? Math.max(r.width / nw, r.height / nh) : Math.min(r.width / nw, r.height / nh);
  const [px, py] = posizione(getComputedStyle(img).objectPosition);
  const ox = r.left + (r.width - nw * scala) * px;
  const oy = r.top + (r.height - nh * scala) * py;

  // Rettangolo in coordinate della tela ridotta.
  const k = rid.w / nw;
  const x0 = Math.max(0, Math.floor(((rett.left - ox) / scala) * k));
  const x1 = Math.min(rid.w - 1, Math.ceil(((rett.right - ox) / scala) * k));
  const y0 = Math.max(0, Math.floor(((rett.top - oy) / scala) * k));
  const y1 = Math.min(rid.h - 1, Math.ceil(((rett.bottom - oy) / scala) * k));
  if (x1 < x0 || y1 < y0) return null;

  let somma = 0;
  let n = 0;
  for (let y = y0; y <= y1; y++) {
    for (let x = x0; x <= x1; x++) {
      const i = (y * rid.w + x) * 4;
      somma += 0.2126 * lin(rid.dati[i]) + 0.7152 * lin(rid.dati[i + 1]) + 0.0722 * lin(rid.dati[i + 2]);
      n++;
    }
  }
  if (n === 0) return null;
  // Il browser mescola il velo nello spazio sRGB, non in quello lineare:
  // sulla luminanza la trasmissione pesa elevata a ~2,2.
  return (somma / n) * trasmissioneVelo(contenitore, cx, cy) ** 2.2;
}

/** Divide sulle virgole di primo livello (fuori dalle parentesi). */
function dividi(testo: string): string[] {
  const parti: string[] = [];
  let livello = 0;
  let inizio = 0;
  for (let i = 0; i < testo.length; i++) {
    const c = testo[i];
    if (c === '(') livello++;
    else if (c === ')') livello--;
    else if (c === ',' && livello === 0) {
      parti.push(testo.slice(inizio, i).trim());
      inizio = i + 1;
    }
  }
  parti.push(testo.slice(inizio).trim());
  return parti;
}

/**
 * Quanto lascia passare (0..1) il velo scuro sopra la foto nel punto
 * (x, y): prodotto di (1 − alfa) di ogni gradiente del velo — lineari con
 * gli angoli 0/90/180/270deg e radiali ellittici «Wx% Hy% at X% Y%»
 * (quelli che usiamo).
 */
function trasmissioneVelo(contenitore: HTMLElement, x: number, y: number): number {
  let velo: HTMLElement | null = null;
  for (const el of contenitore.querySelectorAll<HTMLElement>('[class*="-velo"]')) {
    const r = el.getBoundingClientRect();
    if (r.width > 0 && r.left <= x && r.right > x && r.top <= y && r.bottom > y) velo = el;
  }
  if (!velo) return 1;
  const stile = getComputedStyle(velo);
  const opacita = parseFloat(stile.opacity);
  if (!(opacita > 0)) return 1;
  const r = velo.getBoundingClientRect();
  let passa = 1;
  for (const strato of dividi(stile.backgroundImage)) {
    let parti: string[];
    let t: number | null = null;
    if (strato.startsWith('linear-gradient(')) {
      parti = dividi(strato.slice('linear-gradient('.length, -1));
      let angolo = 180;
      if (/deg$/.test(parti[0])) angolo = parseFloat(parti.shift() as string);
      else if (parti[0].startsWith('to ')) {
        const verso = parti.shift() as string;
        angolo = verso === 'to top' ? 0 : verso === 'to right' ? 90 : verso === 'to left' ? 270 : 180;
      }
      angolo = ((angolo % 360) + 360) % 360;
      t =
        angolo === 180 ? (y - r.top) / r.height
        : angolo === 0 ? (r.bottom - y) / r.height
        : angolo === 90 ? (x - r.left) / r.width
        : angolo === 270 ? (r.right - x) / r.width
        : null;
    } else if (strato.startsWith('radial-gradient(')) {
      parti = dividi(strato.slice('radial-gradient('.length, -1));
      const forma = parti[0].match(/(-?[\d.]+)%\s+(-?[\d.]+)%\s+at\s+(-?[\d.]+)%\s+(-?[\d.]+)%/);
      if (!forma) continue;
      parti.shift();
      const [rx, ry, cx, cy] = forma.slice(1).map((v) => parseFloat(v) / 100);
      const dx = (x - (r.left + cx * r.width)) / (rx * r.width);
      const dy = (y - (r.top + cy * r.height)) / (ry * r.height);
      t = Math.sqrt(dx * dx + dy * dy);
    } else {
      continue;
    }
    if (t === null) continue;
    // Fermate: colore (con alfa) e posizione in %; senza posizione, distribuite.
    const fermate = parti.map((p, i) => {
      const colore = p.match(/rgba?\(([^)]*)\)/);
      const valori = colore ? colore[1].split(/[\s,/]+/).filter(Boolean).map(Number) : [0, 0, 0, 0];
      const alfa = valori.length > 3 ? valori[3] : colore ? 1 : 0;
      const pos = p.match(/(-?[\d.]+)%/);
      return { alfa, pos: pos ? parseFloat(pos[1]) / 100 : parti.length > 1 ? i / (parti.length - 1) : 0 };
    });
    let alfa = fermate[fermate.length - 1]?.alfa ?? 0;
    if (t <= fermate[0].pos) alfa = fermate[0].alfa;
    for (let i = 0; i < fermate.length - 1; i++) {
      const a = fermate[i];
      const b = fermate[i + 1];
      if (t >= a.pos && t <= b.pos) {
        alfa = b.pos === a.pos ? b.alfa : a.alfa + ((t - a.pos) / (b.pos - a.pos)) * (b.alfa - a.alfa);
        break;
      }
    }
    passa *= 1 - Math.min(Math.max(alfa, 0), 1) * opacita;
  }
  return passa;
}

/**
 * Sopra questa luminanza l'inchiostro (#262019) contrasta più dell'avorio
 * (#f4efe8): è il punto in cui i due rapporti di contrasto si equivalgono.
 */
export const SOGLIA_INCHIOSTRO = 0.19;

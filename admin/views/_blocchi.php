<?php
/**
 * Editor a blocchi degli articoli del Magazine. Nessuna libreria: un campo
 * nascosto «blocchi» con il JSON e un'interfaccia che lo tiene aggiornato.
 * Il server rifà comunque tutti i controlli (Magazine::normalizza) e genera
 * l'HTML con i soli tag consentiti.
 *
 * Testo dei paragrafi: **grassetto**, *corsivo*, [testo del link](https://…)
 * (i tre bottoni sopra il campo li inseriscono attorno al testo selezionato).
 *
 * @var string $name
 * @var array<string, mixed> $field
 * @var array<string, mixed> $row
 * @var array<int, string> $mediaOptions
 */

$valore = (string) ($row['blocchi'] ?? '');
$video = $field['options'] ?? [];
?>
<div class="blocchi" data-blocchi>
  <input type="hidden" name="blocchi" value="<?= e($valore !== '' ? $valore : '[]') ?>" data-blocchi-json>
  <div class="blocchi-elenco" data-blocchi-elenco></div>
  <div class="blocchi-aggiungi">
    <span>Aggiungi in fondo:</span>
    <button type="button" class="ghost" data-nuovo="paragrafo">Paragrafo</button>
    <button type="button" class="ghost" data-nuovo="h2">Titolo H2</button>
    <button type="button" class="ghost" data-nuovo="h3">Titolo H3</button>
    <button type="button" class="ghost" data-nuovo="citazione">Citazione</button>
    <button type="button" class="ghost" data-nuovo="immagine">Immagine</button>
    <button type="button" class="ghost" data-nuovo="video">Video</button>
    <button type="button" class="ghost" data-nuovo="domanda">Domanda e risposta</button>
  </div>
  <p class="help">
    Nei paragrafi: <code>**grassetto**</code>, <code>*corsivo*</code>,
    <code>[testo del link](https://indirizzo)</code>. Le frecce spostano il blocco.
    A sinistra l'italiano, a destra l'inglese: l'articolo inglese esiste quando tutti i testi sono tradotti.
  </p>
</div>

<script type="application/json" data-blocchi-media><?= json_encode($mediaOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<script type="application/json" data-blocchi-video><?= json_encode($video, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>

<style>
  .blocchi-elenco { display: grid; gap: 0.75rem; margin: 0.5rem 0 1rem; }
  .blocco { border: 1px solid #d9d2c7; border-radius: 8px; padding: 0.75rem; background: #fffdfa; }
  .blocco-testa { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; }
  .blocco-testa strong { flex: 1; font-size: 0.85rem; letter-spacing: 0.05em; text-transform: uppercase; color: #7d6227; }
  .blocco-testa button { padding: 0.2rem 0.55rem; }
  .blocco .pair { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
  .blocco textarea { min-height: 6rem; }
  .blocco .barra { display: flex; gap: 0.3rem; margin-bottom: 0.3rem; }
  .blocco .barra button { padding: 0.1rem 0.5rem; font-size: 0.8rem; }
  .blocco-anteprima { max-width: 220px; max-height: 160px; object-fit: cover; border-radius: 6px; display: block; margin-top: 0.4rem; }
  .blocchi-aggiungi { display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; }
</style>

<script>
(() => {
  const radice = document.querySelector('[data-blocchi]');
  if (!radice) return;
  const campo = radice.querySelector('[data-blocchi-json]');
  const elenco = radice.querySelector('[data-blocchi-elenco]');
  const media = JSON.parse(document.querySelector('[data-blocchi-media]').textContent || '{}');
  const video = JSON.parse(document.querySelector('[data-blocchi-video]').textContent || '{}');
  let blocchi = [];
  try { blocchi = JSON.parse(campo.value) || []; } catch { blocchi = []; }

  const NOMI = { paragrafo: 'Paragrafo', h2: 'Titolo H2', h3: 'Titolo H3', citazione: 'Citazione in evidenza',
    immagine: 'Immagine', video: 'Video', domanda: 'Domanda e risposta' };
  const NUOVI = {
    paragrafo: () => ({ tipo: 'paragrafo', it: '', en: '' }),
    h2: () => ({ tipo: 'h2', it: '', en: '' }),
    h3: () => ({ tipo: 'h3', it: '', en: '' }),
    citazione: () => ({ tipo: 'citazione', it: '', en: '', firma: '' }),
    immagine: () => ({ tipo: 'immagine', media: 0, formato: 'orizzontale', didascalia_it: '', didascalia_en: '' }),
    video: () => ({ tipo: 'video', video: 0 }),
    domanda: () => ({ tipo: 'domanda', domanda_it: '', domanda_en: '', risposta_it: '', risposta_en: '' }),
  };

  const salva = () => { campo.value = JSON.stringify(blocchi); };
  const el = (tag, attr = {}, figli = []) => {
    const n = document.createElement(tag);
    Object.entries(attr).forEach(([k, v]) => (k === 'text' ? (n.textContent = v) : n.setAttribute(k, v)));
    figli.forEach((f) => n.append(f));
    return n;
  };

  // Avvolge la selezione di una textarea: grassetto, corsivo, link.
  const avvolgi = (area, prima, dopo) => {
    const { selectionStart: a, selectionEnd: b, value } = area;
    const scelto = value.slice(a, b) || 'testo';
    area.value = value.slice(0, a) + prima + scelto + dopo + value.slice(b);
    area.dispatchEvent(new Event('input'));
    area.focus();
  };

  const testo = (b, chiave, etichetta, lungo, conBarra) => {
    const area = lungo ? el('textarea') : el('input', { type: 'text' });
    area.value = b[chiave] || '';
    area.addEventListener('input', () => { b[chiave] = area.value; salva(); });
    const box = el('div', {}, [el('span', { text: etichetta })]);
    if (conBarra) {
      const link = el('button', { type: 'button', class: 'ghost', text: 'Link' });
      link.addEventListener('click', () => {
        const url = prompt('Indirizzo del link (https://… oppure /pagina/)');
        if (url) avvolgi(area, '[', '](' + url.trim() + ')');
      });
      const g = el('button', { type: 'button', class: 'ghost', text: 'B' });
      g.addEventListener('click', () => avvolgi(area, '**', '**'));
      const c = el('button', { type: 'button', class: 'ghost', text: 'I' });
      c.addEventListener('click', () => avvolgi(area, '*', '*'));
      box.append(el('div', { class: 'barra' }, [g, c, link]));
    }
    box.append(area);
    return box;
  };

  const coppia = (b, base, lungo, conBarra) => el('div', { class: 'pair' }, [
    testo(b, base ? base + '_it' : 'it', 'Italiano', lungo, conBarra),
    testo(b, base ? base + '_en' : 'en', 'English', lungo, conBarra),
  ]);

  const scelta = (b, chiave, opzioni, vuota) => {
    const s = el('select');
    s.append(el('option', { value: '', text: vuota }));
    Object.entries(opzioni).forEach(([id, nome]) => {
      if (id === '') return;
      const o = el('option', { value: id, text: nome });
      if (String(b[chiave]) === String(id)) o.selected = true;
      s.append(o);
    });
    s.addEventListener('change', () => { b[chiave] = Number(s.value) || 0; salva(); disegna(); });
    return s;
  };

  const corpo = (b) => {
    switch (b.tipo) {
      case 'paragrafo': return [coppia(b, '', true, true)];
      case 'h2':
      case 'h3': return [coppia(b, '', false, false)];
      case 'citazione': return [coppia(b, '', true, true), testo(b, 'firma', 'Firma (facoltativa, es. «Gaia»)', false, false)];
      case 'immagine': {
        const formato = el('select');
        [['orizzontale', 'Orizzontale 3:2'], ['verticale', 'Verticale 4:5'], ['quadrata', 'Quadrata'], ['larga', 'A tutta larghezza']]
          .forEach(([v, t]) => { const o = el('option', { value: v, text: t }); if (b.formato === v) o.selected = true; formato.append(o); });
        formato.addEventListener('change', () => { b.formato = formato.value; salva(); });
        const parti = [el('span', { text: 'Foto dalla libreria' }), scelta(b, 'media', media, '— scegli una foto —'),
          el('span', { text: 'Formato (il ritaglio segue il punto focale della foto)' }), formato,
          el('span', { text: 'Didascalia' }), coppia(b, 'didascalia', false, false)];
        if (b.media && media[b.media]) parti.splice(2, 0, el('img', { class: 'blocco-anteprima', src: '/uploads/' + media[b.media], alt: '' }));
        return parti;
      }
      case 'video': return [el('span', { text: 'Video dalla libreria (sezione «Video»)' }), scelta(b, 'video', video, '— scegli un video —')];
      case 'domanda': return [el('span', { text: 'Domanda' }), coppia(b, 'domanda', false, false),
        el('span', { text: 'Risposta' }), coppia(b, 'risposta', true, true)];
    }
    return [];
  };

  const disegna = () => {
    elenco.replaceChildren();
    blocchi.forEach((b, i) => {
      const su = el('button', { type: 'button', class: 'ghost', text: '↑', title: 'Sposta su' });
      const giu = el('button', { type: 'button', class: 'ghost', text: '↓', title: 'Sposta giù' });
      const via = el('button', { type: 'button', class: 'ghost', text: 'Elimina' });
      su.disabled = i === 0;
      giu.disabled = i === blocchi.length - 1;
      su.addEventListener('click', () => { [blocchi[i - 1], blocchi[i]] = [blocchi[i], blocchi[i - 1]]; salva(); disegna(); });
      giu.addEventListener('click', () => { [blocchi[i + 1], blocchi[i]] = [blocchi[i], blocchi[i + 1]]; salva(); disegna(); });
      via.addEventListener('click', () => { if (confirm('Eliminare questo blocco?')) { blocchi.splice(i, 1); salva(); disegna(); } });
      elenco.append(el('div', { class: 'blocco' }, [
        el('div', { class: 'blocco-testa' }, [el('strong', { text: (i + 1) + '. ' + (NOMI[b.tipo] || b.tipo) }), su, giu, via]),
        ...corpo(b),
      ]));
    });
  };

  radice.querySelectorAll('[data-nuovo]').forEach((bottone) => bottone.addEventListener('click', () => {
    blocchi.push(NUOVI[bottone.dataset.nuovo]());
    salva();
    disegna();
    elenco.lastElementChild?.scrollIntoView({ block: 'center' });
  }));

  disegna();
})();
</script>

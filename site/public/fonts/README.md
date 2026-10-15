# Font self-hosted

**Cormorant Garamond** e **Montserrat** come font **variabili** (asse `wght`):
un solo `.woff2` per famiglia copre tutti i pesi, invece di un file per peso.
Scaricati da Google Fonts il 15/10/2026.

```
cormorant-garamond-var.woff2             wght 300–700, normale, latin
cormorant-garamond-var-ext.woff2         idem, latin-ext (scaricato solo se serve)
cormorant-garamond-italic-var.woff2      wght 300–700, corsivo, latin
cormorant-garamond-italic-var-ext.woff2  idem, latin-ext
montserrat-var.woff2                     wght 100–900, latin
montserrat-var-ext.woff2                 idem, latin-ext
```

Le `@font-face` stanno in `src/styles/fonts.css`; i due file principali
(`cormorant-garamond-var` e `montserrat-var`) sono precaricati in
`BaseLayout.astro`.

Entrambi i font sono sotto **SIL Open Font License 1.1** — vedi `LICENSE.txt`.

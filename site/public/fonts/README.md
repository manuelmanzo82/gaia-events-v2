# Font self-hosted

Qui vanno i `.woff2` di **Cormorant Garamond** e **Montserrat**, da aggiungere
in fase di design. Nella v1 i font arrivavano da Google Fonts, quindi i file non
sono ancora in repo.

File attesi (sottoinsieme latin + latin-ext):

```
cormorant-garamond-300.woff2
cormorant-garamond-400.woff2
cormorant-garamond-400-italic.woff2
montserrat-300.woff2
montserrat-400.woff2
montserrat-500.woff2
```

Quando i file sono qui, togli i commenti in `src/styles/fonts.css` e aggiungi
il `<link rel="preload">` dei due pesi principali in `BaseLayout.astro`.

Entrambi i font sono sotto **SIL Open Font License 1.1**: il self-hosting è
consentito, il file di licenza va tenuto accanto ai font.

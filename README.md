# SvenDasGoogle – Google Business Bewertungen für Shopware 6

Shopware 6 Plugin, das **Google Business Bewertungen** als schwebendes Floating-Widget
und als CMS-Element in Erlebniswelten anzeigt. Live aus der Google Places API,
mit Cache und ohne Cookie-Banner-Probleme.

Entwickelt und gepflegt von **[Designburg.net](https://designburg.net)** – Shopware-
und Web-Agentur aus Hamburg. Brauchst du individuelle Anpassungen, weitere Plugins
oder Shopware-Hosting? Schreib uns: [shopware@designburg.net](mailto:shopware@designburg.net).

---

## Features

- **Floating Widget** auf jeder Storefront-Seite – Position und Farbe konfigurierbar
- **CMS-Element** für Erlebniswelten (Karussell mit den letzten Bewertungen)
- **Caching** der API-Antworten (Standard 6 h) – minimiert Google-API-Kosten
- Kompatibel mit Shopware ab **6.6**

Eine ausführliche Anleitung findest du in der [HANDBUCH.md](HANDBUCH.md).

---

## Installation

### Variante A: Per ZIP (empfohlen für Shop-Betreiber)

1. Im [Releases-Bereich](https://github.com/zvenson/dasGoogle/releases) die aktuelle
   `SvenDasGoogle-x.y.z.zip` herunterladen.
2. Im Shopware-Admin → **Erweiterungen** → **Meine Erweiterungen** → **Erweiterung hochladen**
   die ZIP auswählen.
3. Plugin **installieren** und **aktivieren**.
4. Konfigurieren – siehe [HANDBUCH.md](HANDBUCH.md).

### Variante B: Per Composer / Git (für Entwickler)

```bash
cd custom/plugins/
git clone https://github.com/zvenson/dasGoogle.git SvenDasGoogle
cd ../..
bin/console plugin:refresh
bin/console plugin:install --activate SvenDasGoogle
bin/console cache:clear
```

### Variante C: ZIP selbst bauen

```bash
git clone https://github.com/zvenson/dasGoogle.git SvenDasGoogle
zip -r SvenDasGoogle.zip SvenDasGoogle \
    -x "SvenDasGoogle/.git/*" "SvenDasGoogle/.github/*" "SvenDasGoogle/*.code-workspace"
```

Die entstandene ZIP kann direkt im Shopware-Admin hochgeladen werden.

---

## Konfiguration

Du brauchst einen **Google API Key** (Places API aktiviert) und eine **Place ID**
deines Google-Business-Eintrags. Die Schritt-für-Schritt-Anleitung steht im
[HANDBUCH.md](HANDBUCH.md).

---

## Support & individuelle Anpassungen

Dieses Plugin wird von **[Designburg.net](https://designburg.net)** entwickelt.

- Webseite: <https://designburg.net>
- Kontakt: [shopware@designburg.net](mailto:shopware@designburg.net)
- Weitere Shopware-Plugins: <https://designburg.net/shopware>

Für Bug-Reports und Feature-Requests bitte ein
[GitHub-Issue](https://github.com/zvenson/dasGoogle/issues) eröffnen.

---

## Lizenz

Proprietär – © [Designburg.net](https://designburg.net). Nutzung im eigenen
Shop ist gestattet, Weiterverkauf nicht.
